<?php

namespace App\Models;

use App\Enums\Billing\BillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'billing_cycle' => BillingCycle::class,
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format($this->price, 0, ',', '.')
        );
    }
}
