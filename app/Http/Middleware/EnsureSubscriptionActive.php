<?php

namespace App\Http\Middleware;

use App\Services\Billing\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = app('tenant_id');

        if (!$tenantId) {
            return $next($request);
        }

        $service = app(SubscriptionService::class);
        $sub = $service->getActive($tenantId);

        if (!$sub) {
            // Check if there is an expired or suspended sub to show more specific messages
            return redirect()->route('billing.invoices')
                ->with('error', 'Langganan tidak aktif atau masa percobaan telah berakhir. Silakan lakukan pembayaran untuk melanjutkan.');
        }

        return $next($request);
    }
}
