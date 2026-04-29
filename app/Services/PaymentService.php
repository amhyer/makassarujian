<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\PaymentProof;
use App\Models\User;
use App\Enums\Billing\PaymentStatus;
use App\Enums\Billing\PaymentMethodType;
use App\Enums\Billing\InvoiceStatus;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class PaymentService
{
    /**
     * Create manual payment record with double-payment prevention.
     */
    public function manual(Invoice $invoice, array $data): Payment
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw new Exception("Invoice already paid");
        }

        $existing = Payment::where('invoice_id', $invoice->id)
            ->whereIn('status', [
                PaymentStatus::Pending,
                PaymentStatus::Processing
            ])
            ->first();

        if ($existing) {
            throw new Exception("Payment already submitted and waiting verification");
        }

        return Payment::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total_amount,
            'payment_method_id' => $data['payment_method_id'],
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function submitProof(Payment $payment, UploadedFile $file, string $bankName, string $accountName): PaymentProof
    {
        return DB::transaction(function () use ($payment, $file, $bankName, $accountName) {
            // Lock payment for update
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === PaymentStatus::Success) {
                throw new Exception("Payment already verified as success");
            }

            $path = $file->store('proofs', 'local'); // local disk is private

            $proof = PaymentProof::create([
                'tenant_id' => $payment->tenant_id,
                'payment_id' => $payment->id,
                'file_path' => $path,
                'bank_name' => $bankName,
                'account_name' => $accountName,
                'status' => 'pending',
            ]);

            $payment->update(['status' => PaymentStatus::Processing]);

            return $proof;
        });
    }

    /**
     * Approve payment with idempotency and race-condition safety.
     */
    public function approve(Payment $payment, User $verifier): void
    {
        DB::transaction(function () use ($payment, $verifier) {
            // Lock for update to prevent race conditions
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === PaymentStatus::Success) {
                return; // Idempotent safe
            }

            // In manual flow, status should be 'processing' (after proof upload)
            // or 'pending' (if admin manually approves without proof).
            if (!in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing])) {
                throw new Exception("Invalid payment state for approval: {$payment->status->value}");
            }

            $payment->update([
                'status' => PaymentStatus::Success,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);

            $payment->proofs()->update(['status' => 'approved']);

            $invoice = Invoice::where('id', $payment->invoice_id)->lockForUpdate()->first();
            if ($invoice) {
                app(InvoiceService::class)->markPaid($invoice);
                
                if ($invoice->subscription) {
                    app(SubscriptionService::class)->activate($invoice->subscription);
                }
            }
        });
    }

    public function reject(Payment $payment, User $verifier, string $reason): void
    {
        $payment->update([
            'status' => PaymentStatus::Rejected,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'notes' => $reason,
        ]);

        $payment->proofs()->update(['status' => 'rejected']);
    }

    public function markPaid(Payment $payment, string $gatewayRef): void
    {
        DB::transaction(function () use ($payment, $gatewayRef) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === PaymentStatus::Success) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Success,
                'gateway_ref' => $gatewayRef,
                'verified_at' => now(),
            ]);

            $invoice = Invoice::where('id', $payment->invoice_id)->lockForUpdate()->first();
            if ($invoice) {
                app(InvoiceService::class)->markPaid($invoice);
                
                if ($invoice->subscription) {
                    app(SubscriptionService::class)->activate($invoice->subscription);
                }
            }
        });
    }

    public function markFailed(Payment $payment): void
    {
        $payment->update(['status' => PaymentStatus::Failed]);
    }
}
