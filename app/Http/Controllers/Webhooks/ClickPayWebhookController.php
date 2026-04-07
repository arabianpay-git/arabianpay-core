<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SchedulePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-5] ClickPay payment callback/webhook handler.
 *
 * Receives asynchronous payment status notifications from ClickPay.
 * - Verifies request signature
 * - Updates payment status idempotently
 * - Logs all callback events
 */
class ClickPayWebhookController extends Controller
{
    /**
     * Handle incoming ClickPay callback.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Step 1: Verify signature
        if (! $this->verifySignature($request)) {
            Log::warning('[CLICKPAY-WEBHOOK] Invalid signature', [
                'ip' => $request->ip(),
                'payload_keys' => array_keys($payload),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Step 2: Extract key fields
        $tranRef = $payload['tran_ref'] ?? null;
        $cartId = $payload['cart_id'] ?? null;
        $responseStatus = $payload['payment_result']['response_status'] ?? null;
        $responseMessage = $payload['payment_result']['response_message'] ?? null;
        $tranType = $payload['tran_type'] ?? null;

        Log::info('[CLICKPAY-WEBHOOK] Received callback', [
            'tran_ref' => $tranRef,
            'cart_id' => $cartId,
            'response_status' => $responseStatus,
            'tran_type' => $tranType,
        ]);

        if (! $tranRef || ! $responseStatus) {
            Log::warning('[CLICKPAY-WEBHOOK] Missing required fields', [
                'tran_ref' => $tranRef,
                'response_status' => $responseStatus,
            ]);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        // Step 3: Find the payment record
        $payment = Payment::where('txn_code', $tranRef)->first();

        if (! $payment) {
            Log::warning('[CLICKPAY-WEBHOOK] Payment not found', [
                'tran_ref' => $tranRef,
            ]);
            return response()->json(['status' => 'not_found'], 404);
        }

        // Step 4: Idempotent status update
        $newStatus = match ($responseStatus) {
            'A' => 'paid',       // Approved
            'D' => 'failed',     // Declined
            'E' => 'failed',     // Error
            'V' => 'refunded',   // Voided
            'R' => 'refunded',   // Refunded
            default => null,
        };

        if (! $newStatus) {
            Log::info('[CLICKPAY-WEBHOOK] Unknown response status, ignoring', [
                'response_status' => $responseStatus,
            ]);
            return response()->json(['status' => 'ignored']);
        }

        // Idempotency: don't re-process if already in terminal state
        if ($payment->payment_status === $newStatus) {
            Log::info('[CLICKPAY-WEBHOOK] Payment already in target status, skipping', [
                'payment_id' => $payment->id,
                'status' => $newStatus,
            ]);
            return response()->json(['status' => 'already_processed']);
        }

        // Don't downgrade from paid to failed
        if ($payment->payment_status === 'paid' && $newStatus === 'failed') {
            Log::warning('[CLICKPAY-WEBHOOK] Attempted to downgrade paid payment to failed', [
                'payment_id' => $payment->id,
            ]);
            return response()->json(['status' => 'conflict'], 409);
        }

        $payment->payment_status = $newStatus;
        $existingDetails = is_array($payment->payment_details)
            ? $payment->payment_details
            : (json_decode($payment->payment_details ?? '[]', true) ?: []);
        $payment->payment_details = array_merge(
            $existingDetails,
            ['webhook_callback' => [
                'tran_ref' => $tranRef,
                'response_status' => $responseStatus,
                'response_message' => $responseMessage,
                'received_at' => now()->toIso8601String(),
            ]]
        );
        $payment->save();

        // Update linked schedule payment if applicable
        if ($payment->schedule_payment_id) {
            $schedulePayment = SchedulePayment::find($payment->schedule_payment_id);
            if ($schedulePayment && $schedulePayment->payment_status !== $newStatus) {
                $schedulePayment->payment_status = $newStatus;
                if ($newStatus === 'paid') {
                    $schedulePayment->paid_at = now();
                }
                $schedulePayment->save();
            }
        }

        Log::info('[CLICKPAY-WEBHOOK] Payment updated', [
            'payment_id' => $payment->id,
            'new_status' => $newStatus,
            'tran_ref' => $tranRef,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Verify ClickPay callback signature.
     *
     * ClickPay signs callbacks using the server key.
     * Signature = HMAC-SHA256(server_key, tran_ref + order_ref)
     */
    private function verifySignature(Request $request): bool
    {
        $serverKey = config('services.clickpay.server_key');
        $webhookSecret = config('services.clickpay.webhook_secret');
        $secret = $webhookSecret ?: $serverKey;

        if (empty($secret)) {
            Log::error('[CLICKPAY-WEBHOOK] No webhook secret or server key configured');
            return false;
        }

        $signature = $request->header('signature');
        if (empty($signature)) {
            return false;
        }

        // ClickPay HMAC: hash of tran_ref + order_ref using server key
        $tranRef = $request->input('tran_ref', '');
        $cartId = $request->input('cart_id', '');
        $expectedSignature = hash_hmac('sha256', $tranRef . $cartId, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
