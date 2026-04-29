<?php

namespace App\Enums\Billing;

enum PaymentStatus: string
{
    case Pending             = 'pending';
    case PendingVerification = 'pending_verification';
    case Approved            = 'approved';
    case Rejected            = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending             => 'Menunggu Pembayaran',
            self::PendingVerification => 'Menunggu Verifikasi',
            self::Approved            => 'Disetujui',
            self::Rejected            => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending             => 'bg-yellow-100 text-yellow-800',
            self::PendingVerification => 'bg-blue-100 text-blue-800',
            self::Approved            => 'bg-green-100 text-green-800',
            self::Rejected            => 'bg-red-100 text-red-800',
        };
    }
}
