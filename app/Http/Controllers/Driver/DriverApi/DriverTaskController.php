<?php

namespace App\Http\Controllers\Driver\DriverApi;

use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Services\TripService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTaskController extends Controller
{
    public function __construct(
        protected TripService $tripService = new TripService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('driver');
        $driverId = $user->driver->id ?? null;

        if (! $driverId) {
            return response()->json(['tasks' => []]);
        }

        // Termasuk trip sebagai driver pendukung loading (multi-driver).
        $tasks = TripRequest::with(['vehicle', 'requestor.department'])
            ->where(function ($query) use ($driverId) {
                $query->where('assigned_driver_id', $driverId)
                    ->orWhereHas('assignments', fn ($q) => $q->where('driver_id', $driverId));
            })
            ->latest()
            ->get();

        return response()->json(['tasks' => $tasks->map(fn ($t) => $this->formatTask($t))]);
    }

    public function show(Request $request, TripRequest $trip): JsonResponse
    {
        $user = $request->user()->load('driver');

        if (! $trip->hasDriver($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['task' => $this->formatTask($trip->load(['vehicle', 'requestor.department']))]);
    }

    private function formatTask(TripRequest $t): array
    {
        return [
            'id' => $t->id,
            'trip_number' => $t->trip_number,
            'status' => $t->status,
            'purpose' => $t->purpose,
            'destination' => $t->destination,
            'departure_time' => $t->departure_time
                ? Carbon::parse($t->departure_time)->format('Y-m-d H:i:s')
                : null,
            'estimated_return_time' => $t->estimated_return_time
                ? Carbon::parse($t->estimated_return_time)->format('Y-m-d H:i:s')
                : null,
            'actual_departure_time' => $t->actual_departure_time?->toIso8601String(),
            'actual_return_time' => $t->actual_return_time?->toIso8601String(),
            'request_type' => $t->request_type,
            'guest_pickup_location' => $t->guest_pickup_location,
            'guest_institution' => $t->guest_institution,
            'cargo_pickup_location' => $t->cargo_pickup_location,
            'cargo_description' => $t->cargo_description,
            'vehicle' => $t->vehicle ? $t->vehicle->only(['id', 'plate_number', 'model', 'type']) : null,
            'bod_link' => $t->bod_link,
            'completion_notes' => $t->completion_notes,
            'completion_submitted_at' => $t->completion_submitted_at?->toIso8601String(),
            'can_submit_completion' => $t->status === TripRequest::STATUS_ON_TRIP,
        ];
    }

    public function accept(Request $request, TripRequest $trip): JsonResponse
    {
        $user = $request->user()->load('driver');
        $driverId = $user->driver->id ?? null;

        if ($trip->assigned_driver_id !== $driverId || $trip->status !== TripRequest::STATUS_DRIVER_ASSIGNED) {
            return response()->json(['message' => 'Tidak bisa menerima'], 422);
        }

        $trip->update([
            'status' => TripRequest::STATUS_DRIVER_ACCEPTED,
            'driver_response' => TripAssignment::RESPONSE_ACCEPTED,
            'driver_response_at' => now(),
        ]);

        return response()->json(['message' => 'Diterima']);
    }

    public function reject(Request $request, TripRequest $trip): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $user = $request->user()->load('driver');
        $driverId = $user->driver->id ?? null;

        if ($trip->assigned_driver_id !== $driverId || $trip->status !== TripRequest::STATUS_DRIVER_ASSIGNED) {
            return response()->json(['message' => 'Tidak bisa menolak'], 422);
        }

        $trip->update([
            'status' => TripRequest::STATUS_SUBMITTED,
            'assigned_driver_id' => null,
            'vehicle_id' => null,
            'driver_response' => TripAssignment::RESPONSE_REJECTED,
            'driver_response_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        return response()->json(['message' => 'Ditolak']);
    }

    public function startTrip(Request $request, TripRequest $trip): JsonResponse
    {
        $user = $request->user()->load('driver');
        $driverId = $user->driver->id ?? null;

        if ($trip->assigned_driver_id !== $driverId || ! in_array($trip->status, [TripRequest::STATUS_DRIVER_ACCEPTED, TripRequest::STATUS_READY_TO_DEPART])) {
            return response()->json(['message' => 'Tidak bisa memulai trip'], 422);
        }

        $trip->update([
            'status' => TripRequest::STATUS_ON_TRIP,
            'actual_departure_time' => now(),
        ]);

        return response()->json(['message' => 'Trip dimulai']);
    }

    /**
     * Pengajuan selesai perjalanan dari aplikasi mobile.
     * Status menjadi COMPLETION_SUBMITTED dan menunggu approval Admin/SuperAdmin.
     */
    public function completeTrip(Request $request, TripRequest $trip): JsonResponse
    {
        $request->validate(['notes' => 'nullable|string|max:2000']);

        $user = $request->user()->load('driver');

        if (! $trip->hasDriver($user)) {
            return response()->json(['message' => 'Tidak bisa menyelesaikan'], 422);
        }

        try {
            $trip = $this->tripService->submitCompletion($trip, $user, $request->input('notes'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan selesai perjalanan dikirim. Menunggu persetujuan Admin/SuperAdmin.',
            'status' => $trip->status,
        ]);
    }

    public function updateLocation(Request $request, TripRequest $trip): JsonResponse
    {
        return response()->json(['message' => 'OK']);
    }
}
