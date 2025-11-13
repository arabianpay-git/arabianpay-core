<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $payment;
    public $schedule;

    public function __construct($user, $payment, $schedule)
    {
        $this->user = $user;
        $this->payment = $payment;
        $this->schedule = $schedule;
    }

    public function build()
    {
        return $this->subject('Payment Successful')
            ->view('emails.payment.success')
            ->with([
                'user' => $this->user,
                'payment' => $this->payment,
                'schedule' => $this->schedule,
            ]);
    }
}
