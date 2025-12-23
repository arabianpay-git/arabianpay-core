<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\SendReminderTrait;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;

class ReminderController extends Controller
{
    use SendReminderTrait;

    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

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

        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->user_id);
            $message = $request->message;
            $method = $request->method;
            $errors = [];
            $sent_via = [];

            // Log reminder initiation
            $this->auditTrailService->log([
                'event_category' => 'communication',
                'event_type' => 'reminder_initiated',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'action_summary' => "Reminder initiated for user {$user->email}",
                'properties' => [
                    'method' => $method,
                    'message_preview' => substr($message, 0, 100),
                ],
            ]);

            // 2. Send SMS
            if ($method === 'sms' || $method === 'both') {
                if ($user->phone_number) {
                    // Log SMS attempt
                    $this->auditTrailService->log([
                        'event_category' => 'communication',
                        'event_type' => 'sms_attempt',
                        'entity_type' => 'User',
                        'entity_id' => $user->id,
                        'action_summary' => "SMS attempt for user {$user->email}",
                    ]);

                    $result = $this->sendSmsViaOurSms([$user->phone_number], $message);
                    // $result = $this->sendSmsViaOurSms(['0545232968'], $message);

                    if (isset($result['error'])) {
                        $errors[] = 'SMS failed: ' . $result['error'];

                        // Log SMS failure
                        $this->auditTrailService->log([
                            'event_category' => 'communication',
                            'event_type' => 'sms_failed',
                            'entity_type' => 'User',
                            'entity_id' => $user->id,
                            'action_summary' => "SMS failed for user {$user->email}",
                            'properties' => [
                                'error' => $result['error'],
                            ],
                        ]);
                    } else {
                        $sent_via[] = 'SMS';

                        // Log SMS success
                        $this->auditTrailService->log([
                            'event_category' => 'communication',
                            'event_type' => 'sms_success',
                            'entity_type' => 'User',
                            'entity_id' => $user->id,
                            'action_summary' => "SMS sent successfully to user {$user->email}",
                        ]);
                    }
                } else {
                    $errors[] = 'User has no phone number to send SMS.';

                    // Log missing phone
                    $this->auditTrailService->log([
                        'event_category' => 'communication',
                        'event_type' => 'sms_missing_phone',
                        'entity_type' => 'User',
                        'entity_id' => $user->id,
                        'action_summary' => "Cannot send SMS - user has no phone number",
                    ]);
                }
            }

            // 3. Send Email
            if ($method === 'email' || $method === 'both') {
                if ($user->email) {
                    // Log email attempt
                    $this->auditTrailService->log([
                        'event_category' => 'communication',
                        'event_type' => 'email_attempt',
                        'entity_type' => 'User',
                        'entity_id' => $user->id,
                        'action_summary' => "Email attempt for user {$user->email}",
                    ]);

                    // $this->sendEmail('asadbala41@gmail.com', $message);
                    $this->sendEmail($user->email, $message);
                    $sent_via[] = 'Email';

                    // Log email success
                    $this->auditTrailService->log([
                        'event_category' => 'communication',
                        'event_type' => 'email_success',
                        'entity_type' => 'User',
                        'entity_id' => $user->id,
                        'action_summary' => "Email sent successfully to user {$user->email}",
                    ]);
                } else {
                    $errors[] = 'User has no email address.';

                    // Log missing email
                    $this->auditTrailService->log([
                        'event_category' => 'communication',
                        'event_type' => 'email_missing_address',
                        'entity_type' => 'User',
                        'entity_id' => $user->id,
                        'action_summary' => "Cannot send email - user has no email address",
                    ]);
                }
            }

            DB::commit();

            // 4. Handle Results and Respond
            if (!empty($errors) && empty($sent_via)) {
                // Log complete failure
                $this->auditTrailService->log([
                    'event_category' => 'communication',
                    'event_type' => 'reminder_failed',
                    'entity_type' => 'User',
                    'entity_id' => $user->id,
                    'action_summary' => "Reminder failed for user {$user->email}",
                    'properties' => [
                        'errors' => $errors,
                    ],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send reminder. ' . implode(' | ', $errors),
                ], 400);
            }

            if (!empty($errors) && !empty($sent_via)) {
                $success_message = 'Reminder partially sent via ' . implode(' and ', $sent_via) . '.';
                $error_message = 'However, ' . implode(' | ', $errors);

                // Log partial success
                $this->auditTrailService->log([
                    'event_category' => 'communication',
                    'event_type' => 'reminder_partial_success',
                    'entity_type' => 'User',
                    'entity_id' => $user->id,
                    'action_summary' => "Reminder partially successful for user {$user->email}",
                    'properties' => [
                        'success_methods' => $sent_via,
                        'errors' => $errors,
                    ],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => $success_message . ' ' . $error_message,
                ], 200);
            }

            // Log complete success
            $this->auditTrailService->log([
                'event_category' => 'communication',
                'event_type' => 'reminder_success',
                'entity_type' => 'User',
                'entity_id' => $user->id,
                'action_summary' => "Reminder sent successfully to user {$user->email}",
                'properties' => [
                    'methods' => $sent_via,
                ],
            ]);

            // Fully successful
            return response()->json([
                'status' => 'success',
                'message' => 'Reminder sent successfully via ' . implode(' and ', $sent_via) . '!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log exception
            $this->auditTrailService->log([
                'event_category' => 'communication',
                'event_type' => 'reminder_exception',
                'entity_type' => 'User',
                'entity_id' => $request->user_id ?? null,
                'action_summary' => "Exception occurred while sending reminder",
                'properties' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send reminder. ' . $e->getMessage(),
            ], 400);
        }
    }
}
