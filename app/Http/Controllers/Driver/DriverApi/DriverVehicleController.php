<?php

namespace App\Http\Controllers\Driver\DriverApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverVehicleController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('driver.vehicle');

        return response()->json([
            'vehicle' => $user->driver?->vehicle ? $user->driver->vehicle->only(['id', 'plate_number', 'model', 'type', 'status']) : null,
        ]);
    }

    public function updateStatus(Request $request, $vehicle): JsonResponse
    {
        $request->validate(['status' => 'required|in:STANDBY,SERVICE,MAINTENANCE,UNAVAILABLE']);

        return response()->json(['message' => 'OK']);
    }
}
