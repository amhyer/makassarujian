<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function index()
    {
        $query = Payment::with(['tenant', 'invoice', 'verifiedBy']);

        if (!Auth::user()->hasRole('Super Admin')) {
            $query->where('tenant_id', Auth::user()->tenant_id);
        }

        $payments = $query->latest()->paginate(10);
        return view('pages.billing.pembayaran', compact('payments'));
    }

    public function submitProof(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $request->validate([
            'proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $this->paymentService->submitProof($payment, $request->file('proof'));

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Mohon tunggu verifikasi admin.');
    }

    public function approve(Payment $payment)
    {
        $this->authorize('approve', $payment);
        $this->paymentService->approve($payment, Auth::user());
        return back()->with('success', 'Pembayaran disetujui.');
    }

    public function reject(Request $request, Payment $payment)
    {
        $this->authorize('reject', $payment);
        $request->validate(['reason' => 'required|string']);
        $this->paymentService->reject($payment, Auth::user(), $request->reason);
        return back()->with('success', 'Pembayaran ditolak.');
    }

    public function downloadProof(Payment $payment, \App\Models\PaymentProof $proof)
    {
        if (!Auth::user()->hasRole('Super Admin') && $payment->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        return Storage::disk('local')->download($proof->file_path, $proof->original_filename);
    }
}
