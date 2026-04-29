<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial     = 'trial';
    case Active    = 'active';
    case Expired   = 'expired';
    case Suspended = 'suspended';
    case Canceled  = 'canceled';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Trial     => in_array($to, [self::Active, self::Expired]),
            self::Active    => in_array($to, [self::Expired, self::Suspended, self::Canceled]),
            self::Expired   => in_array($to, [self::Active]),
            self::Suspended => in_array($to, [self::Active, self::Canceled]),
            self::Canceled  => false,
        };
    }
}
