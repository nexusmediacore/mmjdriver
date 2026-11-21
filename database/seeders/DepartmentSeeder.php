<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'HRD', 'name' => 'Human Resources Development', 'head_name' => 'HR Manager'],
            ['code' => 'IT', 'name' => 'Information Technology', 'head_name' => 'IT Manager'],
            ['code' => 'FIN', 'name' => 'Finance', 'head_name' => 'Finance Manager'],
            ['code' => 'MKT', 'name' => 'Marketing', 'head_name' => 'Marketing Manager'],
            ['code' => 'OPS', 'name' => 'Operations', 'head_name' => 'Operations Manager'],
            ['code' => 'PUR', 'name' => 'Purchasing', 'head_name' => 'Purchasing Manager'],
            ['code' => 'BOD', 'name' => 'Board of Directors', 'head_name' => 'Board of Directors'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }
    }
}
