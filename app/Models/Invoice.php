<?php

namespace App\Models;

use App\Enums\Billing\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'status' => InvoiceStatus::class,
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)->latest('id')->first();
        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;
        
        return sprintf('INV-%s-%04d', $year, $sequence);
    }
}
