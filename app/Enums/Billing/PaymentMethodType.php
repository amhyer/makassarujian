<?php

namespace App\Enums\Billing;

enum PaymentMethodType: string
{
    case Qris         = 'qris';
    case BankTransfer = 'bank_transfer';
    case ShopeePay    = 'shopee_pay';

    public function label(): string
    {
        return match ($this) {
            self::Qris         => 'QRIS',
            self::BankTransfer => 'Transfer Bank',
            self::ShopeePay    => 'ShopeePay',
        };
    }
}
