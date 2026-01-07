<?php

namespace App\Console\Commands;

use App\Mail\PaymentDueReminderMail;
use App\Mail\PaymentUpcomingReminderMail;
use App\Models\SchedulePayment;
use App\Models\Notification as UserNotification;
use App\Services\FirebaseService;
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

    /**
     * Firebase service instance.
     *
     * @var FirebaseService
     */
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

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
            try {
                $lock->release();
            } catch (\Throwable $e) {
                // guard against release errors
                Log::warning('Could not release lock: ' . $e->getMessage());
            }
        }

        return 0;
    }

    protected function sendPreDueReminders(\Carbon\Carbon $date)
    {
        $days = [7, 3, 1];
        foreach ($days as $d) {
            $targetDate = $date->copy()->addDays($d)->toDateString();

            $schedules = SchedulePayment::whereDate('due_date', $targetDate)
                ->whereNotIn('payment_status', ['paid', 'failed'])
                ->with('user')
                ->get();

            foreach ($schedules as $schedule) {
                $this->sendUniqueReminder($schedule, "pre_{$d}", $targetDate, function ($schedule) use ($d, $targetDate) {
                    $user = $schedule->user;
                    if ($user && filter_var($user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                        // Send email
                        Mail::to($user->email)->send(new PaymentUpcomingReminderMail($user, $schedule, $d));
                        $this->info("Pre-{$d} reminder sent for schedule #{$schedule->id} to {$user->email}");
                    }

                    // Send FCM + persist notification (mobile_screen & reference_id required)
                    $notificationTitle = "Upcoming payment in {$d} day" . ($d > 1 ? 's' : '');
                    $notificationDescription = "Your scheduled payment #{$schedule->id} is due on {$schedule->due_date}.";
                    $notificationData = [
                        'title' => $notificationTitle,
                        'description' => $notificationDescription,
                        'mobile_screen' => 'schedule_payment',
                        'reference_id' => (string)$schedule->id,
                        'schedule_payment_id' => (string)$schedule->id,
                        'due_date' => (string)$schedule->due_date,
                        'days_left' => $d,
                    ];

                    $this->dispatchNotification(
                        $schedule->user?->id ?? null,
                        $notificationTitle,
                        $notificationDescription,
                        $notificationData
                    );
                });
            }
        }
    }

    protected function sendDailyDueReminders(\Carbon\Carbon $date)
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

            $this->sendUniqueReminder($schedule, 'due_daily', $date->toDateString(), function ($schedule) use ($attemptNumber, $date) {
                $user = $schedule->user;
                if ($user && filter_var($user->email ?? null, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($user->email)->send(new PaymentDueReminderMail($user, $schedule, $attemptNumber));
                    $this->info("Due reminder (#{$attemptNumber}) sent for schedule #{$schedule->id} to {$user->email}");
                }

                // Send FCM + persist notification (mobile_screen & reference_id required)
                $notificationTitle = "Payment due — attempt #{$attemptNumber}";
                $notificationDescription = "Your scheduled payment #{$schedule->id} is due (attempt {$attemptNumber}).";
                $notificationData = [
                    'title' => $notificationTitle,
                    'description' => $notificationDescription,
                    'mobile_screen' => 'schedule_payment',
                    'reference_id' => (string)$schedule->id,
                    'schedule_payment_id' => (string)$schedule->id,
                    'attempt' => $attemptNumber,
                    'due_date' => (string)$schedule->due_date,
                ];

                $this->dispatchNotification(
                    $schedule->user?->id ?? null,
                    $notificationTitle,
                    $notificationDescription,
                    $notificationData
                );
            });
        }
    }

    /**
     * Insert unique reminder record and run the provided callback (which should send emails/notifications).
     *
     * @param mixed $schedule
     * @param string $type
     * @param string $targetDate
     * @param callable $sendCallback
     * @return void
     */
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

            // send email & notification outside the DB transaction
            try {
                call_user_func($sendCallback, $schedule);
            } catch (\Throwable $e) {
                Log::error("Failed to send reminder mail/notification for schedule {$schedule->id}: " . $e->getMessage());
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error inserting reminder record for schedule {$schedule->id}: " . $e->getMessage());
        }
    }

    /**
     * Persist notification record and call FirebaseService to send push notifications.
     *
     * @param int|null $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    protected function dispatchNotification(?int $userId, string $title, string $body, array $data = []): void
    {
        if (empty($userId)) {
            Log::info('dispatchNotification: no user id provided, skipping FCM/persist.');
            return;
        }

        // ensure required keys exist
        if (!isset($data['mobile_screen']) || !isset($data['reference_id'])) {
            Log::warning("dispatchNotification: required data keys missing for user {$userId}. mobile_screen & reference_id are required.");
            // still proceed but ensure they exist
            $data['mobile_screen'] = $data['mobile_screen'] ?? 'schedule_payment';
            $data['reference_id'] = $data['reference_id'] ?? (string)($data['schedule_payment_id'] ?? '');
        }

        // ensure title & description exist in data (for mobile)
        $data['title'] = $data['title'] ?? $title;
        $data['description'] = $data['description'] ?? $body;

        // persist notification record
        try {
            UserNotification::create([
                'title' => $title,
                'description' => $body,
                'user_id' => $userId,
                'type' => 'schedule_payment_reminder',
                'data' => $data,
                'read_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to persist notification for user {$userId}: " . $e->getMessage());
        }

        // send via FirebaseService (handles empty tokens internally)
        try {
            $this->firebaseService->sendCustomNotification($userId, $title, $body, $data);
            $this->info("FCM dispatched for user {$userId} (title: {$title})");
        } catch (\Throwable $e) {
            Log::error("FCM dispatch failed for user {$userId}: " . $e->getMessage());
        }
    }
}
