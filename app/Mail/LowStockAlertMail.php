<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $product;
    public $attemptNumber;

    public function __construct(Product $product, int $attemptNumber)
    {
        $this->product = $product;
        $this->attemptNumber = $attemptNumber;
    }

    public function build()
    {
        return $this->subject('⚠️ Low Stock Alert: ' . $this->product->name)
            ->view('emails.low_stock_alert')
            ->with([
                'product' => $this->product,
                'attemptNumber' => $this->attemptNumber,
                'user' => $this->product->user,
            ]);
    }
}
