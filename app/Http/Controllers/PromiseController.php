<?php

namespace App\Http\Controllers;

use App\Models\Promise;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PromiseController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedule_payment_id' => 'required|exists:schedule_payments,id',
            'method' => 'required|string|in:call,email',
            'promise_date' => 'required|date|after_or_equal:today'
        ]);

        // Check if a promise already exists for this schedule_payment_id
        $existing = Promise::where('schedule_payment_id', $request->schedule_payment_id)->first();
        if ($existing) {
            // Log duplicate promise attempt
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'duplicate_promise_attempt',
                'entity_type' => 'Promise',
                'entity_id' => $request->schedule_payment_id,
                'action_summary' => 'Attempted to create duplicate promise for schedule payment',
                'properties' => [
                    'schedule_payment_id' => $request->schedule_payment_id,
                    'user_id' => $request->user_id,
                    'method' => $request->method,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A promise already exists for this installment.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create the promise
            $promise = Promise::create([
                'user_id' => $request->user_id,
                'employee_id' => Auth::id(),
                'schedule_payment_id' => $request->schedule_payment_id,
                'promise_date' => $request->promise_date,
                'method' => $request->method,
            ]);

            // Log promise creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Promise created for payment arrangement',
                'business_operation',
                [] // No PII fields
            );

            $this->auditTrailService->logCreated(
                $promise,
                "Created payment promise for schedule payment ID: {$request->schedule_payment_id}",
                array_merge([
                    'user_id' => $request->user_id,
                    'employee_id' => Auth::id(),
                    'schedule_payment_id' => $request->schedule_payment_id,
                    'promise_date' => $request->promise_date,
                    'method' => $request->method,
                    'employee_name' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                ], $justificationData)
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Promise recorded successfully."
                ]);
            }

            return back()->with('success', "Promise recorded successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed promise creation
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'promise_creation_failed',
                'entity_type' => 'Promise',
                'action_summary' => 'Failed to create payment promise',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'schedule_payment_id' => $request->schedule_payment_id,
                    'user_id' => $request->user_id,
                    'method' => $request->method,
                ],
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record promise: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to record promise: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Promise $promise)
    {
        $request->validate([
            'promise_date' => 'required|date|after_or_equal:today',
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $promise->toArray();
            $oldDate = $promise->promise_date;

            // Update the promise
            $promise->promise_date = $request->promise_date;
            $promise->save();

            // Log promise update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Promise date updated due to customer request',
                'data_correction',
                [] // No PII fields
            );

            $this->auditTrailService->logUpdated(
                $promise,
                $oldData,
                "Updated promise date for schedule payment ID: {$promise->schedule_payment_id}",
                array_merge([
                    'old_promise_date' => $oldDate,
                    'new_promise_date' => $request->promise_date,
                    'schedule_payment_id' => $promise->schedule_payment_id,
                    'user_id' => $promise->user_id,
                    'employee_id' => Auth::id(),
                    'employee_name' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                ], $justificationData)
            );

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Promise updated successfully.']);
            }

            return back()->with('success', 'Promise updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed promise update
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'promise_update_failed',
                'entity_type' => 'Promise',
                'entity_id' => $promise->id,
                'action_summary' => "Failed to update promise for schedule payment ID: {$promise->schedule_payment_id}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'schedule_payment_id' => $promise->schedule_payment_id,
                    'promise_id' => $promise->id,
                ],
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update promise: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update promise: ' . $e->getMessage());
        }
    }

    public function destroy(Promise $promise)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $promiseData = $promise->toArray();

            // Log promise deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Promise removed as payment arrangement is no longer valid',
                'data_cleanup',
                [] // No PII fields
            );

            $this->auditTrailService->logDeleted(
                $promise,
                "Deleted promise for schedule payment ID: {$promise->schedule_payment_id}",
                array_merge([
                    'schedule_payment_id' => $promise->schedule_payment_id,
                    'user_id' => $promise->user_id,
                    'promise_date' => $promise->promise_date,
                    'method' => $promise->method,
                    'employee_id' => Auth::id(),
                    'employee_name' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                ], $justificationData)
            );

            $promise->delete();

            DB::commit();

            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Promise deleted successfully.']);
            }

            return back()->with('success', 'Promise deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed promise deletion
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'promise_deletion_failed',
                'entity_type' => 'Promise',
                'entity_id' => $promise->id,
                'action_summary' => "Failed to delete promise for schedule payment ID: {$promise->schedule_payment_id}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'schedule_payment_id' => $promise->schedule_payment_id,
                    'promise_id' => $promise->id,
                ],
            ]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete promise: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete promise: ' . $e->getMessage());
        }
    }
}
