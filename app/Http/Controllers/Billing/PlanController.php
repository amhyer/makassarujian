<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::latest()->get();
        return view('pages.billing.paket', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'student_limit' => 'nullable|integer|min:1',
            'exam_limit' => 'nullable|integer|min:1',
        ]);

        $data['slug'] = Str::slug($data['name']) . '-' . time();
        $data['has_proctoring_feature'] = $request->boolean('has_proctoring_feature');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['features'] = []; // Placeholder untuk array fitur tambahan kedepannya

        Plan::create($data);

        return back()->with('success', 'Paket berhasil dibuat.');
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'student_limit' => 'nullable|integer|min:1',
            'exam_limit' => 'nullable|integer|min:1',
        ]);

        $data['has_proctoring_feature'] = $request->boolean('has_proctoring_feature');
        $data['is_active'] = $request->boolean('is_active', true);

        // Update slug jika nama berubah drastis (opsional, tapi disarankan)
        if ($plan->name !== $data['name']) {
            $data['slug'] = Str::slug($data['name']) . '-' . time();
        }

        $plan->update($data);

        return back()->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Paket gagal dihapus karena ada institusi yang sedang berlangganan paket ini.');
        }

        $plan->delete();
        return back()->with('success', 'Paket berhasil dihapus.');
    }

    public function toggleActive(Plan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);
        return back()->with('success', 'Status paket berhasil diubah.');
    }
}
