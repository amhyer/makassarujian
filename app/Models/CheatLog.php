<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheatLog extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'meta' => 'array',
        'timestamp' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }
}
