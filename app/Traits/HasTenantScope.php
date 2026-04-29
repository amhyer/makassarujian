<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;

trait HasTenantScope
{
    /**
     * Boot the tenant scope for the model.
     */
    protected static function bootHasTenantScope()
    {
        static::addGlobalScope(new TenantScope());
    }
}
