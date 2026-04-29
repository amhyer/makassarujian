<?php

namespace App\Services\Dashboard;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Enums\SubscriptionStatus;
use App\Enums\Billing\InvoiceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RevenueDashboardService
{
    public function metrics(): array
    {
        return Cache::remember('dashboard:revenue:metrics', 300, function () {

            $mrr = Invoice::where('status', InvoiceStatus::Paid)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('total_amount');

            $arr = $mrr * 12;

            $totalRevenue = Invoice::where('status', InvoiceStatus::Paid)
                ->sum('total_amount');

            $pendingRevenue = Invoice::where('status', InvoiceStatus::Pending)
                ->sum('total_amount');

            $active = Subscription::where('status', SubscriptionStatus::Active)->count();
            $expired = Subscription::where('status', SubscriptionStatus::Expired)->count();

            $churnRate = ($active + $expired) > 0
                ? ($expired / ($active + $expired)) * 100
                : 0;

            return [
                'mrr' => $mrr,
                'arr' => $arr,
                'total_revenue' => $totalRevenue,
                'pending_revenue' => $pendingRevenue,
                'active_subscriptions' => $active,
                'churn_rate' => round($churnRate, 2),
            ];
        });
    }

    public function revenueChart()
    {
        return Cache::remember('dashboard:revenue:chart', 300, function () {

            return Invoice::selectRaw("
                DATE(paid_at) as date,
                SUM(total_amount) as total
            ")
                ->where('status', InvoiceStatus::Paid)
                ->where('paid_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
    }

    public function subscriptionStats()
    {
        return Cache::remember('dashboard:subscription:stats', 300, function () {
            // Using pluck to get associative array status => total
            $stats = Subscription::selectRaw("
                status,
                COUNT(*) as total
            ")
                ->groupBy('status')
                ->get();
            
            $result = [];
            foreach ($stats as $stat) {
                // Ensure status is handled correctly as Enum or string
                $key = is_object($stat->status) ? $stat->status->value : $stat->status;
                $result[$key] = $stat->total;
            }
            
            return $result;
        });
    }

    /**
     * Cache invalidation
     */
    public function invalidate(): void
    {
        Cache::forget('dashboard:revenue:metrics');
        Cache::forget('dashboard:revenue:chart');
        Cache::forget('dashboard:subscription:stats');
    }
}
