<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_cycle',
        'features',
        'student_limit',
        'exam_limit',
        'has_proctoring_feature',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array', // Cast kolom JSON ke format array secara otomatis
        'price' => 'decimal:2',
        'student_limit' => 'integer',
        'exam_limit' => 'integer',
        'has_proctoring_feature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}