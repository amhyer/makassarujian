<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'activities' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function impersonator()
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonatedUser()
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }
}
