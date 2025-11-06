<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\SendReminderTrait;

class ReminderController extends Controller
{
    use SendReminderTrait;

    /**
     * Sends a reminder to a user via SMS, Email, or both.
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'method'  => 'required|in:sms,email,both',
            'message' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($request->user_id);
        $message = $request->message;
        $method = $request->method;
        $errors = [];
        $sent_via = [];

        // 2. Send SMS
        if ($method === 'sms' || $method === 'both') {
            if ($user->phone_number) {
                $result = $this->sendSmsViaOurSms([$user->phone_number], $message);
                // $result = $this->sendSmsViaOurSms(['0545232968'], $message);

                if (isset($result['error'])) {
                    $errors[] = 'SMS failed: ' . $result['error'];
                } else {
                    $sent_via[] = 'SMS';
                }
            } else {
                $errors[] = 'User has no phone number to send SMS.';
            }
        }

        // 3. Send Email
        if ($method === 'email' || $method === 'both') {
            if ($user->email) {
                // $this->sendEmail('asadbala41@gmail.com', $message);
                $this->sendEmail($user->email, $message);
                $sent_via[] = 'Email';
            } else {
                $errors[] = 'User has no email address.';
            }
        }

        // 4. Handle Results and Respond
        if (!empty($errors) && empty($sent_via)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send reminder. ' . implode(' | ', $errors),
            ], 400);
        }

        if (!empty($errors) && !empty($sent_via)) {
            $success_message = 'Reminder partially sent via ' . implode(' and ', $sent_via) . '.';
            $error_message = 'However, ' . implode(' | ', $errors);

            return response()->json([
                'status' => 'error',
                'message' => $success_message . ' ' . $error_message,
            ], 200);
        }

        // Fully successful
        return response()->json([
            'status' => 'success',
            'message' => 'Reminder sent successfully via ' . implode(' and ', $sent_via) . '!',
        ]);
    }
}
