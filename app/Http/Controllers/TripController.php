<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveTripRequest as ApproveTripRequestForm;
use App\Http\Requests\AssignDriverRequest;
use App\Http\Requests\CompleteTripRequest;
use App\Http\Requests\DriverResponseRequest as DriverResponseRequestForm;
use App\Http\Requests\RejectTripRequest as RejectTripRequestForm;
use App\Http\Requests\StartTripRequest;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\SubmitTripCompletionRequest;
use App\Models\Department;
use App\Models\Driver;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DriverAssigned;
use App\Services\TripService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TripService $tripService = new TripService,
    ) {}

    /**
     * Kolom & arah sorting yang diizinkan untuk halaman daftar trip.
     */
    protected const SORTABLE_COLUMNS = [
        'trip_number' => 'trip_number',
        'departure_time' => 'departure_time',
        'status' => 'status',
        'priority' => 'priority',
        'destination' => 'destination',
        'created_at' => 'created_at',
    ];

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $role = $user->role->name;

        $sortKey = $request->query('sort_by', 'created_at');
        $sortColumn = self::SORTABLE_COLUMNS[$sortKey] ?? 'created_at';
        $sortDirection = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = TripRequest::with(['requestor', 'driver.user', 'vehicle', 'department'])
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', $sortDirection);

        if ($role === 'EMPLOYEE') {
            $query->where('requestor_id', $user->id);
        } elseif ($role === 'DRIVER') {
            $driver = $user->driver;
            if ($driver) {
                // Termasuk trip sebagai driver pendukung loading.
                $query->where(function ($q) use ($driver) {
                    $q->where('assigned_driver_id', $driver->id)
                        ->orWhereHas('assignments', fn ($sub) => $sub->where('driver_id', $driver->id));
                });
            } else {
                $query->where('requestor_id', $user->id);
            }
        } elseif ($role === 'APPROVER') {
            $query->where('department_id', $user->department_id);
        }

        $trips = $query->paginate(15)->withQueryString();

        return Inertia::render('Trips/Index', [
            'trips' => [
                'data' => collect($trips->items())->map(fn ($trip) => [
                    'id' => $trip->id,
                    'trip_number' => $trip->trip_number,
                    'destination' => $trip->destination,
                    'purpose' => $trip->purpose,
                    // Datetime lokal "Y-m-d H:i:s" — tanpa konversi zona waktu.
                    'departure_time' => $trip->departure_time
                        ? Carbon::parse($trip->departure_time)->format('Y-m-d H:i:s')
                        : null,
                    'status' => $trip->status,
                    'priority' => $trip->priority,
                    'request_type' => $trip->request_type,
                    'requestor' => $trip->requestor ? [
                        'id' => $trip->requestor->id,
                        'name' => $trip->requestor->name,
                    ] : null,
                    'requestor_id' => $trip->requestor_id,
                ])->all(),
                'links' => $trips->appends(request()->query())->links()->render(),
                'current_page' => $trips->currentPage(),
                'per_page' => $trips->perPage(),
                'total' => $trips->total(),
            ],
        ]);
    }

    public function create(): Response
    {
        $user = Auth::user();

        $departments = Department::all();
        $employees = User::with('role')->get();
        $drivers = Driver::with(['user', 'allowedVehicles'])->get();
        $vehicles = Vehicle::whereIn('status', ['AVAILABLE', 'ASSIGNED'])->get();

        return Inertia::render('Trips/Create', [
            'departments' => $departments,
            'employees' => $employees,
            'drivers' => $drivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user->name,
                'status' => $d->status,
                'willing_to_load' => $d->willing_to_load,
                'vehicle_ids' => $d->allowedVehicles->pluck('id')->toArray(),
            ]),
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'brand' => $v->brand,
                'model' => $v->model,
                'type' => $v->type,
                'status' => $v->status,
                'passenger_capacity' => $v->passenger_capacity,
                'driver_ids' => $v->authorizedDrivers->pluck('id')->toArray(),
            ]),
        ]);
    }

    /**
     * Form edit permintaan perjalanan. Hanya trip berstatus DRAFT yang
     * dapat diedit.
     */
    public function edit(TripRequest $trip): Response
    {
        $this->authorize('update', $trip);

        if ($trip->status !== TripRequest::STATUS_DRAFT) {
            abort(403, 'Hanya permintaan berstatus Draft yang dapat diedit.');
        }

        $user = Auth::user();

        $drivers = Driver::with(['user', 'allowedVehicles'])->get();

        return Inertia::render('Trips/Edit', [
            'trip' => [
                'id' => $trip->id,
                'request_type' => $trip->request_type,
                'department_id' => $trip->department_id,
                'destination' => $trip->destination,
                'destination_address' => $trip->destination_address,
                'guest_pickup_location' => $trip->guest_pickup_location,
                'guest_institution' => $trip->guest_institution,
                'cargo_pickup_location' => $trip->cargo_pickup_location,
                'cargo_description' => $trip->cargo_description,
                'purpose' => $trip->purpose,
                'trip_date' => $trip->trip_date?->toDateString(),
                // Format datetime-local: Y-m-d\TH:i (waktu lokal, tanpa
                // konversi zona waktu).
                'departure_time' => $trip->departure_time
                    ? Carbon::parse($trip->departure_time)->format('Y-m-d\TH:i')
                    : '',
                'passenger_count' => $trip->passenger_count,
                'priority' => $trip->priority,
                'requested_priority' => $trip->requested_priority,
                'urgency_notes' => $trip->urgency_notes,
                'requires_loading_assistance' => $trip->requires_loading_assistance,
                'notes' => $trip->notes,
                'bod_link' => $trip->bod_link,
                'passengers' => $trip->passengers->map(fn ($p) => [
                    'name' => $p->name,
                    'phone' => $p->phone,
                    'department' => $p->department,
                    'position' => $p->position,
                ])->toArray(),
                'loading_support_driver_ids' => $trip->assignments()
                    ->where('role', TripAssignment::ROLE_LOADING_SUPPORT)
                    ->pluck('driver_id')
                    ->toArray(),
            ],
            'drivers' => $drivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user->name,
                'status' => $d->status,
                'willing_to_load' => $d->willing_to_load,
                'vehicle_ids' => $d->allowedVehicles->pluck('id')->toArray(),
            ]),
        ]);
    }

    /**
     * Simpan perubahan permintaan perjalanan (hanya saat DRAFT).
     */
    public function update(StoreTripRequest $request, TripRequest $trip): RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($trip->status !== TripRequest::STATUS_DRAFT) {
            return back()->with('error', 'Hanya permintaan berstatus Draft yang dapat diedit.');
        }

        try {
            $this->tripService->updateTrip($trip, $request->validated(), Auth::user());

            return redirect()->route('trips.show', $trip)
                ->with('success', 'Permintaan perjalanan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function store(StoreTripRequest $request): RedirectResponse
    {
        try {
            $this->authorize('create', TripRequest::class);
            $trip = $this->tripService->createTrip($request->validated(), Auth::user());

            // Trip BOD: driver dipilih langsung saat membuat permintaan.
            // Tidak perlu penugasan manual lagi — trip langsung dikirim
            // otomatis ke WhatsApp driver yang dipilih.
            if ($request->filled('driver_id')) {
                $meta = [];
                $this->tripService->assignDriverForBod($trip, Auth::user(), (int) $request->input('driver_id'), $meta);

                $redirect = redirect()->route('trips.show', $trip->id);

                if (! empty($meta['sent'])) {
                    return $redirect->with('success', 'Permintaan perjalanan BOD dibuat, driver ditugaskan, dan trip request dikirim ke WhatsApp driver.');
                }

                if (! empty($meta['wa_link'])) {
                    return $redirect
                        ->with('success', 'Permintaan perjalanan BOD dibuat dan driver ditugaskan. Kirim trip request ke driver via WhatsApp.')
                        ->with('whatsapp_link', $meta['wa_link']);
                }

                return $redirect->with('warning', 'Permintaan perjalanan BOD dibuat dan driver ditugaskan, tetapi nomor WhatsApp driver tidak ditemukan.');
            }

            return redirect()->route('trips.show', $trip)
                ->with('success', 'Permintaan perjalanan berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(TripRequest $trip): Response
    {
        $this->authorize('view', $trip);

        $user = Auth::user();
        $drivers = Driver::with(['user', 'allowedVehicles'])->get();
        $vehicles = Vehicle::whereIn('status', ['AVAILABLE', 'ASSIGNED'])->get();

        $trip->load([
            'requestor.role', 'department', 'driver.user', 'vehicle',
            'passengers', 'approvals.approver', 'assignment.driver.user',
            'assignment.vehicle', 'assignments.driver.user', 'statusLogs.changedBy', 'loadingRequest',
            'beritaAcara', 'attachments',
            'completionSubmittedBy', 'completionApprovedBy',
        ]);

        return Inertia::render('Trips/Show', [
            'trip' => [
                'id' => $trip->id,
                'trip_number' => $trip->trip_number,
                'destination' => $trip->destination,
                'bod_link' => $trip->bod_link,
                'is_bod' => $trip->isBodTrip(),
                'destination_address' => $trip->destination_address,
                'purpose' => $trip->purpose,
                'trip_date' => $trip->trip_date?->toDateString(),
                // Datetime lokal "Y-m-d H:i:s" — frontend memparse sebagai
                // waktu lokal (bukan UTC) sehingga jam tidak bergeser.
                'departure_time' => $trip->departure_time
                    ? Carbon::parse($trip->departure_time)->format('Y-m-d H:i:s')
                    : null,
                'actual_return_time' => $trip->actual_return_time
                    ? Carbon::parse($trip->actual_return_time)->format('Y-m-d H:i:s')
                    : null,
                'passenger_count' => $trip->passenger_count,
                'priority' => $trip->priority,
                'requested_priority' => $trip->requested_priority,
                'urgency_notes' => $trip->urgency_notes,
                'requires_loading_assistance' => $trip->requires_loading_assistance,
                'request_type' => $trip->request_type,
                'guest_pickup_location' => $trip->guest_pickup_location,
                'cargo_pickup_location' => $trip->cargo_pickup_location,
                'guest_institution' => $trip->guest_institution,
                'notes' => $trip->notes,
                'status' => $trip->status,
                'created_at' => $trip->created_at?->toIso8601String(),
                'approved_at' => $trip->approved_at?->toIso8601String(),
                'rejection_reason' => $trip->rejection_reason,
                'approval_notes' => $trip->approval_notes,
                'started_at' => $trip->started_at?->toIso8601String(),
                'completed_at' => $trip->completed_at?->toIso8601String(),
                'completion_notes' => $trip->completion_notes,
                'completion_submitted_at' => $trip->completion_submitted_at?->toIso8601String(),
                'completion_submitted_by' => $trip->completionSubmittedBy?->name,
                'completion_approved_at' => $trip->completion_approved_at?->toIso8601String(),
                'completion_approved_by' => $trip->completionApprovedBy?->name,
                'can_submit_completion' => $this->canSubmitCompletion($trip, $user),
                'assigned_driver_ids' => $trip->assignments->pluck('driver_id')->values()->all(),
                'assignments' => $trip->assignments->map(fn ($a) => [
                    'id' => $a->id,
                    'driver_id' => $a->driver_id,
                    'role' => $a->role,
                    'driver_response' => $a->driver_response,
                    'driver_name' => $a->driver?->user?->name,
                    'vehicle_id' => $a->vehicle_id,
                ])->values()->all(),
                'requestor' => $trip->requestor ? [
                    'id' => $trip->requestor->id,
                    'name' => $trip->requestor->name,
                    'email' => $trip->requestor->email,
                ] : null,
                'department' => $trip->department ? [
                    'id' => $trip->department->id,
                    'name' => $trip->department->name,
                    'code' => $trip->department->code,
                ] : null,
                'driver' => $trip->driver ? [
                    'id' => $trip->driver->id,
                    'name' => $trip->driver->user?->name,
                    'license_number' => $trip->driver->license_number,
                ] : null,
                'vehicle' => $trip->vehicle ? [
                    'id' => $trip->vehicle->id,
                    'plate_number' => $trip->vehicle->plate_number,
                    'brand' => $trip->vehicle->brand,
                    'model' => $trip->vehicle->model,
                ] : null,
                'passengers' => $trip->passengers->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'phone' => $p->phone,
                    'department' => $p->department,
                    'nik' => $p->nik,
                    'position' => $p->position,
                ])->toArray(),
                'approvals' => $trip->approvals->map(fn ($a) => [
                    'id' => $a->id,
                    'decision' => $a->decision,
                    'notes' => $a->notes,
                    'decided_at' => $a->decided_at?->toIso8601String(),
                    'approver' => $a->approver ? ['name' => $a->approver->name] : null,
                ])->toArray(),
                'assignment' => $trip->assignment ? [
                    'id' => $trip->assignment->id,
                    'driver_response' => $trip->assignment->driver_response,
                    'driver_rejection_reason' => $trip->assignment->driver_rejection_reason,
                    'assigned_at' => $trip->assignment->assigned_at?->toIso8601String(),
                    'driver_accepted_at' => $trip->assignment->driver_accepted_at?->toIso8601String(),
                    'driver' => $trip->assignment->driver ? [
                        'user' => $trip->assignment->driver->user ? ['name' => $trip->assignment->driver->user->name] : null,
                    ] : null,
                    'vehicle' => $trip->assignment->vehicle ? [
                        'plate_number' => $trip->assignment->vehicle->plate_number,
                        'brand' => $trip->assignment->vehicle->brand,
                        'model' => $trip->assignment->vehicle->model,
                    ] : null,
                ] : null,
                'statusLogs' => $trip->statusLogs->map(fn ($s) => [
                    'id' => $s->id,
                    'from_status' => $s->from_status,
                    'to_status' => $s->to_status,
                    'created_at' => $s->created_at?->toIso8601String(),
                    'changedBy' => $s->changedBy ? ['name' => $s->changedBy->name] : null,
                ])->toArray(),
                'loadingRequest' => $trip->loadingRequest ? [
                    'id' => $trip->loadingRequest->id,
                    'status' => $trip->loadingRequest->status,
                ] : null,
                'beritaAcara' => $trip->beritaAcara ? [
                    'id' => $trip->beritaAcara->id,
                    'ba_number' => $trip->beritaAcara->ba_number,
                ] : null,
            ],
            'drivers' => $drivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->user->name,
                'license_number' => $d->license_number,
                'phone' => $d->user?->phone ?: $d->phone,
                'vehicle_plate' => $d->vehicle_plate,
                'vehicle_type' => $d->vehicle_type,
                'status' => $d->status,
                'willing_to_load' => $d->willing_to_load,
                'allowed_vehicle_ids' => $d->allowedVehicles->pluck('id')->toArray(),
            ]),
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'brand' => $v->brand,
                'model' => $v->model,
                'type' => $v->type,
                'status' => $v->status,
                'passenger_capacity' => $v->passenger_capacity,
                'authorized_driver_ids' => $v->authorizedDrivers->pluck('id')->toArray(),
            ]),
        ]);
    }

    public function submit(TripRequest $trip): RedirectResponse
    {
        $this->authorize('update', $trip);

        try {
            $this->tripService->submitTrip($trip, Auth::user());

            return back()->with('success', 'Permintaan perjalanan berhasil disubmit.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(ApproveTripRequestForm $request, TripRequest $trip): RedirectResponse
    {
        $this->authorize('approve', $trip);

        try {
            $this->tripService->approveTrip($trip, Auth::user(), $request->notes, $request->priority);

            return back()->with('success', 'Permintaan perjalanan berhasil disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(RejectTripRequestForm $request, TripRequest $trip): RedirectResponse
    {
        $this->authorize('approve', $trip);

        try {
            $this->tripService->rejectTrip($trip, Auth::user(), $request->reason);

            return back()->with('success', 'Permintaan perjalanan berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function assignDriver(AssignDriverRequest $request, TripRequest $trip): RedirectResponse
    {
        $this->authorize('assignDriver', $trip);

        try {
            $additionalDriverIds = collect($request->input('additional_driver_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            $this->tripService->assignDriverAndVehicle(
                $trip,
                Auth::user(),
                (int) $request->driver_id,
                (int) $request->vehicle_id,
                $additionalDriverIds
            );

            // Siapkan tautan WhatsApp berisi detail tugas agar bisa langsung
            // dikirim ke driver (dipakai bila gateway otomatis belum aktif).
            $waLink = $this->buildAssignmentWhatsAppLink($trip->fresh());

            $message = empty($additionalDriverIds)
                ? 'Driver dan kendaraan berhasil ditugaskan. Notifikasi tugas telah dikirim ke driver.'
                : 'Driver utama, '.count($additionalDriverIds).' driver pendukung loading, dan kendaraan berhasil ditugaskan.';

            return back()
                ->with('success', $message)
                ->with('whatsapp_link', $waLink);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bangun tautan click-to-chat WhatsApp berisi detail penugasan.
     */
    protected function buildAssignmentWhatsAppLink(TripRequest $trip): ?string
    {
        $driver = $trip->driver?->load('user');
        $phone = $trip->driver?->user?->phone ?: $trip->driver?->phone;

        if (! $phone) {
            return null;
        }

        $whatsApp = app(WhatsAppService::class);
        $number = $whatsApp->normalizeNumber($phone);

        if (! $number) {
            return null;
        }

        $message = (new DriverAssigned($trip))->toWhatsApp($trip->driver?->user);

        return $whatsApp->buildWaLink($number, $message);
    }

    public function driverResponse(DriverResponseRequestForm $request, TripRequest $trip): RedirectResponse
    {
        try {
            $this->tripService->driverResponse($trip, Auth::user(), $request->action, $request->reason);
            $message = $request->action === 'accept'
                ? 'Anda berhasil menerima penugasan ini.'
                : 'Anda menolak penugasan ini.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function startTrip(StartTripRequest $request, TripRequest $trip): RedirectResponse
    {
        try {
            $this->tripService->startTrip($trip, Auth::user());

            return back()->with('success', 'Perjalanan berhasil dimulai.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pengakhiran perjalanan langsung oleh driver / pemohon (tanpa approval).
     */
    public function submitCompletion(SubmitTripCompletionRequest $request, TripRequest $trip): RedirectResponse
    {
        $this->authorize('submitCompletion', $trip);

        try {
            $this->tripService->submitCompletion($trip, Auth::user(), $request->notes);

            return back()->with('success', 'Perjalanan berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * @deprecated Gunakan submitCompletion() — penyelesaian kini butuh approval.
     *             Dipertahankan agar tautan lama tidak error.
     */
    public function completeTrip(CompleteTripRequest $request, TripRequest $trip): RedirectResponse
    {
        try {
            $this->tripService->submitCompletion($trip, Auth::user(), $request->notes);

            return back()->with('success', 'Perjalanan berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Apakah user saat ini boleh mengakhiri perjalanan.
     */
    protected function canSubmitCompletion(TripRequest $trip, ?User $user): bool
    {
        if (! $user || $trip->status !== TripRequest::STATUS_ON_TRIP) {
            return false;
        }

        $role = $user->role?->name;

        if ($role === 'DRIVER') {
            return $trip->hasDriver($user);
        }

        return (int) $trip->requestor_id === (int) $user->id;
    }

    public function destroy(TripRequest $trip): RedirectResponse
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return redirect()->route('trips.index')
            ->with('success', 'Permintaan perjalanan berhasil dihapus.');
    }
}
