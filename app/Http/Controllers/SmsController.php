<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    /**
     * Show SMS form.
     */
    public function create()
    {
        return view('sms.send');
    }

    /**
     * Send SMS.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'message' => ['required', 'string'],
            'count' => ['required', 'integer', 'min:1'],
        ]);

        $phones = array_map('trim', explode(',', $validated['phone']));

        try {
            for ($i = 0; $i < $validated['count']; $i++) {
                $this->sendSms($phones, $validated['message']);
            }

            return back()->with('status', "SMS sent {$validated['count']} time(s) successfully.");
        } catch (Exception $e) {
            Log::error('SMS sending failed: '.$e->getMessage(), [
                'phone' => $validated['phone'],
                'message' => $validated['message'],
                'count' => $validated['count'],
            ]);

            return back()->withErrors(['sms_error' => 'Failed to send SMS. Please try again later.']);
        }
    }

    /**
     * Actual SMS sending function.
     */
    protected function sendSms(array $phones, string $message)
    {
        $postData = [
            'src' => 'Arabianpay',
            'dests' => $phones,
            'body' => $message,
        ];

        $response = Http::withToken('byrIU6zU7Uk-Si-Z-qvA')
            ->acceptJson()
            ->post('https://api.oursms.com/msgs/sms', $postData);

        return $response->successful()
            ? $response->json()
            : $response->body();
    }
}
