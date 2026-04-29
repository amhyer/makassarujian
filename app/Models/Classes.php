<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Modules\Tenant\Traits\BelongsToTenant;

class Classes extends Model
{
    use HasUuids, BelongsToTenant;

    protected $guarded = ['id'];
}
