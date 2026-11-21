<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'SUPERADMIN', 'display_name' => 'Super Admin', 'description' => 'Full system access'],
            ['name' => 'ADMIN', 'display_name' => 'Admin', 'description' => 'Manage master data, drivers, vehicles, departments, trips'],
            ['name' => 'DISPATCHER', 'display_name' => 'Dispatcher', 'description' => 'Dispatch drivers and vehicles'],
            ['name' => 'APPROVER', 'display_name' => 'Approver', 'description' => 'Approve or reject trip requests'],
            ['name' => 'EMPLOYEE', 'display_name' => 'Employee', 'description' => 'Create and track trip requests'],
            ['name' => 'DRIVER', 'display_name' => 'MMJ Driver', 'description' => 'Receive, complete, and track trips'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        $permissions = [
            ['name' => 'manage_users', 'display_name' => 'Manage Users', 'group' => 'users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'group' => 'roles'],
            ['name' => 'manage_departments', 'display_name' => 'Manage Departments', 'group' => 'departments'],
            ['name' => 'manage_drivers', 'display_name' => 'Manage Drivers', 'group' => 'drivers'],
            ['name' => 'manage_vehicles', 'display_name' => 'Manage Vehicles', 'group' => 'vehicles'],
            ['name' => 'manage_trips', 'display_name' => 'Manage Trips', 'group' => 'trips'],
            ['name' => 'dispatch_trips', 'display_name' => 'Dispatch Trips', 'group' => 'dispatch'],
            ['name' => 'approve_trips', 'display_name' => 'Approve Trips', 'group' => 'approval'],
            ['name' => 'create_trips', 'display_name' => 'Create Trips', 'group' => 'trips'],
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'group' => 'reports'],
            ['name' => 'view_audit_log', 'display_name' => 'View Audit Log', 'group' => 'audit'],
            ['name' => 'generate_ba', 'display_name' => 'Generate Berita Acara', 'group' => 'ba'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        $rolePermissions = [
            'SUPERADMIN' => [
                'manage_users', 'manage_roles', 'manage_departments', 'manage_drivers',
                'manage_vehicles', 'manage_trips', 'dispatch_trips', 'approve_trips',
                'create_trips', 'view_reports', 'view_audit_log', 'generate_ba',
            ],
            'ADMIN' => [
                'manage_departments', 'manage_drivers', 'manage_vehicles', 'manage_trips',
                'dispatch_trips', 'approve_trips', 'create_trips', 'view_reports',
                'view_audit_log', 'generate_ba',
            ],
            'DISPATCHER' => [
                'dispatch_trips', 'manage_drivers', 'view_reports',
            ],
            'APPROVER' => [
                'approve_trips', 'view_reports',
            ],
            'EMPLOYEE' => [
                'create_trips',
            ],
            'DRIVER' => [
                'create_trips',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $permissionIds = Permission::whereIn('name', $perms)->pluck('id')->toArray();
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
