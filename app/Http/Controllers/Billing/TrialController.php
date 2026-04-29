<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;

class TrialController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function index()
    {
        $this->authorize('viewAny', Tenant::class);
        $tenants = Tenant::where('status', \App\Enums\TenantStatus::Trial)->latest()->paginate(10);
        return view('pages.billing.trial', compact('tenants'));
    }

    public function extend(Tenant $tenant)
    {
        $this->authorize('update', $tenant);
        // Using getActive or find the trial subscription
        $sub = $tenant->subscriptions()->where('status', \App\Enums\SubscriptionStatus::Trial)->first();
        if ($sub) {
            $sub->update(['trial_ends_at' => $sub->trial_ends_at->addDays(7)]);
        }
        return back()->with('success', "Masa trial {$tenant->name} berhasil diperpanjang.");
    }

    public function convert(Tenant $tenant)
    {
        $this->authorize('update', $tenant);
        $sub = $tenant->subscriptions()->where('status', \App\Enums\SubscriptionStatus::Trial)->first();
        if ($sub) {
            $this->subscriptionService->activate($sub);
        }
        return back()->with('success', "{$tenant->name} berhasil diaktivasi manual.");
    }
}
