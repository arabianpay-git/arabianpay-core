<?php

namespace App\Console\Commands;

use App\Mail\PaymentDueReminderMail;
use App\Mail\PaymentUpcomingReminderMail;
use App\Models\SchedulePayment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledPaymentReminders extends Command
{
    protected $signature = 'send:scheduled-payment-reminders {--date= : date to consider (Y-m-d)}';
    protected $description = 'Send pre-due (7/3/1 days) and daily due reminders for schedule payments.';

    public function handle()
    {
        $lockKey = 'send_scheduled_payment_reminders_lock';
        $lock = Cache::lock($lockKey, 600);

        if (! $lock->get()) {
            $this->info('Another reminders process is running. Exiting.');
            return 0;
        }

        try {
            $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();

            $this->info('Sending pre-due reminders...');
            $this->sendPreDueReminders($date);

            $this->info('Sending daily due reminders...');
            $this->sendDailyDueReminders($date);

            $this->info('Reminders run completed.');
        } catch (\Throwable $e) {
            Log::error('Error in SendScheduledPaymentReminders: ' . $e->getMessage());
            $this->error($e->getMessage());
        } finally {
            $lock->release();
        }

        return 0;
    }

    protected function sendPreDueReminders(Carbon $date)
    {
        $days = [7, 3, 1];
        foreach ($days as $d) {
            $targetDate = $date->copy()->addDays($d)->toDateString();

            $schedules = SchedulePayment::whereDate('due_date', $targetDate)
                ->whereNotIn('payment_status', ['paid', 'failed'])
                ->with('user')
                ->get();

            foreach ($schedules as $schedule) {
                $this->sendUniqueReminder($schedule, "pre_{$d}", $targetDate, function ($schedule) use ($d) {
                    $user = $schedule->user;
                    if ($user && filter_var($user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                        Mail::to($user->email)->send(new PaymentUpcomingReminderMail($user, $schedule, $d));
                        $this->info("Pre-{$d} reminder sent for schedule #{$schedule->id} to {$user->email}");
                    }
                });
            }
        }
    }

    protected function sendDailyDueReminders(Carbon $date)
    {
        $schedules = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereDate('due_date', '<=', $date)
            ->with('user')
            ->get();

        foreach ($schedules as $schedule) {
            $attempts = DB::table('schedule_payment_reminders')
                ->where('schedule_payment_id', $schedule->id)
                ->where('type', 'due_daily')
                ->count();

            $attemptNumber = $attempts + 1;

            $this->sendUniqueReminder($schedule, 'due_daily', $date->toDateString(), function ($schedule) use ($attemptNumber) {
                $user = $schedule->user;
                if ($user && filter_var($user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($user->email)->send(new PaymentDueReminderMail($user, $schedule, $attemptNumber));
                    $this->info("Due reminder (#{$attemptNumber}) sent for schedule #{$schedule->id} to {$user->email}");
                }
            });
        }
    }

    protected function sendUniqueReminder($schedule, string $type, $targetDate, callable $sendCallback)
    {
        try {
            DB::beginTransaction();

            $exists = DB::table('schedule_payment_reminders')
                ->where('schedule_payment_id', $schedule->id)
                ->where('type', $type)
                ->where('target_date', Carbon::parse($targetDate)->toDateString())
                ->exists();

            if ($exists) {
                DB::commit();
                $this->line("Reminder exists: {$type} schedule #{$schedule->id} target {$targetDate}");
                return;
            }

            $now = Carbon::now();
            DB::table('schedule_payment_reminders')->insert([
                'schedule_payment_id' => $schedule->id,
                'type' => $type,
                'target_date' => Carbon::parse($targetDate)->toDateString(),
                'sent_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::commit();

            // send email outside transaction
            try {
                call_user_func($sendCallback, $schedule);
            } catch (\Throwable $e) {
                Log::error("Failed to send reminder mail for schedule {$schedule->id}: " . $e->getMessage());
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error inserting reminder record for schedule {$schedule->id}: " . $e->getMessage());
        }
    }
}
