<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Modules\Tenant\Traits\BelongsToTenant;

class ExamParticipant extends Model
{
    use HasUuids, BelongsToTenant;

    protected $guarded = ['id'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
