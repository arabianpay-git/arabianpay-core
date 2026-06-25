<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentUpcomingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $schedule;

    public $daysLeft;

    public function __construct($user, $schedule, int $daysLeft)
    {
        $this->user = $user;
        $this->schedule = $schedule;
        $this->daysLeft = $daysLeft;
    }

    public function build()
    {
        return $this->subject("Upcoming payment due in {$this->daysLeft} day(s)")
            ->view('emails.payment.upcoming')
            ->with([
                'user' => $this->user,
                'schedule' => $this->schedule,
                'daysLeft' => $this->daysLeft,
            ]);
    }
}
