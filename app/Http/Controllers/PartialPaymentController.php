<?php

namespace App\Http\Controllers;

use App\Models\PartialPayment;
use App\Models\SchedulePayment;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PartialPaymentController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'employee_id' => 'nullable|exists:users,id',
                'schedule_payment_id' => 'required|exists:schedule_payments,id',
                'partial_amount' => 'required|numeric|min:1',
                'partial_due_date' => 'required|date|after_or_equal:today',
                'details' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                // Log validation failure
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'partial_payment_validation_failed',
                    'entity_type' => 'PartialPayment',
                    'action_summary' => 'Partial payment creation failed validation',
                    'properties' => [
                        'validation_errors' => $validator->errors()->toArray(),
                        'schedule_payment_id' => $request->schedule_payment_id,
                        'user_id' => $request->user_id,
                        'partial_amount' => $request->partial_amount,
                        'attempted_by' => Auth::id(),
                        'ip_address' => $request->ip()
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ]);
            }

            $schedule = SchedulePayment::with(['order', 'user'])->findOrFail($request->schedule_payment_id);

            // Capture before state for audit
            $scheduleBeforeState = $schedule->toArray();

            // Total already paid in previous partial payments
            $totalPartialPaid = $schedule->partialPayments()->sum('partial_amount');

            // Calculate remaining amount including deducted_amount
            $remaining = $schedule->instalment_amount - ($schedule->deducted_amount + $totalPartialPaid);

            if ($request->partial_amount > $remaining) {
                // Log attempted overpayment
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'partial_payment_exceed_limit',
                    'entity_type' => 'SchedulePayment',
                    'entity_id' => $schedule->id,
                    'action_summary' => 'Attempted to create partial payment exceeding remaining amount',
                    'properties' => [
                        'schedule_payment_id' => $schedule->id,
                        'order_id' => $schedule->order_id,
                        'customer_id' => $schedule->user_id,
                        'attempted_amount' => $request->partial_amount,
                        'remaining_amount' => $remaining,
                        'instalment_amount' => $schedule->instalment_amount,
                        'deducted_amount' => $schedule->deducted_amount,
                        'total_partial_paid' => $totalPartialPaid,
                        'attempted_by' => Auth::id()
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'error' => "Partial amount cannot exceed remaining amount. Remaining limit: SAR " . number_format($remaining, 2)
                ]);
            }

            DB::beginTransaction();

            $partial = PartialPayment::create([
                'user_id' => $request->user_id,
                'employee_id' => $request->employee_id,
                'schedule_payment_id' => $request->schedule_payment_id,
                'partial_amount' => $request->partial_amount,
                'partial_due_date' => $request->partial_due_date,
                'details' => $request->details ? ['text' => $request->details] : null,
                'created_by' => Auth::id(),
            ]);

            // Update schedule payment deducted amount
            $newDeductedAmount = $schedule->deducted_amount + $request->partial_amount;
            $schedule->deducted_amount = $newDeductedAmount;

            // Update payment status if fully deducted
            if ($newDeductedAmount >= $schedule->instalment_amount) {
                $schedule->payment_status = 'paid';
                $schedule->paid_at = now();
            }

            $schedule->save();

            // Log partial payment creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Partial payment creation required for flexible payment arrangements and customer accommodation',
                'legitimate_interest',
                ['payment_amount', 'due_date', 'customer_id']
            );

            $this->auditTrailService->logCreated(
                $partial,
                'Created partial payment for schedule payment #' . $schedule->id,
                array_merge([
                    'event_category' => 'payment_operations',
                    'event_type' => 'partial_payment_created',
                    'entity_type' => 'PartialPayment',
                    'properties' => [
                        'partial_payment_id' => $partial->id,
                        'schedule_payment_id' => $schedule->id,
                        'order_id' => $schedule->order_id,
                        'customer_id' => $request->user_id,
                        'partial_amount' => $request->partial_amount,
                        'currency' => 'SAR',
                        'partial_due_date' => $request->partial_due_date,
                        'remaining_limit_before' => $remaining,
                        'remaining_limit_after' => $remaining - $request->partial_amount,
                        'new_deducted_amount' => $newDeductedAmount,
                        'instalment_amount' => $schedule->instalment_amount,
                        'payment_status_updated' => $schedule->payment_status,
                        'created_by' => Auth::id(),
                        'employee_id' => $request->employee_id,
                        'details' => $request->details ? substr($request->details, 0, 200) : null
                    ]
                ], $justificationData)
            );

            // Also log the schedule payment update
            $this->auditTrailService->logUpdated(
                $schedule,
                $scheduleBeforeState,
                'Updated schedule payment deducted amount after partial payment creation',
                [
                    'event_category' => 'payment_operations',
                    'event_type' => 'schedule_payment_updated',
                    'entity_type' => 'SchedulePayment',
                    'properties' => [
                        'schedule_payment_id' => $schedule->id,
                        'old_deducted_amount' => $scheduleBeforeState['deducted_amount'] ?? 0,
                        'new_deducted_amount' => $newDeductedAmount,
                        'partial_payment_id' => $partial->id,
                        'partial_amount' => $request->partial_amount,
                        'old_payment_status' => $scheduleBeforeState['payment_status'] ?? 'pending',
                        'new_payment_status' => $schedule->payment_status,
                        'updated_by' => Auth::id()
                    ]
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partial payment created successfully. Remaining limit now: SAR ' . number_format($remaining - $request->partial_amount, 2),
                'data' => [
                    'partial_payment_id' => $partial->id,
                    'new_deducted_amount' => $newDeductedAmount,
                    'remaining_amount' => $remaining - $request->partial_amount,
                    'payment_status' => $schedule->payment_status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create partial payment', [
                'error' => $e->getMessage(),
                'request_data' => $request->except(['details']), // Exclude long text from logs
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'partial_payment_creation_failed',
                'entity_type' => 'PartialPayment',
                'action_summary' => 'Failed to create partial payment',
                'properties' => [
                    'error' => $e->getMessage(),
                    'schedule_payment_id' => $request->schedule_payment_id ?? 'unknown',
                    'user_id' => $request->user_id ?? 'unknown',
                    'partial_amount' => $request->partial_amount ?? 0,
                    'attempted_by' => Auth::id(),
                    'ip_address' => $request->ip()
                ]
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create partial payment. Please try again.'
            ]);
        }
    }

    public function edit($id)
    {
        try {
            $partial = PartialPayment::with(['schedulePayment.order', 'user'])->findOrFail($id);

            // Log view of partial payment for editing
            $justificationData = $this->auditTrailService->withJustification(
                'Partial payment view required for administrative updates and payment management',
                'legitimate_interest',
                ['payment_amount', 'due_date']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'data_access',
                'event_type' => 'partial_payment_edit_view',
                'entity_type' => 'PartialPayment',
                'entity_id' => $partial->id,
                'action_summary' => 'Viewed partial payment for editing',
                'properties' => [
                    'partial_payment_id' => $partial->id,
                    'schedule_payment_id' => $partial->schedule_payment_id,
                    'order_id' => $partial->schedulePayment->order_id ?? null,
                    'customer_id' => $partial->user_id,
                    'partial_amount' => $partial->partial_amount,
                    'partial_due_date' => $partial->partial_due_date,
                    'viewed_by' => Auth::id(),
                    'viewed_by_type' => Auth::user()->user_type
                ]
            ], $justificationData));

            return response()->json(['success' => true, 'data' => $partial]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve partial payment for editing', [
                'error' => $e->getMessage(),
                'partial_payment_id' => $id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'partial_payment_edit_failed',
                'entity_type' => 'PartialPayment',
                'entity_id' => $id,
                'action_summary' => 'Failed to retrieve partial payment for editing',
                'properties' => [
                    'error' => $e->getMessage(),
                    'partial_payment_id' => $id,
                    'requested_by' => Auth::id()
                ]
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve partial payment details.'
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $partial = PartialPayment::with(['schedulePayment'])->findOrFail($id);

            // Capture before state for audit
            $beforeState = $partial->toArray();

            $validator = Validator::make($request->all(), [
                'partial_due_date' => 'required|date|after_or_equal:today',
                'details' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                // Log validation failure for update
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'partial_payment_update_validation_failed',
                    'entity_type' => 'PartialPayment',
                    'entity_id' => $partial->id,
                    'action_summary' => 'Partial payment update failed validation',
                    'properties' => [
                        'validation_errors' => $validator->errors()->toArray(),
                        'partial_payment_id' => $partial->id,
                        'schedule_payment_id' => $partial->schedule_payment_id,
                        'customer_id' => $partial->user_id,
                        'attempted_by' => Auth::id()
                    ]
                ]);

                return response()->json(['success' => false, 'error' => $validator->errors()->first()]);
            }

            DB::beginTransaction();

            $partial->update([
                'partial_due_date' => $request->partial_due_date,
                'details' => $request->details ? ['text' => $request->details] : null,
                'updated_by' => Auth::id(),
            ]);

            // Log partial payment update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Partial payment update required for payment term adjustments and customer accommodation',
                'legitimate_interest',
                ['due_date', 'payment_terms']
            );

            $this->auditTrailService->logUpdated(
                $partial,
                $beforeState,
                'Updated partial payment #' . $partial->id,
                array_merge([
                    'event_category' => 'payment_operations',
                    'event_type' => 'partial_payment_updated',
                    'entity_type' => 'PartialPayment',
                    'properties' => [
                        'partial_payment_id' => $partial->id,
                        'schedule_payment_id' => $partial->schedule_payment_id,
                        'customer_id' => $partial->user_id,
                        'old_due_date' => $beforeState['partial_due_date'],
                        'new_due_date' => $request->partial_due_date,
                        'old_details' => $beforeState['details'] ? substr(json_encode($beforeState['details']), 0, 200) : null,
                        'new_details' => $request->details ? substr($request->details, 0, 200) : null,
                        'partial_amount' => $partial->partial_amount,
                        'updated_by' => Auth::id(),
                        'changes_made' => $this->getPartialPaymentChangedFields($beforeState, $partial->toArray())
                    ]
                ], $justificationData)
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partial payment updated successfully.',
                'data' => [
                    'partial_payment_id' => $partial->id,
                    'new_due_date' => $partial->partial_due_date,
                    'updated_at' => $partial->updated_at
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update partial payment', [
                'error' => $e->getMessage(),
                'partial_payment_id' => $id,
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'partial_payment_update_failed',
                'entity_type' => 'PartialPayment',
                'entity_id' => $id,
                'action_summary' => 'Failed to update partial payment',
                'properties' => [
                    'error' => $e->getMessage(),
                    'partial_payment_id' => $id,
                    'requested_due_date' => $request->partial_due_date ?? 'not_provided',
                    'attempted_by' => Auth::id()
                ]
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update partial payment. Please try again.'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $partial = PartialPayment::with(['schedulePayment'])->findOrFail($id);

            // Capture before state for audit
            $beforeState = $partial->toArray();

            DB::beginTransaction();

            // Store schedule payment info before deletion
            $schedulePaymentId = $partial->schedule_payment_id;
            $partialAmount = $partial->partial_amount;
            $customerId = $partial->user_id;

            // Update schedule payment deducted amount before deleting partial payment
            $schedule = SchedulePayment::find($partial->schedule_payment_id);
            if ($schedule) {
                $scheduleBeforeState = $schedule->toArray();
                $schedule->deducted_amount = max(0, $schedule->deducted_amount - $partialAmount);

                // Revert payment status if needed
                if ($schedule->payment_status === 'paid' && $schedule->deducted_amount < $schedule->instalment_amount) {
                    $schedule->payment_status = 'pending';
                    $schedule->paid_at = null;
                }

                $schedule->save();
            }

            $partial->delete();

            // Log partial payment deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Partial payment deletion required for payment reconciliation and administrative corrections',
                'legal_obligation',
                ['payment_amount', 'customer_id']
            );

            $this->auditTrailService->logDeleted(
                $partial,
                'Deleted partial payment #' . $partial->id,
                array_merge([
                    'event_category' => 'payment_operations',
                    'event_type' => 'partial_payment_deleted',
                    'entity_type' => 'PartialPayment',
                    'properties' => [
                        'partial_payment_id' => $id,
                        'schedule_payment_id' => $schedulePaymentId,
                        'customer_id' => $customerId,
                        'deleted_amount' => $partialAmount,
                        'partial_due_date' => $beforeState['partial_due_date'],
                        'details' => $beforeState['details'] ? substr(json_encode($beforeState['details']), 0, 200) : null,
                        'deleted_by' => Auth::id(),
                        'deleted_at' => now()->toISOString(),
                        'schedule_payment_updated' => isset($schedule),
                        'new_deducted_amount' => $schedule->deducted_amount ?? null,
                        'new_payment_status' => $schedule->payment_status ?? null
                    ]
                ], $justificationData)
            );

            // Also log schedule payment update if applicable
            if (isset($schedule)) {
                $this->auditTrailService->logUpdated(
                    $schedule,
                    $scheduleBeforeState,
                    'Updated schedule payment deducted amount after partial payment deletion',
                    [
                        'event_category' => 'payment_operations',
                        'event_type' => 'schedule_payment_updated_after_deletion',
                        'entity_type' => 'SchedulePayment',
                        'properties' => [
                            'schedule_payment_id' => $schedule->id,
                            'partial_payment_id' => $id,
                            'deleted_partial_amount' => $partialAmount,
                            'old_deducted_amount' => $scheduleBeforeState['deducted_amount'] ?? 0,
                            'new_deducted_amount' => $schedule->deducted_amount,
                            'old_payment_status' => $scheduleBeforeState['payment_status'] ?? 'pending',
                            'new_payment_status' => $schedule->payment_status,
                            'updated_by' => Auth::id(),
                            'reason' => 'Partial payment deletion'
                        ]
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partial payment deleted successfully.',
                'data' => [
                    'deleted_id' => $id,
                    'deleted_amount' => $partialAmount,
                    'schedule_payment_updated' => isset($schedule),
                    'new_deducted_amount' => $schedule->deducted_amount ?? null
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete partial payment', [
                'error' => $e->getMessage(),
                'partial_payment_id' => $id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'partial_payment_deletion_failed',
                'entity_type' => 'PartialPayment',
                'entity_id' => $id,
                'action_summary' => 'Failed to delete partial payment',
                'properties' => [
                    'error' => $e->getMessage(),
                    'partial_payment_id' => $id,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete partial payment. Please try again.'
            ]);
        }
    }

    /**
     * Helper method to identify changed fields in partial payment updates
     *
     * @param array $beforeState
     * @param array $afterState
     * @return array
     */
    private function getPartialPaymentChangedFields(array $beforeState, array $afterState): array
    {
        $changed = [];

        $fieldsToCheck = ['partial_due_date', 'details', 'updated_by', 'updated_at'];

        foreach ($fieldsToCheck as $field) {
            if (isset($beforeState[$field], $afterState[$field]) && $beforeState[$field] != $afterState[$field]) {
                if ($field === 'details') {
                    $changed[$field] = [
                        'old' => $beforeState[$field] ? '***EXISTS***' : null,
                        'new' => $afterState[$field] ? '***EXISTS***' : null
                    ];
                } else {
                    $changed[$field] = [
                        'old' => $beforeState[$field],
                        'new' => $afterState[$field]
                    ];
                }
            } elseif (!isset($beforeState[$field]) && isset($afterState[$field])) {
                $changed[$field] = [
                    'old' => null,
                    'new' => $afterState[$field]
                ];
            }
        }

        return $changed;
    }
}
