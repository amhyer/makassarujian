<?php

namespace App\Enums\Billing;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly  = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Bulanan',
            self::Yearly  => 'Tahunan',
        };
    }
}
