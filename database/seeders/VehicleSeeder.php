<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $driver1 = Driver::where('license_number', 'LIC-001')->first();
        $driver3 = Driver::where('license_number', 'LIC-003')->first();

        $vehicles = [
            [
                'plate_number' => 'B 1001 AA', 'brand' => 'Toyota', 'model' => 'Innova',
                'type' => 'MPV', 'year' => 2020, 'passenger_capacity' => 8,
                'status' => 'AVAILABLE', 'primary_driver_id' => $driver1?->id, 'notes' => 'Kendaraan utama',
            ],
            [
                'plate_number' => 'B 1002 BB', 'brand' => 'Honda', 'model' => 'Mobilio',
                'type' => 'MPV', 'year' => 2019, 'passenger_capacity' => 7,
                'status' => 'AVAILABLE', 'notes' => 'Cadangan',
            ],
            [
                'plate_number' => 'B 1003 CC', 'brand' => 'Suzuki', 'model' => 'Ertiga',
                'type' => 'MPV', 'year' => 2021, 'passenger_capacity' => 7,
                'status' => 'ON_TRIP', 'primary_driver_id' => $driver3?->id, 'notes' => 'Sedang dipakai Driver 3',
            ],
            [
                'plate_number' => 'B 1004 DD', 'brand' => 'Toyota', 'model' => 'Avanza',
                'type' => 'MPV', 'year' => 2018, 'passenger_capacity' => 7,
                'status' => 'MAINTENANCE', 'notes' => 'Maintenance rutin',
            ],
            [
                'plate_number' => 'B 1005 EE', 'brand' => 'Mitsubishi', 'model' => 'Xpander',
                'type' => 'MPV', 'year' => 2022, 'passenger_capacity' => 8,
                'status' => 'AVAILABLE', 'notes' => 'Cadangan',
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::firstOrCreate(['plate_number' => $vehicle['plate_number']], $vehicle);
        }

        // Populate driver_vehicle compatibility
        $driver1 = Driver::where('license_number', 'LIC-001')->first();
        $driver2 = Driver::where('license_number', 'LIC-002')->first();
        $driver3 = Driver::where('license_number', 'LIC-003')->first();
        $driver4 = Driver::where('license_number', 'LIC-004')->first();
        $driver5 = Driver::where('license_number', 'LIC-005')->first();

        $vehicle1 = Vehicle::where('plate_number', 'B 1001 AA')->first();
        $vehicle2 = Vehicle::where('plate_number', 'B 1002 BB')->first();
        $vehicle3 = Vehicle::where('plate_number', 'B 1003 CC')->first();
        $vehicle4 = Vehicle::where('plate_number', 'B 1004 DD')->first();
        $vehicle5 = Vehicle::where('plate_number', 'B 1005 EE')->first();

        // Driver 1 → Vehicle A, B, C
        if ($driver1 && $vehicle1) {
            $driver1->allowedVehicles()->syncWithoutDetaching([$vehicle1->id]);
        }
        if ($driver1 && $vehicle2) {
            $driver1->allowedVehicles()->syncWithoutDetaching([$vehicle2->id]);
        }
        if ($driver1 && $vehicle3) {
            $driver1->allowedVehicles()->syncWithoutDetaching([$vehicle3->id]);
        }

        // Driver 2 → Vehicle A, C, D
        if ($driver2 && $vehicle1) {
            $driver2->allowedVehicles()->syncWithoutDetaching([$vehicle1->id]);
        }
        if ($driver2 && $vehicle3) {
            $driver2->allowedVehicles()->syncWithoutDetaching([$vehicle3->id]);
        }
        if ($driver2 && $vehicle4) {
            $driver2->allowedVehicles()->syncWithoutDetaching([$vehicle4->id]);
        }

        // Driver 3 → Vehicle B, D
        if ($driver3 && $vehicle2) {
            $driver3->allowedVehicles()->syncWithoutDetaching([$vehicle2->id]);
        }
        if ($driver3 && $vehicle4) {
            $driver3->allowedVehicles()->syncWithoutDetaching([$vehicle4->id]);
        }

        // Driver 4 → Vehicle A, D, E
        if ($driver4 && $vehicle1) {
            $driver4->allowedVehicles()->syncWithoutDetaching([$vehicle1->id]);
        }
        if ($driver4 && $vehicle4) {
            $driver4->allowedVehicles()->syncWithoutDetaching([$vehicle4->id]);
        }
        if ($driver4 && $vehicle5) {
            $driver4->allowedVehicles()->syncWithoutDetaching([$vehicle5->id]);
        }

        // Driver 5 → Vehicle C, E
        if ($driver5 && $vehicle3) {
            $driver5->allowedVehicles()->syncWithoutDetaching([$vehicle3->id]);
        }
        if ($driver5 && $vehicle5) {
            $driver5->allowedVehicles()->syncWithoutDetaching([$vehicle5->id]);
        }
    }
}
