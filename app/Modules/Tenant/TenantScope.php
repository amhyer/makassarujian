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

            if (Auth::check() && Auth::user()->hasRole('Super Admin')) {
                return;
            }

            $builder->where(function ($query) use ($model, $tenantId) {
                $query->where($model->getTable() . '.tenant_id', $tenantId);

                // If the model is an Exam, allow viewing global templates
                if ($model instanceof \App\Models\Exam) {
                    $query->orWhere($model->getTable() . '.is_template', true);
                }
            });
        }
    }
}
