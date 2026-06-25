<?php

namespace Tests\Feature\Console;

use App\Mail\PaymentFailedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\ClickPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ProcessScheduledPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function setUpCache(): void
    {
        $lock = Mockery::mock();
        $lock->shouldReceive('get')->andReturn(true);
        $lock->shouldReceive('release')->zeroOrMoreTimes();
        Cache::shouldReceive('lock')->withAnyArgs()->andReturn($lock);
    }

    // ----------------------------------------------------------------
    //  Non-ClickPay paths — these test the early-exit branches
    // ----------------------------------------------------------------

    public function test_no_prior_payment_fails(): void
    {
        $this->mock(ClickPayService::class)->shouldReceive('chargeWithToken')->never();
        $this->setUpCache();

        $schedule = SchedulePayment::factory()->create([
            'due_date' => now()->subDay(),
            'payment_status' => 'pending',
        ]);

        $this->artisan('process:scheduled-payments', ['--date' => now()->format('Y-m-d')])
            ->assertSuccessful();

        $schedule->refresh();
        $this->assertSame('failed', $schedule->payment_status);
        $this->assertSame('No stored payment token found.', $schedule->failure_reason);
        Mail::assertSent(PaymentFailedMail::class);
    }

    public function test_missing_token_fails(): void
    {
        $this->mock(ClickPayService::class)->shouldReceive('chargeWithToken')->never();
        $this->setUpCache();

        $order = Order::factory()->create();
        $user = $order->user;
        $seller = User::find($order->seller_id);

        $dummy = SchedulePayment::factory()->paid()->create([
            'user_id' => $user->id, 'order_id' => $order->id, 'seller_id' => $seller->id,
            'due_date' => now()->subDays(30),
            'instalment_amount' => $order->grand_total, 'principle_amount' => $order->grand_total,
        ]);

        Payment::create([
            'user_id' => $user->id, 'schedule_payment_id' => $dummy->id,
            'order_id' => $order->id, 'seller_id' => $seller->id,
            'payment_status' => 'paid',
            'payment_details' => json_encode(['some_field' => 'no token here']),
            'amount' => 500.00, 'invoice_number' => 'INV-NT', 'txn_code' => 'TXN-NT',
        ]);

        $schedule = SchedulePayment::factory()->create([
            'user_id' => $user->id, 'order_id' => $order->id, 'seller_id' => $seller->id,
            'due_date' => now()->subDay(), 'payment_status' => 'pending',
        ]);

        $this->artisan('process:scheduled-payments', ['--date' => now()->format('Y-m-d')])
            ->assertSuccessful();

        $schedule->refresh();
        $this->assertSame('failed', $schedule->payment_status);
        $this->assertSame('Token missing in saved payment details.', $schedule->failure_reason);
        Mail::assertSent(PaymentFailedMail::class);
    }

    public function test_already_paid_is_skipped(): void
    {
        $this->mock(ClickPayService::class)->shouldReceive('chargeWithToken')->never();
        $this->setUpCache();

        $schedule = SchedulePayment::factory()->paid()->create([
            'due_date' => now()->subDay(),
        ]);

        $this->artisan('process:scheduled-payments', ['--date' => now()->format('Y-m-d')])
            ->assertSuccessful();

        $schedule->refresh();
        $this->assertSame('paid', $schedule->payment_status);
        Mail::assertNothingSent();
    }

    public function test_concurrent_lock_exits_early(): void
    {
        $this->mock(ClickPayService::class)->shouldReceive('chargeWithToken')->never();

        $blocked = Mockery::mock();
        $blocked->shouldReceive('get')->andReturn(false);
        $blocked->shouldReceive('release')->zeroOrMoreTimes();
        Cache::shouldReceive('lock')->withAnyArgs()->andReturn($blocked);

        $schedule = SchedulePayment::factory()->create([
            'due_date' => now()->subDay(),
            'payment_status' => 'pending',
        ]);

        $this->artisan('process:scheduled-payments', ['--date' => now()->format('Y-m-d')])
            ->assertSuccessful();

        $schedule->refresh();
        $this->assertSame('pending', $schedule->payment_status);
        Mail::assertNothingSent();
    }
}
