<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
