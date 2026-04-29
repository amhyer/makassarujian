<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlanService
{
    public function getActivePlans(): Collection
    {
        return Cache::remember('billing:plans:active', 3600, function () {
            return Plan::where('is_active', true)->orderBy('sort_order')->get();
        });
    }

    public function create(array $data): Plan
    {
        $plan = Plan::create($data);
        $this->clearCache();
        return $plan;
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);
        $this->clearCache();
        return $plan;
    }

    public function toggleActive(Plan $plan): Plan
    {
        $plan->update(['is_active' => !$plan->is_active]);
        $this->clearCache();
        return $plan;
    }

    public function delete(Plan $plan): void
    {
        $plan->delete();
        $this->clearCache();
    }

    protected function clearCache(): void
    {
        Cache::forget('billing:plans:active');
    }
}
