<?php

namespace App\Console\Commands;

use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSuccessMail;
use App\Models\Payment;
use App\Models\SchedulePayment;
use App\Models\Transaction;
use App\Services\ClickPayService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProcessScheduledPayments extends Command
{
    protected $signature = 'process:scheduled-payments {--date= : date to process (Y-m-d)} {--limit= : optional limit per run}';
    protected $description = 'Attempt scheduled payments using stored tokens (ClickPay)';
    protected $clickpay;

    public function __construct(ClickPayService $clickpay)
    {
        parent::__construct();
        $this->clickpay = $clickpay;
    }

    public function handle()
    {
        $lockKey = 'process_scheduled_payments_lock';
        $lock = Cache::lock($lockKey, 600);

        if (!$lock->get()) {
            $this->info('Another process is running. Exiting.');
            return 0;
        }

        try {
            $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
            $limit = $this->option('limit') ? intval($this->option('limit')) : null;

            $query = SchedulePayment::whereIn('payment_status', ['pending', 'due', 'late'])
                ->whereDate('due_date', '<=', $date)
                ->orderBy('due_date', 'asc')
                ->with('user');

            if ($limit) {
                $query->limit($limit);
            }

            $schedules = $query->get();
            $this->info('Found ' . $schedules->count() . ' schedule(s) to attempt charge.');

            foreach ($schedules as $schedule) {
                DB::beginTransaction();
                try {
                    $fresh = SchedulePayment::where('id', $schedule->id)->lockForUpdate()->first();

                    if (!in_array($fresh->payment_status, ['pending', 'due', 'late'])) {
                        DB::commit();
                        $this->line("Skipping #{$fresh->id} status {$fresh->payment_status}");
                        continue;
                    }

                    $cartIdentifierPrefix = "schedule_{$fresh->id}_";

                    // Prevent duplicate payment for SAME schedule today
                    $existingPayment = Payment::where('order_id', $fresh->order_id)
                        ->where('amount', $fresh->instalment_amount)
                        ->where(function ($q) use ($cartIdentifierPrefix) {
                            $q->where('payment_details', 'like', "%{$cartIdentifierPrefix}%")
                                ->orWhere('invoice_number', 'like', "%{$cartIdentifierPrefix}%");
                        })
                        ->where('created_at', '>=', Carbon::today()->startOfDay())
                        ->exists();

                    if ($existingPayment) {
                        DB::commit();
                        $this->line("Skipping #{$fresh->id} - payment already recorded for today.");
                        continue;
                    }

                    // Fetch first successful payment for same order + user
                    $savedPayment = Payment::where('user_id', $fresh->user_id)
                        ->where('order_id', $fresh->order_id)
                        ->where('payment_status', 'paid')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    if (!$savedPayment) {
                        $reason = 'No stored payment token found for this order.';

                        // 🔥 UPDATED — Mark schedule as failed
                        $fresh->payment_status = 'failed';
                        $fresh->failure_reason = $reason;
                        $fresh->save();

                        DB::commit();
                        $this->sendEmailSafe($fresh, 'failed', $reason);

                        $this->warn("Schedule #{$fresh->id} failed (no token).");
                        continue;
                    }

                    $pd = is_array($savedPayment->payment_details)
                        ? $savedPayment->payment_details
                        : json_decode($savedPayment->payment_details, true);

                    $token = $pd['raw']['token'] ?? $pd['token'] ?? null;

                    if (!$token) {
                        $reason = 'Token missing in saved payment details.';

                        // 🔥 UPDATED — Mark schedule as failed
                        $fresh->payment_status = 'failed';
                        $fresh->failure_reason = $reason;
                        $fresh->save();

                        DB::commit();
                        $this->sendEmailSafe($fresh, 'failed', $reason);

                        $this->warn("Schedule #{$fresh->id} failed (token missing).");
                        continue;
                    }

                    // Prepare charge payload
                    $cartId = $cartIdentifierPrefix . time();
                    $customer = [
                        'name' => trim(optional($fresh->user)->first_name . ' ' . optional($fresh->user)->last_name),
                        'email' => optional($fresh->user)->email,
                        'phone' => optional($fresh->user)->phone_number ?? null,
                    ];

                    $payload = [
                        'token' => $token,
                        'cart_id' => $cartId,
                        'cart_amount' => (float)$fresh->instalment_amount,
                        'cart_description' => "Scheduled instalment #{$fresh->instalment_number} for order {$fresh->order_id}",
                        'customer_details' => $customer,
                    ];

                    $this->info("Attempting charge for schedule #{$fresh->id}");

                    $result = $this->clickpay->chargeWithToken($payload);
                    $resp = $result['response'] ?? [];
                    $success = $result['success'] ?? false;

                    $paymentStatus = $success ? 'paid' : 'failed';

                    $txnCode = $success
                        ? (data_get($resp, 'raw.transactionReference') ?? data_get($resp, 'tran_ref'))
                        : (data_get($resp, 'tran_ref'));

                    // 🔥 UPDATED — Always create Payment (success OR failed)
                    $payment = Payment::create([
                        'user_id' => $fresh->user_id,
                        'seller_id' => $fresh->seller_id,
                        'order_id' => $fresh->order_id,
                        'schedule_payment_id' => $fresh->id,
                        'amount' => $fresh->instalment_amount,
                        'payment_details' => json_encode($resp), // store full declined/approved response
                        'invoice_number' => 'INV-' . strtoupper(Str::random(6)) . '-' . $cartId,
                        'txn_code' => $txnCode,
                        'payment_status' => $paymentStatus,
                    ]);

                    if ($success) {
                        // SUCCESS
                        $fresh->payment_status = 'paid';
                        $fresh->receipt = $payment->txn_code;
                        $fresh->deducted_amount = $fresh->instalment_amount;
                        $fresh->is_late = false;
                        $fresh->late_days = 0;
                        $fresh->failure_reason = null;
                        $fresh->save();

                        $transaction = Transaction::where('order_id', $fresh->order_id)->first();
                        if ($transaction) {
                            $transaction->collected = ($transaction->collected ?? 0) + $fresh->instalment_amount;
                            $transaction->payment_status = 'paid';
                            $transaction->save();
                        }

                        DB::commit();
                        $this->sendEmailSafe($fresh, 'success', $payment);

                        $this->info("Schedule #{$fresh->id} paid successfully");
                    } else {
                        // 🔥 DECLINED PAYMENT
                        $declineMessage = $resp['payment_result']['response_message'] ?? 'Unknown decline reason';

                        // 🔥 UPDATED — mark schedule as FAILED
                        $fresh->payment_status = 'failed';
                        $fresh->failure_reason = "Declined by ClickPay: " . $declineMessage;
                        $fresh->save();

                        // 🔥 UPDATED — mark future payments as due
                        SchedulePayment::where('order_id', $fresh->order_id)
                            ->where('due_date', '>', $fresh->due_date)
                            ->update(['payment_status' => 'due']);

                        // UPDATE transaction
                        $transaction = Transaction::where('order_id', $fresh->order_id)->first();
                        if ($transaction) {
                            $transaction->payment_status = 'late';
                            $transaction->save();
                        }

                        DB::commit();
                        $this->sendEmailSafe($fresh, 'failed', $fresh->failure_reason);

                        $this->warn("Schedule #{$fresh->id} declined");
                    }
                } catch (\Throwable $e) {
                    DB::rollBack();

                    $reason = "Unexpected error: " . $e->getMessage();
                    Log::error("Error schedule #{$schedule->id}: " . $e->getMessage());

                    $fresh->payment_status = 'failed';
                    $fresh->failure_reason = $reason;
                    $fresh->save();

                    $this->error("Error schedule #{$schedule->id}: " . $reason);
                }
            }
        } finally {
            $lock->release();
        }

        return 0;
    }

    protected function sendEmailSafe(SchedulePayment $schedule, string $type, $payload = null)
    {
        try {
            if (!$schedule->user || !filter_var($schedule->user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            if ($type === 'success') {
                Mail::to($schedule->user->email)->send(new PaymentSuccessMail($schedule->user, $payload, $schedule));
            } else {
                Mail::to($schedule->user->email)->send(new PaymentFailedMail($schedule->user, $schedule, $payload));
            }
        } catch (\Throwable $e) {
            Log::error("Email not sent for schedule #{$schedule->id}: " . $e->getMessage());
            $schedule->failure_reason = ($schedule->failure_reason ?? '') . "\nEmail not sent: " . $e->getMessage();
            $schedule->save();
        }
    }
}
