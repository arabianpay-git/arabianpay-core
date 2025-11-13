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

            $this->info('Found ' . $schedules->count() . ' schedule(s) to attempt charge.');

            foreach ($schedules as $schedule) {
                DB::beginTransaction();
                try {
                    $fresh = SchedulePayment::where('id', $schedule->id)->lockForUpdate()->first();

                    if (! in_array($fresh->payment_status, ['pending', 'due', 'late'])) {
                        DB::commit();
                        $this->line("Skipping #{$fresh->id} status {$fresh->payment_status}");
                        continue;
                    }

                    // --- IDEMPOTENCY CHECK (no schedule_payment_id column required) ---
                    // Unique prefix to identify payments for this schedule
                    $cartIdentifierPrefix = "schedule_{$fresh->id}_";

                    // If a payment with this schedule cart marker already exists, skip.
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
                        $this->line("Skipping #{$fresh->id} - payment already recorded for today (idempotency).");
                        continue;
                    }

                    // find saved token from last successful payment for this user
                    $savedPayment = Payment::where('user_id', $fresh->user_id)
                        ->where('payment_status', 'paid')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->first(function ($p) {
                            try {
                                $details = is_array($p->payment_details) ? $p->payment_details : json_decode($p->payment_details, true);
                                return (!empty($details['raw']['token']) || !empty($details['token']));
                            } catch (\Throwable $e) {
                                return false;
                            }
                        });

                    if (! $savedPayment) {
                        $fresh->payment_status = 'failed';
                        $fresh->save();
                        DB::commit();

                        $reason = 'No stored payment token found for this user.';
                        if ($fresh->user && filter_var($fresh->user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                            Mail::to($fresh->user->email)->send(new PaymentFailedMail($fresh->user, $fresh, $reason));
                        }
                        $this->warn("Schedule #{$fresh->id} failed (no token).");
                        continue;
                    }

                    $pd = is_array($savedPayment->payment_details) ? $savedPayment->payment_details : json_decode($savedPayment->payment_details, true);
                    $token = $pd['raw']['token'] ?? $pd['token'] ?? null;

                    if (! $token) {
                        $fresh->payment_status = 'failed';
                        $fresh->save();
                        DB::commit();
                        $reason = 'Token missing in saved payment details.';
                        if ($fresh->user && filter_var($fresh->user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                            Mail::to($fresh->user->email)->send(new PaymentFailedMail($fresh->user, $fresh, $reason));
                        }
                        $this->warn("Schedule #{$fresh->id} failed (token missing).");
                        continue;
                    }

                    // create unique cartId (used for idempotency checks)
                    $cartId = $cartIdentifierPrefix . time();

                    $customer = [
                        'name' => trim(optional($fresh->user)->first_name . ' ' . optional($fresh->user)->last_name),
                        'email' => optional($fresh->user)->email,
                        'phone' => optional($fresh->user)->phone_number ?? null,
                    ];

                    $payload = [
                        'token' => $token,
                        'cart_id' => $cartId,
                        'cart_amount' => (float) $fresh->instalment_amount,
                        'cart_description' => "Scheduled instalment #{$fresh->instalment_number} for order {$fresh->order_id}",
                        'customer_details' => $customer,
                    ];

                    $this->info("Attempting charge for schedule #{$fresh->id} amount {$payload['cart_amount']}");

                    $result = $this->clickpay->chargeWithToken($payload);

                    if (isset($result['success']) && $result['success'] === true) {
                        $resp = $result['response'];

                        // store Payment WITHOUT schedule_payment_id column
                        $payment = Payment::create([
                            'user_id' => $fresh->user_id,
                            'seller_id' => $fresh->seller_id,
                            'order_id' => $fresh->order_id,
                            'amount' => $fresh->instalment_amount,
                            'payment_details' => is_array($resp) ? json_encode($resp) : (is_string($resp) ? $resp : json_encode($resp)),
                            // include cartId in invoice_number so we can find it later
                            'invoice_number' => 'INV-' . strtoupper(Str::random(6)) . '-' . $cartId,
                            'txn_code' => data_get($resp, 'raw.transactionReference') ?? data_get($resp, 'tran_ref') ?? null,
                            'payment_status' => 'paid',
                        ]);

                        // update schedule
                        $fresh->payment_status = 'paid';
                        $fresh->receipt = $payment->txn_code;
                        $fresh->deducted_amount = $fresh->instalment_amount;
                        $fresh->is_late = false;
                        $fresh->late_days = 0;
                        $fresh->save();

                        // update transaction collected
                        $transaction = Transaction::where('order_id', $fresh->order_id)->first();
                        if ($transaction) {
                            $transaction->collected = ($transaction->collected ?? 0) + $fresh->instalment_amount;
                            $transaction->payment_status = 'paid';
                            $transaction->save();
                        }

                        DB::commit();

                        if ($fresh->user && filter_var($fresh->user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                            Mail::to($fresh->user->email)->send(new PaymentSuccessMail($fresh->user, $payment, $fresh));
                        }

                        $this->info("Schedule #{$fresh->id} paid, txn: {$payment->txn_code}");
                        continue;
                    } else {
                        DB::rollBack();

                        $resp = $result['response'] ?? [];
                        $error = $result['error'] ?? ($resp['message'] ?? (is_array($resp) ? json_encode($resp) : (string)$resp));

                        DB::beginTransaction();
                        try {
                            $fresh->payment_status = 'failed';
                            $fresh->save();

                            SchedulePayment::where('order_id', $fresh->order_id)
                                ->where('due_date', '>', $fresh->due_date)
                                ->update(['payment_status' => 'due']);

                            $transaction = Transaction::where('order_id', $fresh->order_id)->first();
                            if ($transaction) {
                                $transaction->payment_status = 'late';
                                $transaction->save();
                            }

                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            Log::error('Failed to update schedules after charge fail: ' . $e->getMessage());
                        }

                        if ($fresh->user && filter_var($fresh->user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                            Mail::to($fresh->user->email)->send(new PaymentFailedMail($fresh->user, $fresh, $error));
                        }

                        $this->warn("Schedule #{$fresh->id} charge failed: " . substr($error, 0, 200));
                        continue;
                    }
                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error("Error processing schedule #{$schedule->id}: " . $e->getMessage(), ['schedule_id' => $schedule->id]);
                    $this->error("Error processing schedule #{$schedule->id}: " . $e->getMessage());
                }
            }
        } finally {
            $lock->release();
        }

        return 0;
    }
}
