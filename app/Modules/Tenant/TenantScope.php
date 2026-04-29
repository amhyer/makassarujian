<?php

namespace App\Modules\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Enforce tenant isolation if tenant_id is present in the app container
        if (app()->has('tenant_id')) {
            $tenantId = app('tenant_id');

            // Optional: Bypass scope for Super Admin (global visibility)
            if (Auth::check() && Auth::user()->hasRole('Super Admin')) {
                return;
            }

            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
