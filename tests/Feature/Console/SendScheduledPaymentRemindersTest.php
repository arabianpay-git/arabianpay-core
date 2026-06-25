<?php

namespace Tests\Feature\Console;

use App\Mail\PaymentDueReminderMail;
use App\Mail\PaymentUpcomingReminderMail;
use App\Models\SchedulePayment;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class SendScheduledPaymentRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mockFirebaseService();
        $this->mockCacheLock();
    }

    public function test_pre_due_reminder_is_sent_seven_days_before_due_date(): void
    {
        $schedule = SchedulePayment::factory()->create([
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_status' => 'pending',
        ]);

        $this->artisan('send:scheduled-payment-reminders')
            ->assertSuccessful();

        $user = $schedule->user;

        Mail::assertSent(PaymentUpcomingReminderMail::class, function ($mail) use ($user, $schedule) {
            return $mail->hasTo($user->email) && $mail->schedule->id === $schedule->id && $mail->daysLeft === 7;
        });

        $this->assertDatabaseHas('schedule_payment_reminders', [
            'schedule_payment_id' => $schedule->id,
            'type' => 'pre_7',
            'target_date' => $schedule->due_date->toDateString(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'schedule_payment_reminder',
        ]);
    }

    public function test_duplicate_pre_due_reminder_is_skipped(): void
    {
        $schedule = SchedulePayment::factory()->create([
            'due_date' => now()->addDays(3)->toDateString(),
            'payment_status' => 'pending',
        ]);

        $this->artisan('send:scheduled-payment-reminders')
            ->assertSuccessful();

        $this->artisan('send:scheduled-payment-reminders')
            ->assertSuccessful();

        $count = \DB::table('schedule_payment_reminders')
            ->where('schedule_payment_id', $schedule->id)
            ->where('type', 'pre_3')
            ->count();

        $this->assertSame(1, $count);

        Mail::assertSent(PaymentUpcomingReminderMail::class, 1);
    }

    public function test_daily_due_reminder_is_sent_for_due_schedule(): void
    {
        $schedule = SchedulePayment::factory()->create([
            'due_date' => now()->toDateString(),
            'payment_status' => 'due',
        ]);

        $this->artisan('send:scheduled-payment-reminders')
            ->assertSuccessful();

        $user = $schedule->user;

        Mail::assertSent(PaymentDueReminderMail::class, function ($mail) use ($user, $schedule) {
            return $mail->hasTo($user->email) && $mail->schedule->id === $schedule->id && $mail->attemptNumber === 1;
        });

        $this->assertDatabaseHas('schedule_payment_reminders', [
            'schedule_payment_id' => $schedule->id,
            'type' => 'due_daily',
            'target_date' => now()->toDateString(),
        ]);
    }

    public function test_paid_schedule_is_skipped_for_due_reminders(): void
    {
        $schedule = SchedulePayment::factory()->paid()->create([
            'due_date' => now()->toDateString(),
        ]);

        $this->artisan('send:scheduled-payment-reminders')
            ->assertSuccessful();

        Mail::assertNothingSent();

        $this->assertDatabaseMissing('schedule_payment_reminders', [
            'schedule_payment_id' => $schedule->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'type' => 'schedule_payment_reminder',
        ]);
    }

    private function mockFirebaseService(): void
    {
        $this->mock(FirebaseService::class)
            ->shouldReceive('sendCustomNotification')
            ->withAnyArgs()
            ->zeroOrMoreTimes();
    }

    private function mockCacheLock(): void
    {
        $lock = Mockery::mock();
        $lock->shouldReceive('get')->andReturn(true);
        $lock->shouldReceive('release')->zeroOrMoreTimes();

        Cache::shouldReceive('lock')
            ->with('send_scheduled_payment_reminders_lock', 600)
            ->andReturn($lock);
    }
}
