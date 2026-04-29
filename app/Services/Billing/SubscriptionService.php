<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Plan;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\Billing\SubscriptionExpired;
use Exception;

class SubscriptionService
{
    /**
     * Start trial
     */
    public function startTrial(Tenant $tenant, Plan $plan): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan) {

            $this->invalidate($tenant->id);

            // Sync Tenant status
            $tenant->update(['status' => TenantStatus::Trial]);

            return Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'status' => SubscriptionStatus::Trial,
                'trial_ends_at' => now()->addDays(7),
            ]);
        });
    }

    /**
     * Activate subscription (after payment)
     */
    public function activate(Subscription $sub): Subscription
    {
        return DB::transaction(function () use ($sub) {
            // Lock for update to prevent race conditions during activation
            $sub = Subscription::where('id', $sub->id)->lockForUpdate()->first();
            
            if ($sub->status === SubscriptionStatus::Active) {
                return $sub; // Idempotent
            }

            $this->transition($sub, SubscriptionStatus::Active);

            $sub->update([
                'started_at' => now(),
                'ended_at' => now()->addMonth(),
            ]);

            // Sync Tenant status
            $sub->tenant->update([
                'status' => TenantStatus::Active,
                'activated_at' => now()
            ]);

            $this->invalidate($sub->tenant_id);

            return $sub;
        });
    }

    /**
     * Expire subscription
     */
    public function expire(Subscription $sub): Subscription
    {
        DB::transaction(function () use ($sub) {
            $this->transition($sub, SubscriptionStatus::Expired);
            
            // Sync Tenant status
            $sub->tenant->update(['status' => TenantStatus::Expired]);
            
            $this->invalidate($sub->tenant_id);

            Event::dispatch(new SubscriptionExpired($sub));
        });

        return $sub;
    }

    /**
     * Suspend
     */
    public function suspend(Subscription $sub): Subscription
    {
        DB::transaction(function () use ($sub) {
            $this->transition($sub, SubscriptionStatus::Suspended);
            
            // Sync Tenant status
            $sub->tenant->update(['status' => TenantStatus::Suspended]);
            
            $this->invalidate($sub->tenant_id);
        });

        return $sub;
    }

    /**
     * Cancel
     */
    public function cancel(Subscription $sub): Subscription
    {
        $this->transition($sub, SubscriptionStatus::Canceled);

        $sub->update([
            'canceled_at' => now()
        ]);

        $this->invalidate($sub->tenant_id);

        return $sub;
    }

    /**
     * Renew (generate new cycle)
     */
    public function renew(Subscription $sub): Subscription
    {
        if ($sub->status !== SubscriptionStatus::Expired) {
            throw new Exception("Only expired subscription can be renewed");
        }

        return DB::transaction(function () use ($sub) {

            $this->transition($sub, SubscriptionStatus::Active);

            $sub->update([
                'started_at' => now(),
                'ended_at' => now()->addMonth(),
            ]);

            // Sync Tenant status
            $sub->tenant->update(['status' => TenantStatus::Active]);

            $this->invalidate($sub->tenant_id);

            return $sub;
        });
    }

    /**
     * Core transition guard (CRITICAL)
     */
    protected function transition(Subscription $sub, SubscriptionStatus $to): Subscription
    {
        if (!$sub->status->canTransitionTo($to)) {
            throw new Exception("Invalid transition: {$sub->status->value} → {$to->value}");
        }

        $sub->update([
            'status' => $to
        ]);

        return $sub;
    }

    /**
     * Get active subscription (cached)
     */
    public function getActive(string $tenantId): ?Subscription
    {
        return Cache::remember(
            "subscription:active:{$tenantId}",
            300,
            fn() => Subscription::where('tenant_id', $tenantId)
                ->where('status', SubscriptionStatus::Active)
                ->latest()
                ->first()
        );
    }

    /**
     * Auto expire (cron job)
     */
    public function autoExpire(): void
    {
        Subscription::where('status', SubscriptionStatus::Active)
            ->where('ended_at', '<', now())
            ->chunkById(100, function ($subs) {
                foreach ($subs as $sub) {
                    $this->expire($sub);
                }
            });
    }

    /**
     * Auto trial expire
     */
    public function autoExpireTrial(): void
    {
        Subscription::where('status', SubscriptionStatus::Trial)
            ->where('trial_ends_at', '<', now())
            ->chunkById(100, function ($subs) {
                foreach ($subs as $sub) {
                    $this->expire($sub);
                }
            });
    }

    /**
     * Cache invalidation
     */
    protected function invalidate(string $tenantId): void
    {
        Cache::forget("subscription:active:{$tenantId}");
    }
}
