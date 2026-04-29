<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Plan;
use App\Enums\Billing\InvoiceStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Events\Billing\InvoiceCreated;

class InvoiceService
{
    /**
     * Generate invoice for a specific subscription.
     */
    public function generateForSubscription(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription) {
            // Lock subscription to prevent double invoice generation
            $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->first();

            if ($subscription->invoice_id && $subscription->invoice->status !== InvoiceStatus::Canceled) {
                return $subscription->invoice;
            }

            $invoice = Invoice::create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => Invoice::generateNumber(),
                'amount' => $subscription->plan->price,
                'total_amount' => $subscription->plan->price,
                'due_date' => now()->addDays(7),
                'status' => InvoiceStatus::Pending,
            ]);

            $subscription->update(['invoice_id' => $invoice->id]);

            Event::dispatch(new InvoiceCreated($invoice));

            return $invoice;
        });
    }

    /**
     * Helper for scheduler to generate renewal invoices.
     */
    public function generateForRenewal(Tenant $tenant, Plan $plan): Invoice
    {
        return DB::transaction(function () use ($tenant, $plan) {
            // Check for existing pending invoice for this tenant/plan to avoid duplicates
            $existing = Invoice::where('tenant_id', $tenant->id)
                ->where('status', InvoiceStatus::Pending)
                ->latest()
                ->first();

            if ($existing) {
                return $existing;
            }

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'invoice_number' => Invoice::generateNumber(),
                'amount' => $plan->price,
                'total_amount' => $plan->price,
                'due_date' => now()->addDays(3),
                'status' => InvoiceStatus::Pending,
            ]);

            \Illuminate\Support\Facades\Event::dispatch(new \App\Events\Billing\InvoiceCreated($invoice));

            return $invoice;
        });
    }

    public function markPaid(Invoice $invoice): void
    {
        $invoice->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function cancel(Invoice $invoice): void
    {
        $invoice->update(['status' => InvoiceStatus::Canceled]);
    }
}
