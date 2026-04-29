<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentService      $paymentService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Handle incoming payment gateway webhook.
     * Compatible with Midtrans / Xendit generic pattern.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // 1. VALIDASI SIGNATURE (wajib — cegah webhook palsu)
        $this->verifySignature($payload);

        // 2. Cari payment berdasarkan gateway reference
        $payment = Payment::where('gateway_ref', $payload['order_id'] ?? $payload['id'] ?? null)
            ->first();

        // Jika payment tidak ditemukan, cari berdasarkan ID internal
        if (! $payment && isset($payload['external_id'])) {
            $payment = Payment::find($payload['external_id']);
        }

        if (! $payment) {
            Log::warning('Webhook: Payment not found', $payload);
            return response()->json(['ok' => false, 'message' => 'Payment not found'], 404);
        }

        // 3. Handle berdasarkan status pembayaran
        $status = strtolower($payload['transaction_status'] ?? $payload['status'] ?? '');

        if (in_array($status, ['settlement', 'capture', 'paid', 'success'])) {
            $this->paymentService->markPaid($payment, $payload['order_id'] ?? $payload['id'] ?? '');

            // 4. AKTIVASI SUBSCRIPTION OTOMATIS
            try {
                if ($payment->invoice && $payment->invoice->subscription) {
                    $this->subscriptionService->activate($payment->invoice->subscription);
                    Log::info("Webhook: Subscription activated for tenant [{$payment->tenant->name}] after payment.");
                } else {
                    Log::warning("Webhook: No subscription found for payment [{$payment->id}].");
                }
            } catch (\Exception $e) {
                Log::warning("Webhook: Activation issue for payment [{$payment->id}]: {$e->getMessage()}");
            }
        }

        if (in_array($status, ['deny', 'cancel', 'expire', 'failure', 'failed'])) {
            $this->paymentService->markFailed($payment);
            Log::info("Webhook: Payment [{$payment->id}] marked as failed.");
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Validasi HMAC signature dari payment gateway.
     */
    private function verifySignature(array $payload): void
    {
        $signature = $payload['signature_key'] ?? $payload['signature'] ?? null;

        if (! $signature) {
            if (! app()->isProduction()) {
                return;
            }
            abort(403, 'Signature missing');
        }

        $orderId     = $payload['order_id']     ?? '';
        $statusCode  = $payload['status_code']  ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey   = config('services.midtrans.server_key', ''); // Fixed config path

        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Webhook: Invalid signature', ['payload' => $payload]);
            abort(403, 'Invalid webhook signature');
        }
    }
}
