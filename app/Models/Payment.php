<?php

namespace App\Models;

use App\Enums\Billing\PaymentStatus;
use App\Enums\Billing\PaymentMethodType;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'integer',
        'status' => PaymentStatus::class,
        'method' => PaymentMethodType::class,
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
