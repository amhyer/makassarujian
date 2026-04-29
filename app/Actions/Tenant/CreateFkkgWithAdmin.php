<?php

namespace App\Actions\Tenant;

use App\Events\Tenant\TenantCreated;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\TenantStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateFkkgWithAdmin
{
    /**
     * Buat Tenant FKGG beserta akun admin-nya secara atomic.
     *
     * @return array{tenant: Tenant, admin: User, plain_password: string}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plainPassword = Str::random(12);

            // 1. Buat Tenant FKKG
            $tenant = Tenant::create([
                'name'   => $data['name'],
                'domain' => $data['domain'] ?? null,
                'type'   => 'fkkg',
                'status' => TenantStatus::Pending,
            ]);

            // 2. Buat Admin User
            $admin = User::create([
                'name'            => 'Admin ' . $data['name'],
                'email'           => $data['email_admin'],
                'password'        => Hash::make($plainPassword),
                'tenant_id'       => $tenant->id,
                'is_tenant_admin' => true,
                'status'          => 'active',
            ]);

            // 3. Assign role — FKKG Admin (fallback ke School Admin jika belum ada)
            $roleName = \Spatie\Permission\Models\Role::where('name', 'FKKG Admin')->exists()
                ? 'FKKG Admin'
                : 'School Admin';
            $admin->assignRole($roleName);

            // 4. Dispatch event (reuse TenantCreated)
            TenantCreated::dispatch($tenant, $admin);

            // 5. Audit log
            AuditLog::record('fkkg.created', [
                'tenant_name' => $tenant->name,
                'admin_email' => $admin->email,
            ], $tenant->id);

            return [
                'tenant'         => $tenant,
                'admin'          => $admin,
                'plain_password' => $plainPassword,
            ];
        });
    }
}
