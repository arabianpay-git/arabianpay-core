<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $mail = $this->subject($this->data['subject'])
            ->view('emails.order-accepted')
            ->with([
                'order_id' => $this->data['order_id'],
                'invoice_number' => $this->data['invoice_number'],
                'estimated_delivery_date' => $this->data['estimated_delivery_date'],
                'customer_name' => $this->data['customer_name'],
                'order_total' => $this->data['order_total'],
                'order_date' => $this->data['order_date'],
            ]);

        // Attach invoice if path exists
        if (isset($this->data['attachment_path']) && file_exists($this->data['attachment_path'])) {
            $mail->attach($this->data['attachment_path'], [
                'as' => $this->data['attachment_name'],
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
