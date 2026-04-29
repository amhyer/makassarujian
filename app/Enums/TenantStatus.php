<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Pending   = 'pending';
    case Trial     = 'trial';
    case Active    = 'active';
    case Expired   = 'expired';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Trial     => 'Trial',
            self::Active    => 'Aktif',
            self::Expired   => 'Expired',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending   => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
            self::Trial     => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::Active    => 'bg-green-50 text-green-700 ring-green-600/20',
            self::Expired   => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Suspended => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    }

    public function rowClass(): string
    {
        return match ($this) {
            self::Expired   => 'bg-red-50/50',
            self::Suspended => 'bg-slate-50',
            default         => 'bg-white',
        };
    }

    public function dotColor(): string
    {
        return match ($this) {
            self::Pending   => 'bg-yellow-400',
            self::Trial     => 'bg-blue-500',
            self::Active    => 'bg-green-500',
            self::Expired   => 'bg-red-500',
            self::Suspended => 'bg-slate-400',
        };
    }
}
