<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Driver;
use App\Models\LoadingRequest;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DriverAssigned;
use App\Notifications\TripApproved;
use App\Notifications\TripCompletionApproved;
use App\Notifications\TripCreatedNotification;
use App\Notifications\TripRejected;
use App\Notifications\TripSubmitted;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TripService
{
    public function generateTripNumber(): string
    {
        $year = now()->year;

        // Nomor trip selalu memakai format bertahun: TRIP-<tahun>-<urutan>.
        // Urutan direset per tahun sehingga tiap tahun mulai dari 0001.
        $prefix = "TRIP-{$year}-";

        // Cari urutan tertinggi untuk tahun berjalan. Termasuk soft-deleted
        // karena unique index tetap berlaku pada record terhapus.
        // Perhitungan dilakukan di PHP agar berjalan di MySQL maupun SQLite.
        $maxSeq = TripRequest::withTrashed()
            ->where('trip_number', 'like', "TRIP-{$year}-%")
            ->pluck('trip_number')
            ->map(function ($number) {
                return preg_match('/(\d+)$/', (string) $number, $m) ? (int) $m[1] : 0;
            })
            ->max() ?? 0;

        // Jaga-jaga: geser nomor hingga benar-benar unik
        $nextSeq = max(1, $maxSeq + 1);
        do {
            $candidate = $prefix.str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
            $exists = TripRequest::withTrashed()
                ->where('trip_number', $candidate)
                ->exists();
            if ($exists) {
                $nextSeq++;

                continue;
            }

            return $candidate;
        } while (true);
    }

    public function createTrip(array $data, $user): TripRequest
    {
        return DB::transaction(function () use ($data, $user) {
            // Hanya SuperAdmin yang dapat menentukan prioritas saat mengajukan.
            // Peran lain (admin departemen, karyawan) hanya dapat mengusulkan
            // prioritas lewat `requested_priority` + `urgency_notes`.
            $isSuperAdmin = $user->role?->name === 'SUPERADMIN';
            $finalPriority = $isSuperAdmin
                ? ($data['priority'] ?? TripRequest::PRIORITY_NORMAL)
                : TripRequest::PRIORITY_NORMAL;

            $departmentId = $isSuperAdmin ? $data['department_id'] : $user->department_id;

            // Trip dari departemen BOD (Board of Directors) otomatis disetujui:
            // tidak perlu submit maupun approval, langsung masuk alur penugasan driver.
            $isBodTrip = Department::isBod((int) $departmentId);

            if ($isBodTrip) {
                // BOD tidak mengisi informasi perjalanan. Isi nilai default
                // agar trip tetap valid dan siap dijadwalkan dispatcher.
                $data = array_merge($data, [
                    'destination' => $data['destination'] ?? 'Menunggu konfirmasi BOD',
                    'purpose' => $data['purpose'] ?? 'Perjalanan Board of Directors',
                    'departure_time' => $data['departure_time'] ?? now()->addDay()->setHour(8)->setMinute(0)->setSecond(0),
                    'passenger_count' => $data['passenger_count'] ?? 1,
                ]);
            }

            $trip = TripRequest::create([
                'trip_number' => $this->generateTripNumber(),
                'bod_link' => $data['bod_link'] ?? null,
                'requestor_id' => $user->id,
                'department_id' => $departmentId,
                'request_type' => $data['request_type'] ?? TripRequest::TYPE_BUSINESS_TRIP,
                'trip_date' => $data['trip_date'] ?? ($isBodTrip
                    ? now()->addDay()->toDateString()
                    : now()->toDateString()),
                'departure_time' => $data['departure_time'],
                'estimated_return_time' => $data['estimated_return_time'] ?? null,
                'destination' => $data['destination'],
                'destination_address' => $data['destination_address'] ?? null,
                'guest_pickup_location' => $data['guest_pickup_location'] ?? null,
                'guest_institution' => $data['guest_institution'] ?? null,
                'cargo_pickup_location' => $data['cargo_pickup_location'] ?? null,
                'cargo_description' => $data['cargo_description'] ?? null,
                'purpose' => $data['purpose'],
                'passenger_count' => $data['passenger_count'] ?? 1,
                'priority' => $finalPriority,
                'requested_priority' => $data['requested_priority'] ?? null,
                'urgency_notes' => $data['urgency_notes'] ?? null,
                'requires_loading_assistance' => $this->isPurchasingLoadingTrip($data, $departmentId)
                    ? true
                    : ($data['requires_loading_assistance'] ?? false),
                'status' => $isBodTrip ? TripRequest::STATUS_APPROVED : TripRequest::STATUS_DRAFT,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'approved_by' => $isBodTrip ? $user->id : null,
                'approved_at' => $isBodTrip ? now() : null,
                'approval_notes' => $isBodTrip ? 'Otomatis disetujui (departemen BOD).' : null,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'CREATE_TRIP',
                'module' => 'TripRequest',
                'record_type' => 'TripRequest',
                'record_id' => $trip->id,
                'new_value' => json_encode([
                    'trip_number' => $trip->trip_number,
                    'status' => $trip->status,
                    'destination' => $trip->destination,
                    'request_type' => $trip->request_type,
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            if ($isBodTrip) {
                // Catat auto-approval agar jejak audit tetap utuh, lalu lewati
                // seluruh alur submit/waiting-approval.
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'AUTO_APPROVE_TRIP',
                    'module' => 'TripRequest',
                    'record_type' => 'TripRequest',
                    'record_id' => $trip->id,
                    'new_value' => json_encode([
                        'status' => TripRequest::STATUS_APPROVED,
                        'reason' => 'Departemen BOD otomatis disetujui.',
                    ]),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                $trip->statusLogs()->create([
                    'from_status' => null,
                    'to_status' => TripRequest::STATUS_APPROVED,
                    'changed_by' => $user->id,
                    'notes' => 'Otomatis disetujui (departemen BOD).',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return $trip;
            }

            // Notifikasi ke superadmin saat trip dibuat dari departemen
            $this->notifySuperadminOnTripCreation($trip);

            if (isset($data['passengers']) && count($data['passengers']) > 0) {
                foreach ($data['passengers'] as $passengerData) {
                    $trip->passengers()->create($passengerData);
                }
            }

            // Driver pendukung loading dipilih langsung saat membuat trip
            // request (bisa lebih dari satu). Semua driver pendukung berstatus
            // STANDBY dan bersedia membantu loading (sudah divalidasi di
            // StoreTripRequest). Trip masih DRAFT — assignment pendukung
            // dibuat sekarang agar dispatcher tidak perlu menugaskan ulang.
            $supportDriverIds = collect($data['loading_support_driver_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($supportDriverIds->isNotEmpty()) {
                $this->assignLoadingSupportDrivers($trip, $supportDriverIds->all(), $user);
            }

            return $trip;
        });
    }

    /**
     * Catat driver pendukung loading pada trip yang baru dibuat.
     *
     * @param  array<int, int>  $driverIds
     */
    protected function assignLoadingSupportDrivers(TripRequest $trip, array $driverIds, $user): void
    {
        $drivers = Driver::whereIn('id', $driverIds)
            ->where('status', Driver::STATUS_STANDBY)
            ->where('willing_to_load', true)
            ->get();

        foreach ($drivers as $driver) {
            $trip->assignments()->create([
                'driver_id' => $driver->id,
                'vehicle_id' => null,
                'role' => TripAssignment::ROLE_LOADING_SUPPORT,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'driver_response' => TripAssignment::RESPONSE_PENDING,
            ]);

            $trip->loadingRequests()->create([
                'driver_id' => $driver->id,
                'status' => LoadingRequest::STATUS_PENDING,
                'notes' => 'Driver pendukung loading (dipilih saat membuat trip request)',
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ASSIGN_LOADING_SUPPORT',
                'module' => 'TripRequest',
                'record_type' => 'TripRequest',
                'record_id' => $trip->id,
                'new_value' => json_encode(['driver_id' => $driver->id, 'via' => 'trip_request']),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Notify superadmin when a trip is created from a department.
     */
    public function notifySuperadminOnTripCreation(TripRequest $trip): void
    {
        $superadmins = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['SUPERADMIN']);
        })->get();

        foreach ($superadmins as $superadmin) {
            $superadmin->notify(new TripCreatedNotification($trip));
        }
    }

    /**
     * Notify superadmin when a driver is assigned to a trip.
     */
    public function notifySuperadminOnDriverAssignment(TripAssignment $assignment): void
    {
        $superadmins = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['SUPERADMIN']);
        })->get();

        if (empty($superadmins)) {
            return;
        }

        foreach ($superadmins as $superadmin) {
            $link = $this->buildNotificationLink($superadmin);
            if ($link) {
                $superadmin->notify(new DriverAssignedNotification($assignment));
            }
        }
    }

    protected function buildNotificationLink(?User $user): ?string
    {
        return $user?->phone;
    }

    public function assignDriver(AssignDriverRequest $request, TripRequest $trip): RedirectResponse
    {
        if ($trip->status !== TripRequest::STATUS_DRAFT) {
            throw new \Exception('Hanya trip draft yang dapat ditetapkan driver.');
        }

        $this->tripService->assignDriverAndVehicle($trip, $request->driver_id, $request->vehicle_id);

        // Notifikasi ke superadmin saat driver ditetapkan
        $this->notifySuperadminOnDriverAssignment($trip->assignment);

        return back()->with('success', 'Driver dan kendaraan berhasil ditugaskan. Notifikasi dikirim ke superadmin.');
    }

    public function submitTrip(TripRequest $trip, $user): TripRequest
    {
        if ($trip->status !== TripRequest::STATUS_DRAFT) {
            throw new \Exception('Hanya trip draft yang dapat disubmit.');
        }

        $oldStatus = $trip->status;
        $trip->update([
            'status' => TripRequest::STATUS_SUBMITTED,
            'updated_by' => $user->id,
        ]);

        $this->logStatus($trip, $oldStatus, TripRequest::STATUS_SUBMITTED, $user);

        $trip->update([
            'status' => TripRequest::STATUS_WAITING_APPROVAL,
            'updated_by' => $user->id,
        ]);

        $this->logStatus($trip, TripRequest::STATUS_SUBMITTED, TripRequest::STATUS_WAITING_APPROVAL, $user);

        $trip->requestor->notify(new TripSubmitted($trip));

        return $trip->fresh();
    }

    public function approveTrip(
        TripRequest $trip,
        $user,
        ?string $notes = null,
        ?string $priority = null
    ): TripRequest {
        if ($trip->status !== TripRequest::STATUS_WAITING_APPROVAL) {
            throw new \Exception('Trip ini tidak dapat disetujui.');
        }

        $oldStatus = $trip->status;

        // Hanya SuperAdmin yang menetapkan prioritas final.
        // Prioritas lama tetap dipertahankan bila tidak diberikan.
        $finalPriority = $trip->priority;
        $prioritySetBy = $trip->priority_set_by;

        if ($priority && in_array($user->role?->name, ['SUPERADMIN'], true)) {
            $finalPriority = $priority;
            $prioritySetBy = 'SUPERADMIN';
        }

        $trip->update([
            'status' => TripRequest::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'priority' => $finalPriority,
            'priority_set_by' => $prioritySetBy,
            'updated_by' => $user->id,
        ]);

        $this->logStatus($trip, $oldStatus, TripRequest::STATUS_APPROVED, $user);

        // Create approval record
        $trip->approvals()->create([
            'approver_id' => $user->id,
            'decision' => 'APPROVE',
            'notes' => $notes,
            'decided_at' => now(),
        ]);

        // Move to WAITING_DRIVER status
        $trip->update(['status' => TripRequest::STATUS_WAITING_DRIVER]);
        $this->logStatus($trip, TripRequest::STATUS_APPROVED, TripRequest::STATUS_WAITING_DRIVER, $user);

        // Send notification to requestor
        $trip->requestor->notify(new TripApproved($trip));

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'APPROVE_TRIP',
            'module' => 'TripRequest',
            'record_type' => 'TripRequest',
            'record_id' => $trip->id,
            'old_value' => json_encode(['status' => $oldStatus]),
            'new_value' => json_encode(['status' => TripRequest::STATUS_WAITING_DRIVER]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $trip->fresh();
    }

    public function rejectTrip(TripRequest $trip, $user, string $reason): TripRequest
    {
        if ($trip->status !== TripRequest::STATUS_WAITING_APPROVAL) {
            throw new \Exception('Trip ini tidak dapat ditolak.');
        }

        $oldStatus = $trip->status;
        $trip->update([
            'status' => TripRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        $this->logStatus($trip, $oldStatus, TripRequest::STATUS_REJECTED, $user);

        $trip->approvals()->create([
            'approver_id' => $user->id,
            'decision' => 'REJECT',
            'notes' => $reason,
            'decided_at' => now(),
        ]);

        // Send notification to requestor
        $trip->requestor->notify(new TripRejected($trip, $reason));

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'REJECT_TRIP',
            'module' => 'TripRequest',
            'record_type' => 'TripRequest',
            'record_id' => $trip->id,
            'old_value' => json_encode(['status' => $oldStatus]),
            'new_value' => json_encode(['status' => TripRequest::STATUS_REJECTED]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $trip->fresh();
    }

    /**
     * Tetapkan driver untuk trip BOD dan kirim permintaan ke WhatsApp driver.
     *
     * Beda dengan assignDriverAndVehicle():
     * - tanpa kendaraan (kendaraan ditentukan belakangan oleh dispatcher),
     * - tanpa alur konfirmasi driver (PENDING/ACCEPTED) — trip langsung masuk
     *   status ON_TRIP-like WORKING: tidak perlu "assignment" lagi dari driver,
     * - pesan WhatsApp langsung dikirim otomatis ke nomor driver.
     *
     * @param  array{wa_link?: string|null}  &$meta  Output: tautan wa.me bila
     *                                               gateway otomatis tidak aktif.
     */
    public function assignDriverForBod(TripRequest $trip, $user, int $driverId, array &$meta = []): TripRequest
    {
        return DB::transaction(function () use ($trip, $user, $driverId, &$meta) {
            $driver = Driver::with('user')->findOrFail($driverId);

            if ($driver->isInactive()) {
                $label = Driver::statusLabels()[$driver->status] ?? $driver->status;
                throw new \Exception("Driver berstatus {$label} dan tidak dapat ditugaskan.");
            }

            $this->validateDriverAvailability($driver, $trip);

            $oldStatus = $trip->status;

            $trip->update([
                'status' => TripRequest::STATUS_DRIVER_ACCEPTED,
                'assigned_driver_id' => $driver->id,
                'updated_by' => $user->id,
            ]);

            $trip->assignment()->create([
                'driver_id' => $driver->id,
                'vehicle_id' => null,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'driver_accepted_at' => now(),
                'driver_response' => TripAssignment::RESPONSE_ACCEPTED,
            ]);

            $this->logStatus($trip, $oldStatus, TripRequest::STATUS_DRIVER_ACCEPTED, $user);
            // Trip terjadwal tetap membuat driver STANDBY sampai hari H.
            $this->syncDriverStatus($driver, $this->driverStatusAfterAssignment($trip), 'STANDBY', $user);

            $driver->user->notify(new DriverAssigned($trip));

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ASSIGN_DRIVER_BOD',
                'module' => 'TripRequest',
                'record_type' => 'TripRequest',
                'record_id' => $trip->id,
                'old_value' => json_encode(['driver_id' => null]),
                'new_value' => json_encode(['driver_id' => $driver->id, 'via' => 'BOD']),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Kirim langsung trip request ke WhatsApp driver.
            $meta = $this->sendTripRequestToDriver($trip, $driver);

            return $trip->fresh();
        });
    }

    /**
     * Kirim detail trip request ke WhatsApp driver.
     *
     * @return array{wa_link: ?string, sent: bool}
     */
    protected function sendTripRequestToDriver(TripRequest $trip, Driver $driver): array
    {
        $whatsApp = app(WhatsAppService::class);
        $phone = $driver->user?->phone ?: $driver->phone;
        $number = $whatsApp->normalizeNumber($phone);

        if (! $number) {
            return ['wa_link' => null, 'sent' => false];
        }

        $message = $this->buildBodTripMessage($trip, $driver);
        $result = $whatsApp->send($number, $message);

        // Gateway otomatis (fonnte/wablas) sukses -> pesan sudah terkirim.
        // Mode link / fallback -> sediakan tautan wa.me agar pengguna bisa
        // mengirim manual dengan satu klik.
        $viaLink = ($result['driver'] ?? '') === 'link';

        return [
            'wa_link' => ($result['link'] ?? null) ?: $whatsApp->buildWaLink($number, $message),
            'sent' => (bool) ($result['ok'] ?? false) && ! $viaLink,
        ];
    }

    /**
     * Susun pesan trip request untuk driver BOD.
     */
    protected function buildBodTripMessage(TripRequest $trip, Driver $driver): string
    {
        $fmt = fn ($value) => $value ? Carbon::parse($value)->format('d M Y H:i') : '-';

        $message = "━━━━━━━━━━━━━━━━━━━━━\n"
            ."*TUGASAN TRIP (BOD)*\n"
            .''."━━━━━━━━━━━━━━━━━━━━━\n"
            ."No. Trip    : *{$trip->trip_number}*\n"
            .'Departemen  : *'.($trip->department?->name ?? '-')."*\n"
            .'Tujuan      : *'.($trip->destination ?: '-')."*\n"
            .'Keperluan   : '.($trip->purpose ?: '-')."\n"
            .'Berangkat   : '.$fmt($trip->departure_time)."\n"
            .'Penumpang   : '.($trip->passenger_count ?? '-')."\n"
            .'Prioritas   : '.($trip->priority ?? '-')."\n";

        if ($trip->bod_link) {
            $message .= 'Tautan      : '.$trip->bod_link."\n";
        }

        return $message
            .''."━━━━━━━━━━━━━━━━━━━━━\n"
            .'Driver      : *'.($driver->user?->name ?? '-')."*\n\n"
            .'Mohon konfirmasi kesiapan kepada admin. Terima kasih.';
    }

    /**
     * Tetapkan driver utama + kendaraan untuk sebuah trip.
     *
     * Opsi `$additionalDriverIds` (feature multi-driver Purchasing dengan
     * bantuan loading) menambahkan driver pendukung loading. Seluruh driver
     * divalidasi (status, konflik jadwal, kesediaan loading) di dalam satu
     * transaksi database.
     *
     * @param  array<int, int>  $additionalDriverIds
     */
    public function assignDriverAndVehicle(
        TripRequest $trip,
        $user,
        int $driverId,
        int $vehicleId,
        array $additionalDriverIds = []
    ): TripRequest {
        return DB::transaction(function () use ($trip, $user, $driverId, $vehicleId, $additionalDriverIds) {
            // Kunci baris trip agar dua dispatcher tidak menugaskan bersamaan.
            $trip = TripRequest::whereKey($trip->getKey())->lockForUpdate()->firstOrFail();

            $driver = Driver::findOrFail($driverId);
            $vehicle = Vehicle::findOrFail($vehicleId);

            // Compatibility check: driver must be allowed to use the vehicle
            if (! $driver->allowedVehicles()->whereKey($vehicle->id)->exists()) {
                throw new \Exception('Driver tidak diizinkan menggunakan kendaraan ini.');
            }

            // Conflict check: driver availability and schedule
            $this->validateDriverAvailability($driver, $trip);

            // Conflict check: vehicle availability and schedule
            $this->validateVehicleAvailability($vehicle, $trip);

            // Driver harus bertugas (tidak sedang sakit / off / cuti)
            if ($driver->isInactive()) {
                $label = Driver::statusLabels()[$driver->status] ?? $driver->status;
                throw new \Exception("Driver berstatus {$label} dan tidak dapat ditugaskan.");
            }

            // Vehicle must be active and available for assignment
            if (in_array($vehicle->status, ['MAINTENANCE', 'OUT_OF_SERVICE', 'ON_TRIP'])) {
                throw new \Exception('Kendaraan tidak tersedia untuk ditugaskan.');
            }

            // Loading assistance check
            if ($trip->requires_loading_assistance && ! $driver->willing_to_load) {
                throw new \Exception('Driver tidak bersedia membantu loading. Pilih driver lain yang bersedia.');
            }

            // Driver pendukung loading (khusus trip ber-bantuan loading)
            $supportDrivers = $this->resolveLoadingSupportDrivers($trip, $driver, $additionalDriverIds);

            $oldStatus = $trip->status;

            // Update trip
            $trip->update([
                'status' => TripRequest::STATUS_DRIVER_ASSIGNED,
                'assigned_driver_id' => $driver->id,
                'assigned_vehicle_id' => $vehicle->id,
                'updated_by' => $user->id,
            ]);

            // Update vehicle status
            $vehicle->update(['status' => 'ASSIGNED']);

            // Create assignment record (driver utama)
            $trip->assignments()->create([
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'role' => TripAssignment::ROLE_MAIN,
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'driver_response' => TripAssignment::RESPONSE_PENDING,
            ]);

            // Create loading request if needed
            if ($trip->requires_loading_assistance) {
                $trip->loadingRequests()->create([
                    'driver_id' => $driver->id,
                    'status' => LoadingRequest::STATUS_PENDING,
                ]);
            }

            // Assignment + loading request untuk driver pendukung
            foreach ($supportDrivers as $supportDriver) {
                $trip->assignments()->create([
                    'driver_id' => $supportDriver->id,
                    'vehicle_id' => null,
                    'role' => TripAssignment::ROLE_LOADING_SUPPORT,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                    'driver_response' => TripAssignment::RESPONSE_PENDING,
                ]);

                $trip->loadingRequests()->create([
                    'driver_id' => $supportDriver->id,
                    'status' => LoadingRequest::STATUS_PENDING,
                    'notes' => 'Driver pendukung loading',
                ]);

                // Trip terjadwal membuat driver pendukung juga tetap STANDBY.
                $this->syncDriverStatus($supportDriver, $this->driverStatusAfterAssignment($trip), 'STANDBY', $user);
            }

            $this->logStatus($trip, $oldStatus, TripRequest::STATUS_DRIVER_ASSIGNED, $user);

            // Log driver status change (dilewati bila driver sedang tidak bekerja).
            // Trip terjadwal masa depan membuat driver tetap STANDBY.
            $this->syncDriverStatus($driver, $this->driverStatusAfterAssignment($trip), 'STANDBY', $user);

            // Send notification to setiap driver yang ditugaskan
            foreach ($this->assignedDriverUsers($trip, $driver, $supportDrivers) as $driverUser) {
                $driverUser->notify(new DriverAssigned($trip));
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ASSIGN_DRIVER',
                'module' => 'TripRequest',
                'record_type' => 'TripRequest',
                'record_id' => $trip->id,
                'old_value' => json_encode(['driver_id' => null, 'vehicle_id' => null]),
                'new_value' => json_encode([
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'loading_support_driver_ids' => $supportDrivers->pluck('id')->values()->all(),
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $trip->fresh();
        });
    }

    /**
     * Validasi & muat driver pendukung loading.
     *
     * @param  array<int, int>  $additionalDriverIds
     * @return Collection<int, Driver>
     */
    protected function resolveLoadingSupportDrivers(
        TripRequest $trip,
        Driver $mainDriver,
        array $additionalDriverIds
    ): Collection {
        $ids = collect($additionalDriverIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->reject(fn ($id) => $id === (int) $mainDriver->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        if (! $this->allowsMultipleDrivers($trip)) {
            throw new \Exception('Penugasan lebih dari satu driver hanya untuk pengajuan Purchasing dengan bantuan loading.');
        }

        $drivers = Driver::with('user')->whereIn('id', $ids)->get();

        if ($drivers->count() !== $ids->count()) {
            throw new \Exception('Sebagian driver pendukung yang dipilih tidak valid.');
        }

        foreach ($drivers as $driver) {
            if ($driver->isInactive()) {
                $label = Driver::statusLabels()[$driver->status] ?? $driver->status;
                throw new \Exception("Driver {$driver->user?->name} berstatus {$label} dan tidak dapat ditugaskan.");
            }

            if (! $driver->willing_to_load) {
                throw new \Exception("Driver {$driver->user?->name} tidak bersedia membantu loading. Pilih driver lain yang bersedia.");
            }

            $this->validateDriverAvailability($driver, $trip);
        }

        return $drivers;
    }

    /**
     * Kumpulkan akun user semua driver yang ditugaskan (utama + pendukung).
     *
     * @param  Collection<int, Driver>  $supportDrivers
     * @return Collection<int, User>
     */
    protected function assignedDriverUsers(TripRequest $trip, Driver $mainDriver, Collection $supportDrivers): Collection
    {
        $users = collect([$mainDriver->user])->filter();

        foreach ($supportDrivers as $supportDriver) {
            if ($supportDriver->user) {
                $users->push($supportDriver->user);
            }
        }

        return $users->unique('id')->values();
    }

    /**
     * Multi-driver hanya berlaku untuk trip yang membutuhkan bantuan loading.
     */
    public function allowsMultipleDrivers(TripRequest $trip): bool
    {
        return (bool) $trip->requires_loading_assistance;
    }

    /**
     * Cek apakah trip berasal dari departemen Purchasing / pengadaan barang.
     */
    protected function isPurchasingLoadingTrip(array $data, ?int $departmentId): bool
    {
        if (empty($data['requires_loading_assistance'])) {
            return false;
        }

        $departmentName = Department::whereKey($departmentId)->value('name');

        return is_string($departmentName)
            && str_contains(mb_strtolower($departmentName), 'purchasing');
    }

    /**
     * Cek konflik jadwal driver.
     *
     * Konflik dibaca dari tabel `trip_assignments` (bukan kolom driver.status)
     * sehingga driver yang sedang STANDBY karena trip-nya belum masuk tanggal
     * tetap terdeteksi bila jadwalnya bentrok.
     */
    protected function validateDriverAvailability(Driver $driver, TripRequest $trip): void
    {
        $newDeparture = $trip->departure_time;
        $newReturn = $this->scheduleEnd($trip);

        $conflictTrips = TripRequest::query()
            ->whereKeyNot($trip->getKey())
            ->whereIn('status', TripRequest::driverBlockingStatuses())
            ->where(function ($query) use ($driver) {
                $query->where('assigned_driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
            ->get();

        foreach ($conflictTrips as $existingTrip) {
            if ($this->timeRangesOverlap(
                $existingTrip->departure_time,
                $this->scheduleEnd($existingTrip),
                $newDeparture,
                $newReturn
            )) {
                throw new \Exception('Driver tidak tersedia pada waktu tersebut karena memiliki perjalanan lain.');
            }
        }
    }

    /**
     * Batas akhir jadwal sebuah trip untuk pengecekan konflik.
     */
    protected function scheduleEnd(TripRequest $trip): Carbon
    {
        $end = $trip->estimated_return_time ?? $trip->departure_time;

        return Carbon::parse($end);
    }

    /**
     * True bila jadwal trip masih di masa depan (belum hari H).
     */
    public function isScheduledInFuture(TripRequest $trip): bool
    {
        return $trip->departure_time !== null
            && Carbon::parse($trip->departure_time)->isAfter(Carbon::now()->endOfDay());
    }

    /**
     * Status driver setelah penugasan.
     *
     * Trip yang jadwalnya masih di masa depan TIDAK mengubah status driver —
     * driver tetap STANDBY dan masih bisa menerima tugas lain di hari berjalan
     * (bentrok jadwal tetap dicek dari tabel trip_assignments).
     */
    /**
     * Status driver setelah ditugaskan.
     *
     * Driver yang sudah ditugaskan SELALU berstatus ASSIGNED (ditugaskan) —
     * tidak boleh lagi tampak STANDBY. Status baru berubah menjadi ON_TRIP
     * (sedang bertugas) ketika driver benar-benar memulai perjalanan.
     */
    protected function driverStatusAfterAssignment(TripRequest $trip): string
    {
        return Driver::STATUS_ASSIGNED;
    }

    /**
     * Bila driver masih tercatat ASSIGNED/ACCEPTED padahal trip-nya sudah
     * selesai / dibatalkan / dijadwalkan di masa depan, kembalikan ke STANDBY.
     */
    protected function releaseDriverIfIdle(?Driver $driver, $user = null, ?int $tripId = null): void
    {
        if (! $driver || $driver->isInactive()) {
            return;
        }

        // Trip yang sedang diproses tidak dihitung (misal baru saja selesai).
        $tripId = $tripId ?? 0;

        $stillWorking = TripRequest::query()
            ->whereIn('status', [
                TripRequest::STATUS_ON_TRIP,
                TripRequest::STATUS_COMPLETION_SUBMITTED,
            ])
            ->whereKeyNot($tripId ?? 0)
            ->where(function ($query) use ($driver) {
                $query->where('assigned_driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
            ->exists();

        // Trip hari ini yang MASIH berjalan (trip yang baru selesai tidak
        // menghalangi driver kembali STANDBY).
        $hasTripToday = TripRequest::query()
            ->whereIn('status', [
                TripRequest::STATUS_ON_TRIP,
                TripRequest::STATUS_COMPLETION_SUBMITTED,
            ])
            ->whereKeyNot($tripId)
            ->whereDate('departure_time', Carbon::today())
            ->where(function ($query) use ($driver) {
                $query->where('assigned_driver_id', $driver->id)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driver->id));
            })
            ->exists();

        if ($stillWorking || $hasTripToday) {
            return;
        }

        $this->syncDriverStatus($driver, Driver::STATUS_STANDBY, $driver->status, $user);
    }

    protected function validateVehicleAvailability(Vehicle $vehicle, TripRequest $trip): void
    {
        $conflictTrips = TripRequest::where('assigned_vehicle_id', $vehicle->id)
            ->whereIn('status', ['ON_TRIP', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'DRIVER_ASSIGNED'])
            ->get();

        foreach ($conflictTrips as $existingTrip) {
            $existingDeparture = $existingTrip->departure_time;
            $existingReturn = $existingTrip->estimated_return_time ?? $existingTrip->departure_time;
            $newDeparture = $trip->departure_time;
            $newReturn = $trip->estimated_return_time ?? $trip->departure_time;

            if ($this->timeRangesOverlap($existingDeparture, $existingReturn, $newDeparture, $newReturn)) {
                throw new \Exception('Kendaraan tidak tersedia pada waktu tersebut karena memiliki perjalanan lain.');
            }
        }
    }

    protected function timeRangesOverlap($start1, $end1, $start2, $end2): bool
    {
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);

        return $start1 < $end2 && $start2 < $end1;
    }

    public function driverResponse(TripRequest $trip, $user, string $action, ?string $reason = null): TripRequest
    {
        $driver = $user->driver;
        if (! $driver || $trip->assigned_driver_id !== $driver->id) {
            throw new \Exception('Anda tidak memiliki akses ke penugasan ini.');
        }

        return DB::transaction(function () use ($trip, $user, $action, $reason, $driver) {
            if ($action === 'reject') {
                $oldStatus = $trip->status;
                $trip->update([
                    'status' => TripRequest::STATUS_DRIVER_REJECTED,
                    'updated_by' => $user->id,
                ]);
                $this->logStatus($trip, $oldStatus, TripRequest::STATUS_DRIVER_REJECTED, $user);

                $trip->assignment()->update([
                    'driver_response' => TripAssignment::RESPONSE_REJECTED,
                    'driver_rejection_reason' => $reason,
                ]);

                $this->releaseDriverIfIdle($driver, $user);
                $trip->vehicle?->update(['status' => 'AVAILABLE']);
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'DRIVER_REJECT',
                    'module' => 'TripRequest',
                    'record_type' => 'TripRequest',
                    'record_id' => $trip->id,
                    'old_value' => json_encode(['status' => $oldStatus]),
                    'new_value' => json_encode(['status' => TripRequest::STATUS_DRIVER_REJECTED, 'reason' => $reason]),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return $trip->fresh();
            }

            if ($action === 'accept') {
                $oldStatus = $trip->status;
                $trip->update([
                    'status' => TripRequest::STATUS_DRIVER_ACCEPTED,
                    'updated_by' => $user->id,
                ]);
                $this->logStatus($trip, $oldStatus, TripRequest::STATUS_DRIVER_ACCEPTED, $user);

                $trip->assignment()->update([
                    'driver_response' => TripAssignment::RESPONSE_ACCEPTED,
                    'driver_accepted_at' => now(),
                ]);

                // Driver yang sudah menerima penugasan berstatus ACCEPTED.
                $this->syncDriverStatus($driver, Driver::STATUS_ACCEPTED, 'ASSIGNED', $user);

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'DRIVER_ACCEPT',
                    'module' => 'TripRequest',
                    'record_type' => 'TripRequest',
                    'record_id' => $trip->id,
                    'old_value' => json_encode(['status' => $oldStatus]),
                    'new_value' => json_encode(['status' => TripRequest::STATUS_DRIVER_ACCEPTED]),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return $trip->fresh();
            }

            throw new \Exception('Aksi tidak valid.');
        });
    }

    public function startTrip(TripRequest $trip, $user): TripRequest
    {
        $driver = $user->driver;

        // Driver utama maupun driver pendukung loading dapat memulai perjalanan.
        if (! $driver || ! $trip->hasDriver($user)) {
            throw new \Exception('Anda tidak memiliki akses ke perjalanan ini.');
        }

        if (! in_array($trip->status, [TripRequest::STATUS_DRIVER_ACCEPTED, TripRequest::STATUS_READY_TO_DEPART])) {
            throw new \Exception('Perjalanan tidak siap untuk dimulai.');
        }

        return DB::transaction(function () use ($trip, $user, $driver) {
            $oldStatus = $trip->status;
            $trip->update([
                'status' => TripRequest::STATUS_ON_TRIP,
                'started_at' => now(),
                'started_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $this->logStatus($trip, $oldStatus, TripRequest::STATUS_ON_TRIP, $user);

            $this->syncDriverStatus($driver, 'ON_TRIP', $driver->getOriginal('status'), $user);

            $trip->vehicle?->update(['status' => 'ON_TRIP']);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'START_TRIP',
                'module' => 'TripRequest',
                'record_type' => 'TripRequest',
                'record_id' => $trip->id,
                'old_value' => json_encode(['status' => $oldStatus, 'started_at' => null]),
                'new_value' => json_encode(['status' => TripRequest::STATUS_ON_TRIP, 'started_at' => now()->toDateTimeString()]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $trip->fresh();
        });
    }

    /**
     * Pengakhiran perjalanan (dual trigger).
     *
     * Driver yang ditugaskan ATAU Employee pemohon dapat menekan "Selesaikan
     * Perjalanan" saat status ON_TRIP. Perjalanan langsung berstatus COMPLETED
     * tanpa perlu approval Admin/SuperAdmin lagi — driver bebas mengakhiri
     * perjalanan sendiri, dan pemohon dapat mengakhirinya bila driver lupa
     * setelah permintaannya selesai/sudah pulang.
     */
    public function submitCompletion(TripRequest $trip, $user, ?string $notes = null): TripRequest
    {
        $role = $user->role?->name;
        $driver = $user->driver;
        $isAssignedDriver = $driver && $trip->hasDriver($user);
        $isRequestor = (int) $trip->requestor_id === (int) $user->id;

        if (! $isAssignedDriver && ! $isRequestor) {
            throw new \Exception('Anda tidak memiliki akses ke perjalanan ini.');
        }

        if (! in_array($role, ['DRIVER', 'EMPLOYEE', 'SUPERADMIN', 'ADMIN'], true) && ! $isRequestor) {
            throw new \Exception('Anda tidak berhak menyelesaikan perjalanan ini.');
        }

        if ($trip->status === TripRequest::STATUS_COMPLETION_SUBMITTED) {
            throw new \Exception('Pengajuan selesai perjalanan sudah dibuat dan sedang menunggu persetujuan.');
        }

        if ($trip->status === TripRequest::STATUS_COMPLETED) {
            throw new \Exception('Perjalanan ini sudah selesai.');
        }

        if ($trip->status !== TripRequest::STATUS_ON_TRIP) {
            throw new \Exception('Perjalanan harus sedang berlangsung untuk dapat diselesaikan.');
        }

        if (! $trip->started_at) {
            throw new \Exception('Perjalanan harus dimulai terlebih dahulu.');
        }

        return DB::transaction(function () use ($trip, $user, $notes, $isAssignedDriver) {
            $trip = TripRequest::whereKey($trip->getKey())->lockForUpdate()->firstOrFail();

            if ($trip->status !== TripRequest::STATUS_ON_TRIP) {
                throw new \Exception('Status perjalanan sudah berubah. Muat ulang halaman.');
            }

            $oldStatus = $trip->status;

            $trip->update([
                'status' => TripRequest::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $user->id,
                'actual_return_time' => now(),
                'completion_notes' => $notes !== null && $notes !== '' ? $notes : $trip->completion_notes,
                'updated_by' => $user->id,
            ]);

            $this->logStatus($trip, $oldStatus, TripRequest::STATUS_COMPLETED, $user);

            // Kendaraan kembali AVAILABLE dan semua driver (utama + pendukung
            // loading) kembali STANDBY bila tidak punya trip aktif lain.
            $trip->vehicle?->update(['status' => 'AVAILABLE']);

            $driverIds = $trip->assignments()->pluck('driver_id')->push($trip->assigned_driver_id)
                ->filter()->unique()->values();

            foreach (Driver::whereIn('id', $driverIds)->get() as $driver) {
                $this->releaseDriverIfIdle($driver, $user);
            }

            $this->notifyCompletionApproved($trip);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'COMPLETE_TRIP',
                'module' => 'TripRequest',
                'record_type' => 'TripRequest',
                'record_id' => $trip->id,
                'old_value' => json_encode(['status' => $oldStatus]),
                'new_value' => json_encode([
                    'status' => TripRequest::STATUS_COMPLETED,
                    'completed_at' => now()->toDateTimeString(),
                    'completed_as' => $isAssignedDriver ? 'DRIVER' : 'EMPLOYEE',
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $trip->fresh();
        });
    }

    /**
     * @deprecated Penyelesaian perjalanan kini langsung oleh driver/pemohon
     *             tanpa approval. Method ini hanya meneruskan ke submitCompletion().
     */
    public function completeTrip(TripRequest $trip, $user, ?string $notes = null): TripRequest
    {
        return $this->submitCompletion($trip, $user, $notes);
    }

    /**
     * Notifikasi ke driver & pemohon setelah penyelesaian disetujui.
     */
    protected function notifyCompletionApproved(TripRequest $trip): void
    {
        $recipients = collect();

        if ($trip->requestor) {
            $recipients->push($trip->requestor);
        }

        $driverIds = $trip->assignments()->pluck('driver_id')->push($trip->assigned_driver_id)
            ->filter()->unique()->values();

        $driverUsers = User::whereIn('id', Driver::whereIn('id', $driverIds)->pluck('user_id'))->get();

        $recipients = $recipients->concat($driverUsers)->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new TripCompletionApproved($trip));
        }
    }

    public function cancelTrip(TripRequest $trip, $user): TripRequest
    {
        $oldStatus = $trip->status;
        $trip->update([
            'status' => TripRequest::STATUS_CANCELLED,
            'updated_by' => $user->id,
        ]);
        $this->logStatus($trip, $oldStatus, TripRequest::STATUS_CANCELLED, $user);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'CANCEL_TRIP',
            'module' => 'TripRequest',
            'record_type' => 'TripRequest',
            'record_id' => $trip->id,
            'old_value' => json_encode(['status' => $oldStatus]),
            'new_value' => json_encode(['status' => TripRequest::STATUS_CANCELLED]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $trip->fresh();
    }

    protected function logStatus(TripRequest $trip, ?string $fromStatus, string $toStatus, $user): void
    {
        $trip->statusLogs()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function logDriverStatus(Driver $driver, ?string $fromStatus, string $toStatus, $user): void
    {
        $driver->statusLogs()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Update status driver melalui alur otomatis perjalanan.
     *
     * Status manual "tidak bekerja" (SICK / OFF_DUTY / LEAVE / UNAVAILABLE)
     * yang ditetapkan SuperAdmin TIDAK BOLEH ditimpa oleh alur otomatis.
     */
    protected function syncDriverStatus(Driver $driver, string $status, ?string $fromStatus = null, $user = null): void
    {
        if ($driver->isInactive()) {
            return; // Pertahankan status tidak-bekerja
        }

        $driver->update(['status' => $status]);
        $driver->update(['last_status_change_at' => now()]);

        if ($user) {
            $this->logDriverStatus($driver, $fromStatus, $status, $user);
        }
    }
}
