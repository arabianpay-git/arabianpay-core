<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject($this->data['subject'])
            ->view('emails.order-status-updated')
            ->with([
                'order_id' => $this->data['order_id'],
                'delivery_status' => $this->data['delivery_status'],
                'general_status' => $this->data['general_status'],
                'status_message' => $this->data['status_message'],
                'tracking_url' => $this->data['tracking_url'],
                'customer_name' => $this->data['customer_name'],
            ]);
    }
}
