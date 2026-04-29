<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index()
    {
        $query = Invoice::with(['tenant', 'subscription.plan']);

        if (!Auth::user()->hasRole('Super Admin')) {
            $query->where('tenant_id', Auth::user()->tenant_id);
        }

        $invoices = $query->latest()->paginate(10);
        return view('pages.billing.tagihan', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            $this->authorize('view', $invoice);
        }
        
        return view('pages.billing.tagihan_detail', compact('invoice'));
    }
}
