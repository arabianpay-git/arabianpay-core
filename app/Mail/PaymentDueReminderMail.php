<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentDueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $schedule;
    public $attemptNumber;

    public function __construct($user, $schedule, $attemptNumber = 1)
    {
        $this->user = $user;
        $this->schedule = $schedule;
        $this->attemptNumber = $attemptNumber;
    }

    public function build()
    {
        return $this->subject("Payment due reminder (Attempt {$this->attemptNumber})")
            ->view('emails.payment.due_reminder')
            ->with([
                'user' => $this->user,
                'schedule' => $this->schedule,
                'attemptNumber' => $this->attemptNumber,
            ]);
    }
}
