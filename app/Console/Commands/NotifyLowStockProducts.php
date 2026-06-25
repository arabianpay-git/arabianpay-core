<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlertMail;
use App\Models\LowStockNotification;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyLowStockProducts extends Command
{
    protected $signature = 'products:notify-low-stock';

    protected $description = 'Send email alerts for low-stock products (3-day alert system).';

    public function handle()
    {
        Log::info('Low-stock cron started');

        try {
            $products = Product::with('user')
                ->whereColumn('current_stock', '<=', 'low_stock_quantity')
                ->get();

            foreach ($products as $product) {
                $user = $product->user;

                if (! $user || ! $user->email) {
                    continue;
                } // false mean user and email exist

                // Check if an alert was already sent
                $notification = LowStockNotification::firstOrCreate([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                ]);

                if ($notification->emails_sent >= 3) {
                    continue;
                } // Stop after 3 emails

                $lastSent = $notification->last_email_sent_at
                    ? Carbon::parse($notification->last_email_sent_at)
                    : null;

                // Send once per day (1-day gap)
                if ($lastSent && $lastSent->isToday()) {
                    continue;
                }

                Mail::to($user->email)->send(new LowStockAlertMail($product, $notification->emails_sent + 1));

                $notification->increment('emails_sent');
                $notification->last_email_sent_at = now();
                $notification->save();
            }

            Log::info('Low-stock cron completed successfully.');
        } catch (\Throwable $e) {
            Log::error('Low-stock cron failed: '.$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
