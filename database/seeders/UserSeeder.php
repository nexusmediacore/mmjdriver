<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Driver;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminRole = Role::where('name', 'SUPERADMIN')->first();
        $adminRole = Role::where('name', 'ADMIN')->first();
        $dispatcherRole = Role::where('name', 'DISPATCHER')->first();
        $approverRole = Role::where('name', 'APPROVER')->first();
        $employeeRole = Role::where('name', 'EMPLOYEE')->first();
        $driverRole = Role::where('name', 'DRIVER')->first();

        $itDept = Department::where('code', 'IT')->first();
        $opsDept = Department::where('code', 'OPS')->first();
        $hrdDept = Department::where('code', 'HRD')->first();
        $finDept = Department::where('code', 'FIN')->first();
        $mktDept = Department::where('code', 'MKT')->first();

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('Pass@123'),
                'role_id' => $superadminRole->id,
                'department_id' => null,
                'email_verified_at' => now(),
                'phone' => '081100000001',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('Pass@123'),
                'role_id' => $adminRole->id,
                'department_id' => $itDept->id,
                'email_verified_at' => now(),
                'phone' => '081100000002',
            ],
            [
                'name' => 'Dispatcher 1',
                'email' => 'dispatcher1@example.com',
                'password' => Hash::make('Pass@123'),
                'role_id' => $dispatcherRole->id,
                'department_id' => $opsDept->id,
                'email_verified_at' => now(),
                'phone' => '081100000003',
            ],
            [
                'name' => 'Dispatcher 2',
                'email' => 'dispatcher2@example.com',
                'password' => Hash::make('Pass@123'),
                'role_id' => $dispatcherRole->id,
                'department_id' => $opsDept->id,
                'email_verified_at' => now(),
                'phone' => '081100000004',
            ],
            [
                'name' => 'Approver 1',
                'email' => 'approver1@example.com',
                'password' => Hash::make('Pass@123'),
                'role_id' => $approverRole->id,
                'department_id' => $finDept->id,
                'email_verified_at' => now(),
                'phone' => '081100000005',
            ],
            [
                'name' => 'Approver 2',
                'email' => 'approver2@example.com',
                'password' => Hash::make('Pass@123'),
                'role_id' => $approverRole->id,
                'department_id' => $mktDept->id,
                'email_verified_at' => now(),
                'phone' => '081100000006',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(['email' => $userData['email']], $userData);
        }

        // Create 10 Employees
        for ($i = 1; $i <= 10; $i++) {
            $dept = [$itDept, $opsDept, $finDept, $mktDept, $hrdDept][$i % 5];
            User::updateOrCreate(
                ['email' => 'employee'.$i.'@example.com'],
                [
                    'name' => 'Employee '.$i,
                    'password' => Hash::make('Pass@123'),
                    'role_id' => $employeeRole->id,
                    'department_id' => $dept->id,
                    'email_verified_at' => now(),
                    'phone' => '0812000000'.str_pad($i, 2, '0', STR_PAD_LEFT),
                ]
            );
        }

        // Create 5 Drivers
        $driverUsers = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::updateOrCreate(
                ['email' => 'driver'.$i.'@example.com'],
                [
                    'name' => 'Driver '.$i,
                    'password' => Hash::make('Pass@123'),
                    'role_id' => $driverRole->id,
                    'department_id' => $opsDept->id,
                    'email_verified_at' => now(),
                    'phone' => '0813000000'.str_pad($i, 2, '0', STR_PAD_LEFT),
                ]
            );
            $driverUsers[] = $user;
        }

        // Create driver profiles
        $drivers = [
            ['license_number' => 'LIC-001', 'phone' => '081300000001', 'willing_to_load' => true,  'status' => 'STANDBY'],
            ['license_number' => 'LIC-002', 'phone' => '081300000002', 'willing_to_load' => false, 'status' => 'OFF_DUTY'],
            ['license_number' => 'LIC-003', 'phone' => '081300000003', 'willing_to_load' => true,  'status' => 'STANDBY'],
            ['license_number' => 'LIC-004', 'phone' => '081300000004', 'willing_to_load' => true,  'status' => 'ON_TRIP'],
            ['license_number' => 'LIC-005', 'phone' => '081300000005', 'willing_to_load' => false, 'status' => 'STANDBY'],
        ];

        foreach ($driverUsers as $idx => $user) {
            $d = $drivers[$idx];
            Driver::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'license_number' => $d['license_number'],
                    'phone' => $d['phone'],
                    'vehicle_plate' => 'B '.(1000 + $idx),
                    'vehicle_type' => 'SUV',
                    'status' => $d['status'],
                    'willing_to_load' => $d['willing_to_load'],
                ]
            );
        }
    }
}
