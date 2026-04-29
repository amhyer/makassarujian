<?php

namespace App\Enums\Billing;

enum InvoiceStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Failed    = 'failed';
    case Canceled  = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Menunggu Pembayaran',
            self::Paid      => 'Lunas',
            self::Failed    => 'Gagal',
            self::Canceled  => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending   => 'bg-yellow-100 text-yellow-800',
            self::Paid      => 'bg-green-100 text-green-800',
            self::Failed    => 'bg-red-100 text-red-800',
            self::Canceled  => 'bg-slate-100 text-slate-800',
        };
    }
}
