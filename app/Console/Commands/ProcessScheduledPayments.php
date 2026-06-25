<?php

namespace App\Console\Commands;

use App\Mail\PaymentFailedMail;
use App\Mail\PaymentSuccessMail;
use App\Models\Payment;
use App\Models\SchedulePayment;
use App\Models\Transaction;
use App\Services\ClickPayService;
use App\Traits\WithApprovalContext;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProcessScheduledPayments extends Command
{
    protected $signature = 'process:scheduled-payments {--date=} {--limit=}';

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

        if (! $lock->get()) {
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
            $this->info('Found '.$schedules->count().' schedules.');

            // FIX 3: Wrap all SchedulePayment saves inside approval context to prevent
            // WithApprovalContext trait from firing Log::critical() on every save.
            SchedulePayment::runInApprovalContext(function () use ($schedules) {
                foreach ($schedules as $schedule) {
                    // FIX 1: Redis atomic lock per schedule prevents double-spend when
                    // two concurrent processes both pass the duplicate-payment check.
                    // The Payment::where(...)->exists() query spans related columns
                    // (payment_details LIKE), making lockForUpdate() unreliable.
                    $paymentLock = Cache::lock("payment_schedule:{$schedule->id}", 30);

                    if (! $paymentLock->get()) {
                        Log::warning("Could not acquire payment lock for schedule #{$schedule->id}, skipping (likely already being processed).");

                        continue;
                    }

                    try {
                        DB::beginTransaction();
                        try {
                            $fresh = SchedulePayment::where('id', $schedule->id)->lockForUpdate()->first();

                            if (! in_array($fresh->payment_status, ['pending', 'due', 'late'])) {
                                DB::commit();

                                continue;
                            }

                            $cartIdentifierPrefix = "schedule_{$fresh->id}_";

                            // Duplicate protection — now guarded by the Redis lock above
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

                                continue;
                            }

                            // Get original paid payment to extract token
                            $savedPayment = Payment::where('user_id', $fresh->user_id)
                                ->where('order_id', $fresh->order_id)
                                ->where('payment_status', 'paid')
                                ->orderBy('created_at', 'asc')
                                ->first();

                            if (! $savedPayment) {
                                $reason = 'No stored payment token found.';
                                $fresh->payment_status = 'failed';
                                $fresh->failure_reason = $reason;
                                $fresh->save();
                                DB::commit();
                                $this->sendEmailSafe($fresh, 'failed', $reason);

                                continue;
                            }

                            $pd = is_array($savedPayment->payment_details)
                                ? $savedPayment->payment_details
                                : json_decode($savedPayment->payment_details, true);

                            $token = $pd['raw']['token'] ?? $pd['token'] ?? null;

                            if (! $token) {
                                $reason = 'Token missing in saved payment details.';
                                $fresh->payment_status = 'failed';
                                $fresh->failure_reason = $reason;
                                $fresh->save();
                                DB::commit();
                                $this->sendEmailSafe($fresh, 'failed', $reason);

                                continue;
                            }

                            // Prepare charge request
                            $cartId = $cartIdentifierPrefix.time();

                            $payload = [
                                'token' => $token,
                                'cart_id' => $cartId,
                                'cart_amount' => (float) $fresh->instalment_amount,
                                'cart_description' => "Scheduled instalment #{$fresh->instalment_number} for order {$fresh->order_id}",
                                'customer_details' => [
                                    'name' => trim($fresh->user->first_name.' '.$fresh->user->last_name),
                                    'email' => $fresh->user->email,
                                    'phone' => $fresh->user->phone_number,
                                ],
                            ];

                            $result = $this->clickpay->chargeWithToken($payload);
                            $resp = $result['response'] ?? [];

                            // 🔥 UPDATED — strict ClickPay response check
                            $status = data_get($resp, 'payment_result.response_status');

                            // A = Approved
                            $isApproved = ($status === 'A'); // 🔥 UPDATED
                            // D = Declined
                            $isDeclined = ($status === 'D'); // 🔥 UPDATED

                            $txnCode = data_get($resp, 'raw.transactionReference') ?? data_get($resp, 'tran_ref');

                            // 🔥 UPDATED — Always create Payment entry
                            $payment = Payment::create([
                                'user_id' => $fresh->user_id,
                                'seller_id' => $fresh->seller_id,
                                'order_id' => $fresh->order_id,
                                'schedule_payment_id' => $fresh->id,
                                'amount' => $fresh->instalment_amount,
                                'payment_details' => json_encode($resp),
                                'invoice_number' => 'INV-'.strtoupper(Str::random(6)).'-'.$cartId,
                                'txn_code' => $txnCode,
                                'payment_status' => $isApproved ? 'paid' : 'failed', // 🔥 UPDATED
                            ]);

                            if ($isApproved) {
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
                                    if (extension_loaded('bcmath')) {
                                        $transaction->collected = bcadd(
                                            (string) ($transaction->collected ?? 0),
                                            (string) $fresh->instalment_amount,
                                            2
                                        );
                                    } else {
                                        $existingHalalas = (int) round(((float) ($transaction->collected ?? 0)) * 100);
                                        $addedHalalas = (int) round(((float) $fresh->instalment_amount) * 100);
                                        $transaction->collected = ($existingHalalas + $addedHalalas) / 100;
                                    }
                                    $transaction->payment_status = 'paid';
                                    Transaction::runInApprovalContext(function () use ($transaction) {
                                        $transaction->save();
                                    });
                                }

                                DB::commit();
                                app(\App\Services\AuditTrailService::class)->logCrudOperation(
                                    'process',
                                    'ScheduledPayment',
                                    $fresh->id ?? null,
                                    'Scheduled payment processed successfully',
                                    null,
                                    ['status' => 'processed', 'amount' => $fresh->instalment_amount ?? null]
                                );
                                $this->sendEmailSafe($fresh, 'success', $payment);
                            }

                            // 🔥 UPDATED — check DECLINED explicitly
                            if ($isDeclined) {
                                $declineMsg = data_get($resp, 'payment_result.response_message', 'Declined');

                                // Mark schedule failed (inside transaction)
                                $fresh->payment_status = 'failed';               // 🔥 UPDATED
                                $fresh->failure_reason = 'Declined: '.$declineMsg; // 🔥 UPDATED
                                $fresh->save();

                                // Mark future instalments as due
                                SchedulePayment::where('order_id', $fresh->order_id)
                                    ->where('due_date', '>', $fresh->due_date)
                                    ->update(['payment_status' => 'due']);

                                // Update transaction as late
                                $transaction = Transaction::where('order_id', $fresh->order_id)->first();
                                if ($transaction) {
                                    $transaction->payment_status = 'late';
                                    $transaction->save();
                                }

                                DB::commit();
                                app(\App\Services\AuditTrailService::class)->logCrudOperation(
                                    'process',
                                    'ScheduledPayment',
                                    $fresh->id ?? null,
                                    'Scheduled payment declined',
                                    null,
                                    ['status' => 'declined', 'amount' => $fresh->instalment_amount ?? null, 'reason' => $declineMsg]
                                );
                                Log::warning("Schedule #{$schedule->id}: Payment declined — {$declineMsg}");
                                $this->sendEmailSafe($fresh, 'failed', $fresh->failure_reason);
                            } else {
                                // Non-A/non-D response (e.g. pending/error/timeout)
                                $fresh->payment_status = 'failed';
                                $fresh->failure_reason = 'Unhandled ClickPay response status: '.($status ?: 'empty');
                                $fresh->save();

                                DB::commit();
                                app(\App\Services\AuditTrailService::class)->logCrudOperation(
                                    'process',
                                    'ScheduledPayment',
                                    $fresh->id ?? null,
                                    'Scheduled payment failed with unhandled gateway status',
                                    null,
                                    ['status' => 'failed', 'amount' => $fresh->instalment_amount ?? null, 'gateway_status' => $status, 'response' => $resp]
                                );
                                Log::warning("Schedule #{$schedule->id}: Unhandled ClickPay status — {$status}", ['response' => $resp]);
                            }
                        } catch (\Throwable $e) {
                            DB::rollBack();

                            Log::error("Error schedule #{$schedule->id}: ".$e->getMessage());

                            // FIX 2: Do NOT save the stale $fresh model outside the rolled-back
                            // transaction. Instead, fetch a fresh instance in a NEW transaction
                            // and mark it failed there.
                            SchedulePayment::runInApprovalContext(function () use ($schedule) {
                                DB::transaction(function () use ($schedule) {
                                    $s = SchedulePayment::find($schedule->id);
                                    if ($s) {
                                        $s->payment_status = 'failed';
                                        $s->failure_reason = 'Processing error — see application logs (schedule #'.$schedule->id.')';
                                        $s->save();
                                    }
                                });
                            });
                        }
                    } finally {
                        // FIX 1 (continued): Always release the per-schedule Redis lock
                        $paymentLock->release();
                    }
                }
            });
        } finally {
            $lock->release();
        }

        return 0;
    }

    protected function sendEmailSafe(SchedulePayment $schedule, string $type, $payload = null)
    {
        try {
            if (! $schedule->user || ! filter_var($schedule->user->email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            if ($type === 'success') {
                Mail::to($schedule->user->email)->send(new PaymentSuccessMail($schedule->user, $payload, $schedule));
            } else {
                Mail::to($schedule->user->email)->send(new PaymentFailedMail($schedule->user, $schedule, $payload));
            }
        } catch (\Throwable $e) {
            Log::error("Email failed for schedule #{$schedule->id}: ".$e->getMessage());
        }
    }
}
