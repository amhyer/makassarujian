<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TenantAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@makassarujian.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        $tenant = Tenant::firstOrCreate(
            ['domain' => 'smpn1.makassarujian.com'],
            [
                'name' => 'SMPN 1 Makassar',
                'type' => 'school',
            ]
        );

        $schoolAdmin = User::firstOrCreate(
            ['email' => 'admin@smpn1.com'],
            [
                'name' => 'Admin SMPN 1',
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (!$schoolAdmin->hasRole('School Admin')) {
            $schoolAdmin->assignRole('School Admin');
        }

        $teacher = User::firstOrCreate(
            ['email' => 'guru@smpn1.com'],
            [
                'name' => 'Guru Pengawas',
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (!$teacher->hasRole('Teacher')) {
            $teacher->assignRole('Teacher');
        }

        $student = User::firstOrCreate(
            ['email' => 'siswa@smpn1.com'],
            [
                'name' => 'Siswa Teladan',
                'tenant_id' => $tenant->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (!$student->hasRole('Student')) {
            $student->assignRole('Student');
        }
    }
}
