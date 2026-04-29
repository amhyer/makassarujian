<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_tenants',
            'manage_users',
            'manage_exams',
            'take_exams',
            'view_reports'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $schoolAdmin = Role::firstOrCreate(['name' => 'School Admin']);
        $schoolAdmin->givePermissionTo(['manage_users', 'manage_exams', 'view_reports']);

        $teacher = Role::firstOrCreate(['name' => 'Teacher']);
        $teacher->givePermissionTo(['manage_exams', 'view_reports']);

        $student = Role::firstOrCreate(['name' => 'Student']);
        $student->givePermissionTo(['take_exams']);
    }
}
