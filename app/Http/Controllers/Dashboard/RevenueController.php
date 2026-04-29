<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\RevenueDashboardService;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, RevenueDashboardService $service)
    {
        // Security check
        if (auth()->user()->tenant_id !== null) {
            abort(403, 'Unauthorized. Revenue dashboard is only for Super Admin.');
        }

        $data = [
            'metrics' => $service->metrics(),
            'chart' => $service->revenueChart(),
            'subscriptions' => $service->subscriptionStats(),
        ];

        return $request->wantsJson()
            ? response()->json($data)
            : view('dashboard.revenue', $data);
    }
}
