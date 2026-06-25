<?php

namespace Tests\Feature\Console;

use App\Mail\LowStockAlertMail;
use App\Models\LowStockNotification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifyLowStockProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_sends_alert_for_low_stock_product(): void
    {
        $user = User::factory()->create();

        $product = $this->createProduct($user->id, [
            'current_stock' => 2,
            'low_stock_quantity' => 5,
        ]);

        $this->artisan('products:notify-low-stock')
            ->assertSuccessful();

        Mail::assertSent(LowStockAlertMail::class, function ($mail) use ($product) {
            return $mail->product->id === $product->id
                && $mail->hasTo($product->user->email)
                && $mail->attemptNumber === 1;
        });

        $this->assertDatabaseHas('low_stock_notifications', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'emails_sent' => 1,
        ]);

        $notification = LowStockNotification::where('product_id', $product->id)->first();
        $this->assertNotNull($notification->last_email_sent_at);
    }

    public function test_sends_multiple_alerts_across_days(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user->id, [
            'current_stock' => 2,
            'low_stock_quantity' => 5,
        ]);

        // First run — sends email, increments to 1
        $this->artisan('products:notify-low-stock')->assertSuccessful();

        Mail::assertSent(LowStockAlertMail::class, 1);

        // Simulate a new day by backdating the notification
        $notification = LowStockNotification::where('product_id', $product->id)->first();
        $notification->update(['last_email_sent_at' => now()->subDay()]);

        // Re-initialize the fake to clear the sent mail queue
        Mail::fake();

        // Second run — sends second email
        $this->artisan('products:notify-low-stock')->assertSuccessful();

        Mail::assertSent(LowStockAlertMail::class, function ($mail) use ($product) {
            return $mail->product->id === $product->id && $mail->attemptNumber === 2;
        });

        $this->assertDatabaseHas('low_stock_notifications', [
            'product_id' => $product->id,
            'emails_sent' => 2,
        ]);
    }

    public function test_stops_after_three_emails(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user->id, [
            'current_stock' => 2,
            'low_stock_quantity' => 5,
        ]);

        // Pre-seed notification with 3 emails already sent
        LowStockNotification::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'emails_sent' => 3,
            'last_email_sent_at' => now()->subDay(),
        ]);

        $this->artisan('products:notify-low-stock')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_skips_product_with_sufficient_stock(): void
    {
        $user = User::factory()->create();

        $this->createProduct($user->id, [
            'current_stock' => 50,
            'low_stock_quantity' => 5,
        ]);

        $this->artisan('products:notify-low-stock')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_dedup_skips_if_already_notified_today(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct($user->id, [
            'current_stock' => 2,
            'low_stock_quantity' => 5,
        ]);

        // Pre-seed notification: already sent 1 email today
        LowStockNotification::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'emails_sent' => 1,
            'last_email_sent_at' => now(),
        ]);

        $this->artisan('products:notify-low-stock')->assertSuccessful();

        // No new email because last_email_sent_at is today
        Mail::assertNothingSent();

        $this->assertDatabaseHas('low_stock_notifications', [
            'product_id' => $product->id,
            'emails_sent' => 1,
        ]);
    }

    public function test_skips_product_without_user(): void
    {
        // Product without a user_id (orphan record)
        $product = $this->createProduct(null, [
            'current_stock' => 2,
            'low_stock_quantity' => 5,
        ]);

        $this->artisan('products:notify-low-stock')->assertSuccessful();

        Mail::assertNothingSent();

        $this->assertDatabaseMissing('low_stock_notifications', [
            'product_id' => $product->id,
        ]);
    }

    public function test_skips_product_with_user_but_no_email(): void
    {
        $user = User::factory()->create(['email' => '']);

        $product = $this->createProduct($user->id, [
            'current_stock' => 2,
            'low_stock_quantity' => 5,
        ]);

        $this->artisan('products:notify-low-stock')->assertSuccessful();

        Mail::assertNothingSent();
    }

    // ----------------------------------------------------------------
    //  Helpers
    // ----------------------------------------------------------------

    /**
     * Create a product with minimal required fields, bypassing the
     * trait constructor incompatibility in the Product model.
     */
    private function createProduct(?int $userId, array $overrides = []): Product
    {
        $data = array_merge([
            'name' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'added_by' => 'admin',
            'user_id' => $userId,
            'unit_price' => 100.00,
            'current_stock' => 10,
            'low_stock_quantity' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        $id = DB::table('products')->insertGetId($data);

        return Product::find($id);
    }
}
