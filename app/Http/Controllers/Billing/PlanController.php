<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(protected PlanService $planService) {}

    public function index()
    {
        $plans = $this->planService->getActivePlans();
        return view('pages.billing.paket', compact('plans'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Plan::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:plans,slug',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'features' => 'nullable|array',
        ]);

        $this->planService->create($data);

        return back()->with('success', 'Paket berhasil dibuat.');
    }

    public function toggleActive(Plan $plan)
    {
        $this->authorize('update', $plan);
        $this->planService->toggleActive($plan);
        return back()->with('success', 'Status paket berhasil diubah.');
    }
}
