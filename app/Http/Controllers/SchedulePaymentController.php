<?php

namespace App\Http\Controllers;

use App\Models\PartialPayment;
use App\Models\SchedulePayment;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SchedulePaymentController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function filterByPaymentStatus($status)
    {
        try {
            $user = currentUser();
            $validStatuses = ['pending', 'paid', 'overdue', 'cancelled', 'failed'];

            if (!in_array($status, $validStatuses)) {
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'invalid_payment_status_filter',
                    'entity_type' => 'SchedulePayment',
                    'action_summary' => 'Attempted to filter by invalid payment status',
                    'properties' => [
                        'requested_status' => $status,
                        'valid_statuses' => $validStatuses,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type
                    ]
                ]);

                return redirect()->route('schedule-payments.index')
                    ->with('error', 'Invalid payment status filter.');
            }

            $schedulePayments = SchedulePayment::with('assigned')->where('payment_status', $status)
                ->when($user->user_type !== 'admin', function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                })->orderByRaw('assigned_to IS NULL DESC')
                ->select('uuid', 'instalment_number', 'due_date', 'instalment_amount', 'payment_status')
                ->paginate(10);

            $type = ucfirst($status);

            // Log filtered view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Schedule payment filtered view required for collections management and payment tracking',
                'legitimate_interest',
                ['payment_status', 'due_dates', 'payment_amounts']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'data_access',
                'event_type' => 'schedule_payments_filtered_view',
                'entity_type' => 'SchedulePayment',
                'action_summary' => 'Viewed schedule payments filtered by status: ' . $status,
                'properties' => [
                    'filter_status' => $status,
                    'total_results' => $schedulePayments->total(),
                    'current_page' => $schedulePayments->currentPage(),
                    'per_page' => $schedulePayments->perPage(),
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'assigned_only' => $user->user_type !== 'admin'
                ]
            ], $justificationData));

            return view('admin.schedule-payment.index', compact('schedulePayments', 'type'));
        } catch (\Exception $e) {
            Log::error('Failed to filter schedule payments by status', [
                'error' => $e->getMessage(),
                'status' => $status,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payments_filter_failed',
                'entity_type' => 'SchedulePayment',
                'action_summary' => 'Failed to filter schedule payments by status',
                'properties' => [
                    'error' => $e->getMessage(),
                    'requested_status' => $status,
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->route('schedule-payments.index')
                ->with('error', 'Failed to load schedule payments. Please try again.');
        }
    }

    public function index()
    {
        try {
            $user = currentUser();

            $schedulePayments = SchedulePayment::with('assigned')->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })
                ->orderByRaw('assigned_to IS NULL DESC')
                ->select('uuid', 'instalment_number', 'due_date', 'instalment_amount', 'payment_status')
                ->paginate(10);

            $type = "All";

            // Log schedule payments list view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Schedule payments list view required for collections oversight and payment management',
                'legitimate_interest',
                ['payment_status', 'due_dates', 'customer_references']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'data_access',
                'event_type' => 'schedule_payments_list_view',
                'entity_type' => 'SchedulePayment',
                'action_summary' => 'Viewed all schedule payments',
                'properties' => [
                    'total_payments' => $schedulePayments->total(),
                    'current_page' => $schedulePayments->currentPage(),
                    'per_page' => $schedulePayments->perPage(),
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'view_type' => $user->user_type === 'admin' ? 'admin_view' : 'assigned_view',
                    'assigned_only' => $user->user_type !== 'admin'
                ]
            ], $justificationData));

            return view('admin.schedule-payment.index', compact('schedulePayments', 'type'));
        } catch (\Exception $e) {
            Log::error('Failed to load schedule payments list', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payments_list_failed',
                'entity_type' => 'SchedulePayment',
                'action_summary' => 'Failed to load schedule payments list',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load schedule payments. Please try again.');
        }
    }

    public function show(SchedulePayment $schedulePayment)
    {
        try {
            $user = currentUser();

            // Check if user has permission to view this schedule payment
            if ($user->user_type !== 'admin' && $schedulePayment->assigned_to !== $user->id) {
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_schedule_payment_view',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $schedulePayment->id,
                    'action_summary' => 'User attempted to view unauthorized schedule payment',
                    'properties' => [
                        'schedule_payment_id' => $schedulePayment->id,
                        'assigned_to' => $schedulePayment->assigned_to,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'unauthorized_access' => true
                    ]
                ]);

                return redirect()->route('schedule-payments.index')
                    ->with('error', 'You are not authorized to view this schedule payment.');
            }

            $schedulePayment->load([
                'assigned',
                'user',
                'customer',
                'checkout.orders',
                'payment',
                'claims',
            ]);

            // Log detailed schedule payment view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Schedule payment details view required for collections, customer service, and payment verification',
                'legitimate_interest',
                ['customer_details', 'payment_amount', 'due_date', 'payment_history']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'data_access',
                'event_type' => 'schedule_payment_details_view',
                'entity_type' => 'SchedulePayment',
                'entity_id' => $schedulePayment->id,
                'action_summary' => 'Viewed detailed schedule payment information',
                'properties' => [
                    'schedule_payment_id' => $schedulePayment->id,
                    'uuid' => $schedulePayment->uuid,
                    'instalment_number' => $schedulePayment->instalment_number,
                    'due_date' => $schedulePayment->due_date,
                    'instalment_amount' => $schedulePayment->instalment_amount,
                    'payment_status' => $schedulePayment->payment_status,
                    'deducted_amount' => $schedulePayment->deducted_amount,
                    'customer_id' => $schedulePayment->user_id,
                    'assigned_to' => $schedulePayment->assigned_to,
                    'has_payment_record' => !is_null($schedulePayment->payment),
                    'claims_count' => $schedulePayment->claims->count(),
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type
                ]
            ], $justificationData));

            return view('admin.schedule-payment.show', compact('schedulePayment'));
        } catch (\Exception $e) {
            Log::error('Failed to load schedule payment details', [
                'error' => $e->getMessage(),
                'schedule_payment_id' => $schedulePayment->id ?? 'unknown',
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payment_details_failed',
                'entity_type' => 'SchedulePayment',
                'entity_id' => $schedulePayment->id ?? null,
                'action_summary' => 'Failed to load schedule payment details',
                'properties' => [
                    'error' => $e->getMessage(),
                    'schedule_payment_id' => $schedulePayment->id ?? 'unknown',
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->route('schedule-payments.index')
                ->with('error', 'Failed to load schedule payment details. Please try again.');
        }
    }

    public function paymentJson(SchedulePayment $schedulePayment)
    {
        try {
            $user = currentUser();

            // Check if user has permission to view this schedule payment
            if ($user->user_type !== 'admin' && $schedulePayment->assigned_to !== $user->id) {
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_schedule_payment_api',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $schedulePayment->id,
                    'action_summary' => 'User attempted unauthorized API access to schedule payment',
                    'properties' => [
                        'schedule_payment_id' => $schedulePayment->id,
                        'assigned_to' => $schedulePayment->assigned_to,
                        'user_id' => $user->id,
                        'api_endpoint' => 'paymentJson',
                        'unauthorized_access' => true
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized access'
                ], 403);
            }

            $schedulePayment->load(['payment', 'user', 'checkout', 'claims']);

            $payment = $schedulePayment->payment;

            $data = [
                'schedule' => [
                    'id' => $schedulePayment->id,
                    'uuid' => $schedulePayment->uuid,
                    'instalment_number' => $schedulePayment->instalment_number,
                    'due_date' => optional($schedulePayment->due_date)->toDateString(),
                    'instalment_amount' => $schedulePayment->instalment_amount,
                    'late_fee' => $schedulePayment->late_fee,
                    'status' => $schedulePayment->payment_status,
                    'is_late' => (bool)$schedulePayment->is_late,
                    'late_days' => $schedulePayment->late_days,
                ],
                'checkout' => $schedulePayment->checkout ? [
                    'id' => $schedulePayment->checkout->id,
                    'total_amount' => $schedulePayment->checkout->total_amount,
                ] : null,
                'user' => $schedulePayment->user ? [
                    'id' => $schedulePayment->user->id,
                    'name' => $schedulePayment->user->name,
                    'email' => $schedulePayment->user->email,
                ] : null,
                'payment' => $payment ? [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status ?? 'paid',
                    'method' => $payment->method ?? null,
                    'reference' => $payment->reference ?? null,
                    'created_at' => optional($payment->created_at)->toDateTimeString(),
                ] : null,
                'claims' => $schedulePayment->claims->map(function ($claim) {
                    return [
                        'id' => $claim->id,
                        'claim_type' => $claim->claim_type,
                        'claim_status' => $claim->claim_status,
                        'priority' => $claim->priority,
                        'attempt_count' => $claim->attempt_count,
                        'next_follow_up' => optional($claim->next_follow_up)->toDateTimeString(),
                        'notes' => $claim->notes,
                        'created_at' => optional($claim->created_at)->toDateTimeString(),
                    ];
                })->values()->all(),
            ];

            // Log API access to payment data
            $justificationData = $this->auditTrailService->withJustification(
                'Schedule payment API access required for system integration and payment verification',
                'legitimate_interest',
                ['payment_status', 'payment_amount', 'customer_reference']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'api_operations',
                'event_type' => 'schedule_payment_api_access',
                'entity_type' => 'SchedulePayment',
                'entity_id' => $schedulePayment->id,
                'action_summary' => 'Accessed schedule payment data via API',
                'properties' => [
                    'schedule_payment_id' => $schedulePayment->id,
                    'api_endpoint' => 'paymentJson',
                    'http_method' => 'GET',
                    'payment_status' => $schedulePayment->payment_status,
                    'has_payment_data' => !is_null($payment),
                    'claims_count' => count($data['claims']),
                    'requested_by' => $user->id,
                    'request_ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]
            ], $justificationData));

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch schedule payment JSON data', [
                'error' => $e->getMessage(),
                'schedule_payment_id' => $schedulePayment->id ?? 'unknown',
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payment_api_failed',
                'entity_type' => 'SchedulePayment',
                'entity_id' => $schedulePayment->id ?? null,
                'action_summary' => 'Failed to fetch schedule payment data via API',
                'properties' => [
                    'error' => $e->getMessage(),
                    'schedule_payment_id' => $schedulePayment->id ?? 'unknown',
                    'api_endpoint' => 'paymentJson',
                    'request_ip' => request()->ip(),
                    'user_id' => Auth::id()
                ]
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch schedule payment data'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = currentUser();
            $payment = SchedulePayment::findOrFail($id);

            // Check if user has permission to update this schedule payment
            if ($user->user_type !== 'admin' && $payment->assigned_to !== $user->id) {
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_schedule_payment_update',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $payment->id,
                    'action_summary' => 'User attempted to update unauthorized schedule payment',
                    'properties' => [
                        'schedule_payment_id' => $payment->id,
                        'assigned_to' => $payment->assigned_to,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'unauthorized_update' => true
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'You are not authorized to update this schedule payment.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'due_date' => 'required|date|after_or_equal:today',
            ]);

            if ($validator->fails()) {
                // Log validation failure
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'schedule_payment_update_validation_failed',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $payment->id,
                    'action_summary' => 'Schedule payment update failed validation',
                    'properties' => [
                        'validation_errors' => $validator->errors()->toArray(),
                        'schedule_payment_id' => $payment->id,
                        'customer_id' => $payment->user_id,
                        'attempted_by' => $user->id
                    ]
                ]);

                return response()->json(['success' => false, 'error' => $validator->errors()->first()]);
            }

            // Capture before state for audit
            $beforeState = $payment->toArray();

            DB::beginTransaction();

            $oldDueDate = $payment->due_date;
            $payment->update([
                'due_date' => $request->due_date,
                'payment_status' => 'pending',
                'updated_by' => $user->id,
            ]);

            // Log schedule payment update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Schedule payment due date update required for customer accommodation and payment rescheduling',
                'legitimate_interest',
                ['due_date', 'payment_schedule', 'customer_reference']
            );

            $this->auditTrailService->logUpdated(
                $payment,
                $beforeState,
                'Updated schedule payment #' . $payment->id . ' due date',
                array_merge([
                    'event_category' => 'payment_operations',
                    'event_type' => 'schedule_payment_updated',
                    'entity_type' => 'SchedulePayment',
                    'properties' => [
                        'schedule_payment_id' => $payment->id,
                        'customer_id' => $payment->user_id,
                        'old_due_date' => $oldDueDate,
                        'new_due_date' => $request->due_date,
                        'instalment_number' => $payment->instalment_number,
                        'instalment_amount' => $payment->instalment_amount,
                        'old_payment_status' => $beforeState['payment_status'] ?? 'unknown',
                        'new_payment_status' => 'pending',
                        'updated_by' => $user->id,
                        'updated_by_type' => $user->user_type,
                        'changes_made' => $this->getSchedulePaymentChangedFields($beforeState, $payment->toArray())
                    ]
                ], $justificationData)
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedule payment updated successfully.',
                'data' => [
                    'id' => $payment->id,
                    'new_due_date' => $payment->due_date,
                    'payment_status' => $payment->payment_status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update schedule payment', [
                'error' => $e->getMessage(),
                'schedule_payment_id' => $id,
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payment_update_failed',
                'entity_type' => 'SchedulePayment',
                'entity_id' => $id,
                'action_summary' => 'Failed to update schedule payment',
                'properties' => [
                    'error' => $e->getMessage(),
                    'schedule_payment_id' => $id,
                    'requested_due_date' => $request->due_date,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update schedule payment. Please try again.'
            ], 500);
        }
    }

    public function payNow(Request $request)
    {
        try {
            $user = currentUser();

            $validator = Validator::make($request->all(), [
                'schedule_id'    => 'required|exists:schedule_payments,id',
                'payment_method' => 'required',
                'receipt'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            ]);

            if ($validator->fails()) {
                // Log validation failure
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'schedule_payment_paynow_validation_failed',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $request->schedule_id,
                    'action_summary' => 'Schedule payment paynow failed validation',
                    'properties' => [
                        'validation_errors' => $validator->errors()->toArray(),
                        'schedule_payment_id' => $request->schedule_id,
                        'payment_method' => $request->payment_method,
                        'attempted_by' => $user->id
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $payment = SchedulePayment::with('user')->findOrFail($request->schedule_id);

            // Check if user has permission to process payment
            if ($user->user_type !== 'admin' && $payment->assigned_to !== $user->id) {
                DB::rollBack();

                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_schedule_payment_paynow',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $payment->id,
                    'action_summary' => 'User attempted unauthorized payment processing',
                    'properties' => [
                        'schedule_payment_id' => $payment->id,
                        'assigned_to' => $payment->assigned_to,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'unauthorized_payment' => true,
                        'payment_method' => $request->payment_method
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to process this payment.',
                ], 403);
            }

            // Safely handle null deducted_amount
            $deductedAmount = $payment->deducted_amount ?? 0;

            // Prevent double payment if already fully paid
            if ($deductedAmount >= $payment->instalment_amount) {
                DB::rollBack();

                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'schedule_payment_already_paid',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $payment->id,
                    'action_summary' => 'Attempted to pay already fully paid schedule payment',
                    'properties' => [
                        'schedule_payment_id' => $payment->id,
                        'customer_id' => $payment->user_id,
                        'instalment_amount' => $payment->instalment_amount,
                        'deducted_amount' => $deductedAmount,
                        'payment_status' => $payment->payment_status,
                        'attempted_by' => $user->id
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'This schedule payment is already fully paid.',
                ], 400);
            }

            // Calculate remaining amount
            $remainingAmount = $payment->instalment_amount - $deductedAmount;

            // Capture before state for audit
            $beforeState = $payment->toArray();

            try {
                $payment->payment_method = $request->payment_method;
                $payment->deducted_amount += $remainingAmount;
                $payment->payment_status = 'paid';
                $payment->paid_at = now();

                // Handle receipt upload
                $receiptPath = null;
                if ($request->hasFile('receipt')) {
                    $disk = 'public';
                    $folder = 'receipts';
                    $file = $request->file('receipt');

                    $extension = strtolower($file->getClientOriginalExtension());
                    $filename = Str::random(40) . '.' . $extension;
                    $receiptPath = "$folder/$filename";

                    $file->storeAs($folder, $filename, $disk);
                    $payment->receipt = $receiptPath;

                    // Log receipt upload
                    $this->auditTrailService->log([
                        'event_category' => 'document_operations',
                        'event_type' => 'payment_receipt_uploaded',
                        'entity_type' => 'SchedulePayment',
                        'entity_id' => $payment->id,
                        'action_summary' => 'Uploaded payment receipt',
                        'properties' => [
                            'schedule_payment_id' => $payment->id,
                            'receipt_filename' => $filename,
                            'file_extension' => $extension,
                            'file_size_kb' => round($file->getSize() / 1024, 2),
                            'uploaded_by' => $user->id
                        ]
                    ]);
                }

                $payment->save();

                // Update related partial payments if needed
                $updatedPartialCount = $this->markPartialPaymentsAsPaid(
                    $request->schedule_id,
                    $request->payment_method,
                    $payment->receipt ?? null,
                    $user->id
                );

                // Log payment processing with justification
                $justificationData = $this->auditTrailService->withJustification(
                    'Schedule payment processing required for collections, revenue recognition, and customer account reconciliation',
                    'legitimate_interest',
                    ['payment_amount', 'payment_method', 'receipt_document', 'customer_reference']
                );

                $this->auditTrailService->logUpdated(
                    $payment,
                    $beforeState,
                    'Processed payment for schedule payment #' . $payment->id,
                    array_merge([
                        'event_category' => 'payment_operations',
                        'event_type' => 'schedule_payment_processed',
                        'entity_type' => 'SchedulePayment',
                        'properties' => [
                            'schedule_payment_id' => $payment->id,
                            'customer_id' => $payment->user_id,
                            'customer_email' => $payment->user->email ?? null,
                            'instalment_number' => $payment->instalment_number,
                            'total_amount' => $payment->instalment_amount,
                            'remaining_amount_paid' => $remainingAmount,
                            'previous_deducted_amount' => $deductedAmount,
                            'new_deducted_amount' => $payment->deducted_amount,
                            'payment_method' => $request->payment_method,
                            'old_payment_status' => $beforeState['payment_status'] ?? 'unknown',
                            'new_payment_status' => 'paid',
                            'paid_at' => $payment->paid_at,
                            'has_receipt' => !is_null($receiptPath),
                            'receipt_path' => $receiptPath,
                            'updated_partial_payments_count' => $updatedPartialCount,
                            'processed_by' => $user->id,
                            'processed_by_type' => $user->user_type,
                            'changes_made' => $this->getSchedulePaymentChangedFields($beforeState, $payment->toArray())
                        ]
                    ], $justificationData)
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment submitted successfully. Amount: SAR ' . number_format($remainingAmount, 2),
                    'data' => [
                        'payment_id' => $payment->id,
                        'amount_paid' => $remainingAmount,
                        'total_deducted' => $payment->deducted_amount,
                        'payment_status' => $payment->payment_status,
                        'paid_at' => $payment->paid_at,
                        'receipt_uploaded' => !is_null($receiptPath)
                    ]
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to process schedule payment', [
                'error' => $e->getMessage(),
                'schedule_id' => $request->schedule_id ?? 'unknown',
                'payment_method' => $request->payment_method ?? 'unknown',
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payment_process_failed',
                'entity_type' => 'SchedulePayment',
                'entity_id' => $request->schedule_id ?? null,
                'action_summary' => 'Failed to process schedule payment',
                'properties' => [
                    'error' => $e->getMessage(),
                    'schedule_payment_id' => $request->schedule_id ?? 'unknown',
                    'payment_method' => $request->payment_method ?? 'unknown',
                    'has_receipt_file' => $request->hasFile('receipt'),
                    'attempted_by' => Auth::id()
                ]
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Private helper to mark all partial payments of a schedule as paid
     */
    private function markPartialPaymentsAsPaid(int $scheduleId, string $paymentMethod, ?string $receiptPath = null, int $processedBy): int
    {
        try {
            $partialPayments = PartialPayment::where('schedule_payment_id', $scheduleId)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->get();

            if ($partialPayments->isEmpty()) {
                return 0;
            }

            $updatedCount = PartialPayment::where('schedule_payment_id', $scheduleId)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->update([
                    'status' => 'paid',
                    'approval_status' => 'approved',
                    'payment_method' => $paymentMethod,
                    'paid_at' => now(),
                    'receipt' => $receiptPath,
                    'processed_by' => $processedBy,
                ]);

            // Log partial payments update
            if ($updatedCount > 0) {
                $this->auditTrailService->log([
                    'event_category' => 'payment_operations',
                    'event_type' => 'partial_payments_auto_updated',
                    'entity_type' => 'PartialPayment',
                    'action_summary' => 'Auto-updated partial payments after schedule payment',
                    'properties' => [
                        'schedule_payment_id' => $scheduleId,
                        'partial_payments_updated' => $updatedCount,
                        'payment_method' => $paymentMethod,
                        'has_receipt' => !is_null($receiptPath),
                        'processed_by' => $processedBy,
                        'processed_at' => now()->toISOString()
                    ]
                ]);
            }

            return $updatedCount;
        } catch (\Exception $e) {
            Log::error('Failed to mark partial payments as paid', [
                'error' => $e->getMessage(),
                'schedule_payment_id' => $scheduleId,
                'processed_by' => $processedBy
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'partial_payments_update_failed',
                'entity_type' => 'PartialPayment',
                'action_summary' => 'Failed to auto-update partial payments',
                'properties' => [
                    'error' => $e->getMessage(),
                    'schedule_payment_id' => $scheduleId,
                    'attempted_by' => $processedBy
                ]
            ]);

            return 0;
        }
    }

    /**
     * Helper method to identify changed fields in schedule payment updates
     *
     * @param array $beforeState
     * @param array $afterState
     * @return array
     */
    private function getSchedulePaymentChangedFields(array $beforeState, array $afterState): array
    {
        $changed = [];
        $sensitiveFields = ['user_id', 'customer_id', 'assigned_to'];

        foreach ($beforeState as $key => $value) {
            if (isset($afterState[$key]) && $afterState[$key] != $value) {
                if (in_array($key, $sensitiveFields)) {
                    $changed[$key] = [
                        'old' => '***MASKED***',
                        'new' => '***MASKED***',
                        'changed' => true
                    ];
                } else {
                    $changed[$key] = [
                        'old' => $value,
                        'new' => $afterState[$key]
                    ];
                }
            }
        }

        // Check for new fields that weren't in before state
        foreach ($afterState as $key => $value) {
            if (!isset($beforeState[$key])) {
                if (in_array($key, $sensitiveFields)) {
                    $changed[$key] = [
                        'old' => null,
                        'new' => '***MASKED***'
                    ];
                } else {
                    $changed[$key] = [
                        'old' => null,
                        'new' => $value
                    ];
                }
            }
        }

        return $changed;
    }
}
