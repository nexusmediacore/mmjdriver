<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_superadmin_can_update_department(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $department = Department::where('code', 'HRD')->firstOrFail();

        $response = $this->actingAs($superadmin)->put(route('admin.departments.update', $department), [
            'code' => 'HRD',
            'name' => 'Human Resources Updated',
            'head_name' => 'HR Manager',
        ]);

        $response->assertRedirect(route('admin.departments'));
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Human Resources Updated',
        ]);
    }

    public function test_empty_department_can_be_deleted(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $department = Department::create([
            'code' => 'TMP',
            'name' => 'Temporary Department',
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.departments.destroy', $department));

        $response->assertRedirect(route('admin.departments'));
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_with_users_cannot_be_deleted(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $employee = User::where('email', 'employee1@example.com')->firstOrFail();

        $response = $this->actingAs($superadmin)->delete(route('admin.departments.destroy', $employee->department));

        $response->assertRedirect(route('admin.departments'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $employee->department_id]);
    }

    public function test_bod_department_cannot_be_deleted(): void
    {
        $superadmin = User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->firstOrFail();
        $bod = Department::where('code', 'BOD')->firstOrFail();

        $response = $this->actingAs($superadmin)->delete(route('admin.departments.destroy', $bod));

        $response->assertRedirect(route('admin.departments'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $bod->id]);
    }
}
