<?php

namespace App\Models;

use App\Enums\Billing\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'type' => PaymentMethodType::class,
        'is_active' => 'boolean',
    ];
}
