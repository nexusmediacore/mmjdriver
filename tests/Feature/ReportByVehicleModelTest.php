<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportByVehicleModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_by_vehicle_shows_model_not_plate(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();

        $response = $this->actingAs($superadmin)->get('/reports');
        $response->assertStatus(200);

        $response->assertInertia(function ($page) {
            $page = $page->toArray();
            $byVehicle = $page['props']['byVehicle'] ?? [];

            dump('Jumlah kendaraan di laporan : '.count($byVehicle));

            foreach ($byVehicle as $v) {
                dump("  label='{$v['name']}' | plate={$v['plate_number']} | count={$v['count']}");
            }

            // Setiap entri harus punya 'name' berupa brand + model
            foreach ($byVehicle as $v) {
                $this->assertArrayHasKey('name', $v);
                $this->assertArrayHasKey('plate_number', $v);
                $this->assertNotSame($v['name'], $v['plate_number']);
            }

            return true;
        });
    }
}
