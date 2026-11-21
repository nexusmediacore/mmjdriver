<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BeritaAcara;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function users(): Response
    {
        $users = User::with('role', 'department')->get();
        $roles = Role::orderBy('id')->get();
        $departments = Department::orderBy('name')->get();

        return Inertia::render('Admin/Users', [
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role?->name,
                'role_id' => $u->role_id,
                'role_display_name' => $u->role?->display_name ?? $u->role?->name,
                'department' => $u->department?->name,
                'department_id' => $u->department_id,
                'phone' => $u->phone,
                'is_active' => $u->is_active,
            ]),
            'roles' => $roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'display_name' => $r->display_name ?? $r->name,
            ]),
            'departments' => $departments->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
            ]),
        ]);
    }

    /**
     * Daftar akun ber-role DRIVER yang belum terdaftar sebagai driver.
     * Dipakai form "Tambah Driver" agar datanya selalu terbaru — tidak
     * bergantung pada props Inertia yang bisa bersifat basi.
     */
    public function availableDriverUsers(): JsonResponse
    {
        $users = User::query()
            ->whereDoesntHave('driver')
            ->whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'users' => $users,
            'count' => $users->count(),
        ]);
    }

    public function drivers(): Response
    {
        $drivers = Driver::with(['user', 'allowedVehicles'])->get();

        // User BER-ROLE DRIVER yang belum memiliki record driver.
        // Hanya user inilah yang layak dijadikan driver baru.
        $availableUsers = User::query()
            ->whereDoesntHave('driver')
            ->whereHas('role', fn ($q) => $q->where('name', 'DRIVER'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Drivers', [
            'drivers' => $drivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user?->name ?? '-',
                'license_number' => $d->license_number,
                // Nomor telepon mengikuti data terbaru di akun user (diubah
                // lewat User Management), fallback ke data driver lama.
                'phone' => $d->user?->phone ?: $d->phone,
                'vehicle_plate' => $d->vehicle_plate,
                'vehicle_type' => $d->vehicle_type,
                'status' => $d->status,
                'willing_to_load' => $d->willing_to_load,
                'allowed_vehicle_ids' => $d->allowedVehicles->pluck('id')->toArray(),
            ]),
            'available_users' => $availableUsers,
        ]);
    }

    public function driverAllowedVehicles(Driver $driver): Response
    {
        $driver->load('user', 'allowedVehicles');
        $allVehicles = Vehicle::all();

        return Inertia::render('Admin/DriverVehicles', [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->user->name,
                'status' => $driver->status,
            ],
            'vehicles' => $allVehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'brand' => $v->brand,
                'model' => $v->model,
                'type' => $v->type,
                'status' => $v->status,
            ]),
            'allowed_vehicle_ids' => $driver->allowedVehicles->pluck('id')->toArray(),
        ]);
    }

    public function updateDriverVehicles(Request $request, Driver $driver): RedirectResponse
    {
        $request->validate([
            'vehicle_ids' => 'array',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        $driver->allowedVehicles()->sync($request->vehicle_ids ?? []);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_DRIVER_VEHICLES',
            'module' => 'Driver',
            'record_type' => 'Driver',
            'record_id' => $driver->id,
            'old_value' => json_encode($driver->getOriginal('allowedVehicles') ?? []),
            'new_value' => json_encode($request->vehicle_ids ?? []),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Daftar kendaraan driver berhasil diperbarui.');
    }

    public function vehicles(): Response
    {
        $vehicles = Vehicle::with('primaryDriver.user', 'authorizedDrivers.user')->get();

        return Inertia::render('Admin/Vehicles', [
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'brand' => $v->brand,
                'model' => $v->model,
                'type' => $v->type,
                'year' => $v->year,
                'passenger_capacity' => $v->passenger_capacity,
                'status' => $v->status,
                'last_service_date' => $v->last_service_date,
                'authorized_driver_ids' => $v->authorizedDrivers->pluck('id')->toArray(),
            ]),
        ]);
    }

    public function vehicleAuthorizedDrivers(Vehicle $vehicle): Response
    {
        $vehicle->load('authorizedDrivers.user');
        $allDrivers = Driver::with('user')->get();

        return Inertia::render('Admin/VehicleDrivers', [
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'type' => $vehicle->type,
                'status' => $vehicle->status,
            ],
            'drivers' => $allDrivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user->name,
                'license_number' => $d->license_number,
                'phone' => $d->user?->phone ?: $d->phone,
                'status' => $d->status,
            ]),
            'authorized_driver_ids' => $vehicle->authorizedDrivers->pluck('id')->toArray(),
        ]);
    }

    public function updateVehicleDrivers(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $request->validate([
            'driver_ids' => 'array',
            'driver_ids.*' => 'exists:drivers,id',
        ]);

        $vehicle->authorizedDrivers()->sync($request->driver_ids ?? []);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'UPDATE_VEHICLE_DRIVERS',
            'module' => 'Vehicle',
            'record_type' => 'Vehicle',
            'record_id' => $vehicle->id,
            'old_value' => json_encode([]),
            'new_value' => json_encode($request->driver_ids ?? []),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Daftar driver kendaraan berhasil diperbarui.');
    }

    public function departments(): Response
    {
        $departments = Department::with('users:id,name,department_id')->get();
        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Departments', [
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ]),
            'departments' => $departments->map(fn ($d) => [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'description' => $d->description,
                'user_count' => $d->users->count(),
                'user_ids' => $d->users->pluck('id')->values()->all(),
            ]),
        ]);
    }

    public function beritaAcara(): Response
    {
        $beritaAcaras = BeritaAcara::with(['tripRequest', 'driver.user', 'vehicle'])
            ->latest()
            ->get();

        return Inertia::render('Admin/BeritaAcara', [
            'beritaAcaras' => $beritaAcaras->map(fn ($ba) => [
                'id' => $ba->id,
                'ba_number' => $ba->ba_number,
                'trip_number' => $ba->tripRequest?->trip_number,
                'driver' => $ba->driver?->user?->name,
                'vehicle' => $ba->vehicle ? "{$ba->vehicle->plate_number} ({$ba->vehicle->brand})" : null,
                'destination' => $ba->destination,
                'purpose' => $ba->purpose,
                'departure_time' => $ba->departure_time,
                'return_time' => $ba->return_time,
                'created_at' => $ba->created_at,
            ]),
        ]);
    }

    public function auditLogs(): Response
    {
        $logs = AuditLog::with('user')->latest()->limit(100)->get();

        return Inertia::render('Admin/AuditLog', [
            'logs' => $logs->map(fn ($l) => [
                'id' => $l->id,
                'user' => $l->user?->name,
                'action' => $l->action,
                'module' => $l->module,
                'record_type' => $l->record_type,
                'record_id' => $l->record_id,
                'old_value' => $l->old_value,
                'new_value' => $l->new_value,
                'ip_address' => $l->ip_address,
                'created_at' => $l->created_at,
            ]),
        ]);
    }

    public function settings(): Response
    {
        $whatsApp = app(WhatsAppService::class);
        $config = $whatsApp->configuration();

        return Inertia::render('Admin/Settings', [
            'whatsapp' => [
                'driver' => $config['driver'],
                'token' => (string) Setting::get('whatsapp_token', (string) config('whatsapp.token', '')),
                'endpoint' => (string) Setting::get('whatsapp_endpoint', (string) config('whatsapp.endpoint')),
                'has_token' => $config['has_token'],
                'auto_send' => $config['auto_send'],
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'whatsapp_driver' => 'required|in:fonnte,wablas,link,log',
            'whatsapp_token' => 'nullable|string|max:255',
            'whatsapp_endpoint' => 'nullable|string|max:255',
        ], [
            'whatsapp_driver.required' => 'Metode pengiriman WhatsApp wajib dipilih.',
            'whatsapp_driver.in' => 'Metode pengiriman WhatsApp tidak valid.',
        ]);

        $driver = $request->input('whatsapp_driver');

        Setting::set('whatsapp_driver', $driver, 'whatsapp');

        // Token hanya diperbarui bila diisi (kunci tidak ditampilkan ulang)
        $token = $request->input('whatsapp_token');
        if ($token !== null && $token !== '') {
            Setting::set('whatsapp_token', $token, 'whatsapp');
        }
        // Bila driver bukan gateway, hapus token agar tidak terkirim nyasar
        if (! in_array($driver, ['fonnte', 'wablas'], true)) {
            Setting::forget('whatsapp_token');
        }

        if ($request->filled('whatsapp_endpoint')) {
            Setting::set('whatsapp_endpoint', $request->input('whatsapp_endpoint'), 'whatsapp');
        }

        // Bersihkan cache konfigurasi WhatsApp agar langsung berlaku
        Cache::forget('whatsapp_config');

        $message = in_array($driver, ['fonnte', 'wablas'], true) && Setting::get('whatsapp_token')
            ? 'Pengaturan tersimpan. Notifikasi tugas kini terkirim OTOMATIS ke WhatsApp driver.'
            : 'Pengaturan tersimpan.';

        return back()->with('success', $message);
    }

    public function driverAvailability(): Response
    {
        $drivers = Driver::with('user')->get();

        return Inertia::render('Dispatcher/DriverAvailability', [
            'drivers' => $drivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user->name,
                'license_number' => $d->license_number,
                'phone' => $d->user?->phone ?: $d->phone,
                'vehicle_plate' => $d->vehicle_plate,
                'status' => $d->status,
                'willing_to_load' => $d->willing_to_load,
                'last_status_change_at' => $d->last_status_change_at,
            ]),
        ]);
    }

    public function vehicleAvailability(): Response
    {
        $vehicles = Vehicle::all();

        return Inertia::render('Dispatcher/VehicleAvailability', [
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'brand' => $v->brand,
                'model' => $v->model,
                'type' => $v->type,
                'status' => $v->status,
                'primary_driver' => $v->primaryDriver ? $v->primaryDriver->user->name : null,
            ]),
        ]);
    }

    public function activeTrips(): Response
    {
        $trips = TripRequest::with(['requestor', 'driver.user', 'vehicle', 'department'])
            ->whereIn('status', ['ON_TRIP', 'COMPLETION_SUBMITTED', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'DRIVER_ASSIGNED'])
            ->latest()
            ->get();

        return Inertia::render('Dispatcher/ActiveTrips', [
            'trips' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'trip_number' => $t->trip_number,
                'destination' => $t->destination,
                'purpose' => $t->purpose,
                'departure_time' => $t->departure_time,
                'status' => $t->status,
                'priority' => $t->priority,
                'driver' => $t->driver?->user?->name,
                'vehicle' => $t->vehicle ? "{$t->vehicle->plate_number} ({$t->vehicle->brand})" : null,
            ]),
        ]);
    }

    public function schedule(): Response
    {
        $trips = TripRequest::with(['driver.user', 'vehicle'])
            ->whereDate('departure_time', '>=', Carbon::today())
            ->whereDate('departure_time', '<=', Carbon::today()->addDays(7))
            ->orderBy('departure_time')
            ->get();

        return Inertia::render('Dispatcher/Schedule', [
            'trips' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'trip_number' => $t->trip_number,
                'destination' => $t->destination,
                'departure_time' => $t->departure_time,
                'status' => $t->status,
                'driver' => $t->driver?->user?->name,
                'vehicle' => $t->vehicle ? $t->vehicle->plate_number : null,
            ]),
        ]);
    }

    public function pendingApprovals(): Response
    {
        $user = Auth::user();
        $trips = TripRequest::with(['requestor', 'department'])
            ->where('status', 'WAITING_APPROVAL')
            ->where('department_id', $user->department_id)
            ->latest()
            ->get();

        return Inertia::render('Approver/PendingApprovals', [
            'pending_requests' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'trip_number' => $t->trip_number,
                'destination' => $t->destination,
                'purpose' => $t->purpose,
                'departure_time' => $t->departure_time,
                'requestor' => $t->requestor?->name,
                'department' => $t->department?->name,
                'priority' => $t->priority,
                'passenger_count' => $t->passenger_count,
            ]),
        ]);
    }

    public function tripHistory(): Response
    {
        $user = Auth::user();
        $trips = TripRequest::with(['requestor', 'department'])
            ->where('department_id', $user->department_id)
            ->whereIn('status', ['COMPLETED', 'CANCELLED', 'REJECTED'])
            ->latest()
            ->get();

        return Inertia::render('Approver/TripHistory', [
            'trips' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'trip_number' => $t->trip_number,
                'destination' => $t->destination,
                'purpose' => $t->purpose,
                'departure_time' => $t->departure_time,
                'status' => $t->status,
                'priority' => $t->priority,
                'requestor' => $t->requestor?->name,
                'department' => $t->department?->name,
            ]),
        ]);
    }

    public function myTasks(): Response
    {
        $user = Auth::user();
        $driver = $user->driver;

        $trips = TripRequest::with(['requestor', 'vehicle'])
            ->when(
                $driver,
                // Termasuk trip sebagai driver pendukung loading (multi-driver).
                fn ($q) => $q->where(function ($sub) use ($driver) {
                    $sub->where('assigned_driver_id', $driver->id)
                        ->orWhereHas('assignments', fn ($a) => $a->where('driver_id', $driver->id));
                }),
                fn ($q) => $q->whereRaw('1 = 0') // tanpa record driver: tidak ada tugas yang bisa dilihat
            )
            ->whereIn('status', ['DRIVER_ASSIGNED', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'ON_TRIP'])
            ->latest()
            ->get();

        return Inertia::render('Driver/MyTasks', [
            'tasks' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'trip_number' => $t->trip_number,
                'destination' => $t->destination,
                'purpose' => $t->purpose,
                'departure_time' => $t->departure_time,
                'status' => $t->status,
                'priority' => $t->priority,
                'vehicle' => $t->vehicle ? "{$t->vehicle->plate_number} ({$t->vehicle->brand})" : null,
                'requestor' => $t->requestor?->name,
            ]),
        ]);
    }

    public function driverTripHistory(): Response
    {
        $user = Auth::user();
        $driver = $user->driver;

        $trips = TripRequest::with(['requestor', 'vehicle'])
            ->when(
                $driver,
                fn ($q) => $q->where(function ($sub) use ($driver) {
                    $sub->where('assigned_driver_id', $driver->id)
                        ->orWhereHas('assignments', fn ($a) => $a->where('driver_id', $driver->id));
                }),
                fn ($q) => $q->whereRaw('1 = 0') // tanpa record driver: tidak ada riwayat yang bisa dilihat
            )
            ->whereIn('status', ['COMPLETED', 'CANCELLED', 'DRIVER_REJECTED'])
            ->latest()
            ->get();

        return Inertia::render('Driver/TripHistory', [
            'trips' => $trips->map(fn ($t) => [
                'id' => $t->id,
                'trip_number' => $t->trip_number,
                'destination' => $t->destination,
                'purpose' => $t->purpose,
                'departure_time' => $t->departure_time,
                'status' => $t->status,
                'priority' => $t->priority,
                'vehicle' => $t->vehicle ? "{$t->vehicle->plate_number} ({$t->vehicle->brand})" : null,
            ]),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:20',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'phone' => $request->phone,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Bila akun yang baru dibuat ber-role DRIVER, arahkan langsung ke
        // halaman Driver Management agar bisa segera didaftarkan sebagai driver.
        $createdRole = Role::find($request->role_id);
        if ($createdRole && $createdRole->name === 'DRIVER') {
            return redirect()
                ->route('admin.drivers')
                ->with('success', "Akun driver \"{$request->name}\" berhasil dibuat. Silakan lengkapi data driver pada form Tambah Driver.");
        }

        return redirect()->route('admin.users')->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'phone' => $request->phone,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.users')->with('success', 'User berhasil diubah.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        // User yang masih menjadi requestor/approver trip tidak boleh dihapus:
        // FK trip_requests & trip_approvals menggunakan onDelete('restrict'),
        // sehingga penghapusan akan menabrak constraint integritas data.
        $tripCount = $user->tripRequests()->count();
        $approvalCount = $user->approvals()->count();

        if ($tripCount > 0 || $approvalCount > 0) {
            return redirect()->route('admin.users')->with(
                'error',
                "User tidak dapat dihapus karena masih memiliki {$tripCount} trip request dan {$approvalCount} approval. "
                    .'Tetapkan user sebagai nonaktif atau nonaktifkan aksesnya saja.'
            );
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User berhasil dihapus.');
    }

    public function storeDriver(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            // Nomor SIM harus unik di TABEL drivers. Driver lama yang sudah
            // dihapus (tanpa SoftDeletes, baris benar-benar hilang) tidak
            // jadi masalah, tetapi duplikasi input manual harus ditolak
            // dengan pesan yang jelas — bukan error 500 database.
            'license_number' => 'required|string|max:50|unique:drivers,license_number',
            'phone' => 'nullable|string|max:20',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'willing_to_load' => 'sometimes|boolean',
            'status' => 'in:STANDBY,OFF_DUTY,LEAVE,SICK,UNAVAILABLE',
        ], [
            'license_number.unique' => 'Nomor SIM sudah terdaftar pada driver lain. Gunakan nomor SIM yang berbeda.',
            'user_id.unique_driver' => 'Akun ini sudah terdaftar sebagai driver.',
        ]);

        // Akun user hanya boleh menjadi driver satu kali (satu record per user)
        if (Driver::where('user_id', $request->input('user_id'))->exists()) {
            return back()
                ->withInput()
                ->with('error', 'Akun user ini sudah terdaftar sebagai driver.');
        }

        Driver::create($request->only([
            'user_id', 'license_number', 'phone', 'vehicle_plate',
            'vehicle_type', 'willing_to_load', 'status',
        ]));

        return redirect()->route('admin.drivers')->with('success', 'Driver berhasil ditambahkan.');
    }

    public function updateDriver(Request $request, Driver $driver): RedirectResponse
    {
        // SuperAdmin HANYA dapat mengubah status operasional driver:
        // STANDBY (kembali bekerja), SICK (sakit), LEAVE (cuti).
        // Jenis status lain (ON_TRIP, ASSIGNED, dll) hanya diatur otomatis
        // oleh alur perjalanan, bukan manual.
        $allowedStatuses = [Driver::STATUS_STANDBY, Driver::STATUS_SICK, Driver::STATUS_LEAVE];

        $request->validate([
            'status' => 'required|in:'.implode(',', $allowedStatuses),
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status hanya boleh Standby, Sakit, atau Cuti.',
        ]);

        $newStatus = $request->input('status');

        // Driver tidak boleh dipasifkan (sakit / cuti) bila sedang
        // memiliki perjalanan aktif — harus diselesaikan/dibatalkan dulu.
        if ($newStatus !== $driver->status && in_array($newStatus, Driver::inactiveStatuses(), true)) {
            $hasActiveTrip = $driver->tripRequests()
                ->whereIn('status', ['DRIVER_ASSIGNED', 'DRIVER_ACCEPTED', 'READY_TO_DEPART', 'ON_TRIP'])
                ->exists();

            if ($hasActiveTrip) {
                return back()->with('error', 'Driver sedang memiliki perjalanan aktif. Selesaikan atau batalkan perjalanan terlebih dahulu sebelum mengubah status menjadi '.(Driver::statusLabels()[$newStatus] ?? $newStatus).'.');
            }
        }

        $oldStatus = $driver->status;

        $driver->update(['status' => $newStatus]);
        $driver->update(['last_status_change_at' => now()]);

        if ($newStatus !== $oldStatus) {
            $driver->statusLogs()->create([
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'UPDATE_DRIVER_STATUS',
                'module' => 'Driver',
                'record_type' => 'Driver',
                'record_id' => $driver->id,
                'old_value' => json_encode(['status' => $oldStatus]),
                'new_value' => json_encode(['status' => $newStatus]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return back()->with('success', 'Status driver berhasil diperbarui menjadi '.(Driver::statusLabels()[$newStatus] ?? $newStatus).'.');
    }

    public function destroyDriver(Driver $driver): RedirectResponse
    {
        $driver->delete();

        return redirect()->route('admin.drivers')->with('success', 'Driver berhasil dihapus.');
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand' => 'required|string|max:50',
            'model' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'passenger_capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'in:AVAILABLE,ASSIGNED,ON_TRIP,MAINTENANCE,OUT_OF_SERVICE',
            'primary_driver_id' => 'nullable|exists:drivers,id',
            'notes' => 'nullable|string',
        ]);

        Vehicle::create($request->only([
            'plate_number', 'brand', 'model', 'type', 'year',
            'passenger_capacity', 'status', 'primary_driver_id', 'notes',
        ]));

        return redirect()->route('admin.vehicles')->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,'.$vehicle->id,
            'brand' => 'required|string|max:50',
            'model' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'passenger_capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'in:AVAILABLE,ASSIGNED,ON_TRIP,MAINTENANCE,OUT_OF_SERVICE',
            'primary_driver_id' => 'nullable|exists:drivers,id',
            'notes' => 'nullable|string',
        ]);

        $vehicle->update($request->only([
            'plate_number', 'brand', 'model', 'type', 'year',
            'passenger_capacity', 'status', 'primary_driver_id', 'notes',
        ]));

        return redirect()->route('admin.vehicles')->with('success', 'Kendaraan berhasil diubah.');
    }

    public function destroyVehicle(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('admin.vehicles')->with('success', 'Kendaraan berhasil dihapus.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:departments,code',
            'name' => 'required|string|max:100',
            'head_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $department = Department::create($request->only(['code', 'name', 'head_name', 'notes']));

        // Sinkronkan anggota user departemen (opsional saat pembuatan).
        if ($request->filled('user_ids')) {
            $this->syncDepartmentUsers($department, $request->input('user_ids'));
        }

        return redirect()->route('admin.departments')->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:departments,code,'.$department->id,
            'name' => 'required|string|max:100',
            'head_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $department->update($request->only(['code', 'name', 'head_name', 'notes']));

        if ($request->has('user_ids')) {
            $this->syncDepartmentUsers($department, $request->input('user_ids', []));
        }

        return redirect()->route('admin.departments')->with('success', 'Departemen berhasil diubah.');
    }

    /**
     * Sinkronkan anggota user sebuah departemen: user yang dipilih
     * dipindahkan ke departemen ini, sisanya yang sebelumnya anggota
     * departemen ini dilepas (department_id = null).
     */
    private function syncDepartmentUsers(Department $department, array $userIds): void
    {
        $validIds = User::whereIn('id', $userIds)->pluck('id')->all();

        // Lepaskan user lama yang tidak lagi dipilih.
        $department->users()
            ->whereNotIn('users.id', $validIds)
            ->update(['department_id' => null]);

        // Pindahkan user terpilih ke departemen ini.
        User::whereIn('id', $validIds)->update(['department_id' => $department->id]);
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        // Departemen BOD tidak boleh dihapus: trip BOD mengandalkan
        // auto-approval berdasarkan departemen ini.
        if ($department->code === Department::CODE_BOD) {
            return redirect()->route('admin.departments')
                ->with('error', 'Departemen BOD (Board of Directors) tidak dapat dihapus karena digunakan untuk alur trip otomatis.');
        }

        // Departemen masih dipakai user — tidak boleh dihapus.
        if ($department->users()->exists()) {
            return redirect()->route('admin.departments')
                ->with('error', 'Departemen masih memiliki '.($department->users()->count()).' user. Pindahkan user terlebih dahulu sebelum menghapus.');
        }

        $department->delete();

        return redirect()->route('admin.departments')->with('success', 'Departemen berhasil dihapus.');
    }

    public function destroyBeritaAcara(BeritaAcara $beritaAcara): RedirectResponse
    {
        $beritaAcara->delete();

        return redirect()->route('admin.berita-acara')->with('success', 'Berita Acara berhasil dihapus.');
    }
}
