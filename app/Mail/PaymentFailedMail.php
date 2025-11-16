<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $schedule;
    public $failureReason;

    public function __construct($user, $schedule, $failureReason = null)
    {
        $this->user = $user;
        $this->schedule = $schedule;
        $this->failureReason = $failureReason;
    }

    public function build()
    {
        return $this->subject('Payment Failed')
            ->view('emails.payment.failed')
            ->with([
                'user' => $this->user,
                'schedule' => $this->schedule,
                'failureReason' => $this->failureReason,
            ]);
    }
}
