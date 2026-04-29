<?php

namespace App\Services;

use App\Models\Tenant;
use App\Enums\TenantStatus;

class ActionResolver
{
    /**
     * Kembalikan daftar aksi yang tersedia berdasarkan status tenant saat ini.
     * Ini adalah satu-satunya tempat logika "aksi apa yang boleh dilakukan".
     */
    public function getActions(Tenant $tenant): array
    {
        return match ($tenant->status) {
            TenantStatus::Pending => [
                ['key' => 'start-trial', 'label' => 'Mulai Trial',  'style' => 'blue'],
                ['key' => 'activate',    'label' => 'Aktivasi Langsung', 'style' => 'green'],
            ],
            TenantStatus::Trial => [
                ['key' => 'activate',       'label' => 'Aktivasi (Sudah Bayar)', 'style' => 'green'],
                ['key' => 'extend-trial',   'label' => 'Perpanjang Trial',       'style' => 'blue'],
                ['key' => 'expire',         'label' => 'Paksa Expire',           'style' => 'red'],
            ],
            TenantStatus::Active => [
                ['key' => 'suspend', 'label' => 'Suspend', 'style' => 'yellow'],
                ['key' => 'expire',  'label' => 'Paksa Expire', 'style' => 'red'],
            ],
            TenantStatus::Expired => [
                ['key' => 'activate',       'label' => 'Reaktivasi',      'style' => 'green'],
                ['key' => 'send-reminder',  'label' => 'Kirim Reminder',  'style' => 'blue'],
            ],
            TenantStatus::Suspended => [
                ['key' => 'activate', 'label' => 'Aktifkan Ulang', 'style' => 'green'],
            ],
            default => [],
        };
    }

    /**
     * Resolve style class Tailwind untuk tombol dropdown action.
     */
    public function resolveButtonClass(string $style): string
    {
        return match ($style) {
            'green'  => 'text-green-700 hover:bg-green-50',
            'blue'   => 'text-blue-700 hover:bg-blue-50',
            'yellow' => 'text-yellow-700 hover:bg-yellow-50',
            'red'    => 'text-red-700 hover:bg-red-50',
            default  => 'text-slate-700 hover:bg-slate-50',
        };
    }
}
