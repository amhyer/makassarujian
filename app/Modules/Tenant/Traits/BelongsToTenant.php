<?php

namespace App\Modules\Tenant\Traits;

use App\Modules\Tenant\TenantScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * Boot the trait to add global scope and creating listener.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model) {
            if (!$model->tenant_id && app()->has('tenant_id')) {
                $model->tenant_id = app('tenant_id');
            }
        });
    }

    /**
     * Relationship to the Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
