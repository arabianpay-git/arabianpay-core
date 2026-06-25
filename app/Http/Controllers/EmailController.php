<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    /**
     * Show email form.
     */
    public function create()
    {
        return view('emails.send');
    }

    /**
     * Handle email sending.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        try {
            Mail::send([], [], function ($mail) use ($validated) {
                $mail->to($validated['email'])
                    ->subject($validated['subject'])
                    ->html(
                        view('emails.simple-message', [
                            'subjectLine' => $validated['subject'],
                            'bodyMessage' => $validated['message'],
                        ])->render()
                    )
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return back()->with('status', 'Email sent successfully.');
        } catch (Exception $e) {
            Log::error('Email sending failed: '.$e->getMessage(), [
                'to' => $validated['email'],
                'subject' => $validated['subject'],
            ]);

            return back()->withErrors([
                'email_error' => 'Failed to send email. Please try again later.'.$e->getMessage(),
            ]);
        }
    }
}
