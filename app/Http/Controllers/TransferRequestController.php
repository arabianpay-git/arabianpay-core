<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Models\TransferRequest;
use App\Services\AuditTrailService;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransferRequestController extends Controller
{
    protected $firebase;

    protected $auditTrailService;

    public function __construct(FirebaseService $firebase, AuditTrailService $auditTrailService)
    {
        $this->firebase = $firebase;
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        $user = currentUser();
        $transferRequests = TransferRequest::with(['fromUser', 'toUser', 'model'])
            ->when($user->user_type !== 'admin', fn ($query) => $query->where('to_user_id', $user->id))
            ->latest()
            ->paginate(10);

        // Log view of transfer requests
        $this->auditTrailService->log([
            'event_category' => 'transfer_requests',
            'event_type' => 'view_list',
            'entity_type' => 'TransferRequest',
            'action_summary' => 'Viewed transfer requests list',
            'properties' => [
                'user_type' => $user->user_type,
                'viewed_by' => Auth::id(),
            ],
        ]);

        return view('admin.transfer_requests.index', compact('transferRequests'));
    }

    public function store(StoreTransferRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $transferRequest = TransferRequest::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $data['to_user_id'],
                'model_type' => $data['model_type'],
                'model_id' => $data['model_id'],
                'description' => $data['description'],
                'status' => 'pending',
            ]);

            $this->updateModelAssignedTo($data['model_type'], $data['model_id'], $data['to_user_id']);

            $this->createNotificationAndSendFirebase(
                $data['to_user_id'],
                $data['model_type'],
                $data['model_id'],
                $data['description'],
                $transferRequest->id
            );

            $this->logTransferRequestCreation(
                Auth::user(),
                $transferRequest->id,
                $data['model_type'],
                $data['model_id'],
                $data['to_user_id'],
                $data['description']
            );

            // Add audit trail for single transfer request creation
            $this->auditTrailService->log([
                'event_category' => 'transfer_requests',
                'event_type' => 'single_transfer_created',
                'entity_type' => 'TransferRequest',
                'entity_id' => $transferRequest->id,
                'action_summary' => 'Created single transfer request',
                'properties' => [
                    'from_user_id' => Auth::id(),
                    'to_user_id' => $data['to_user_id'],
                    'model_type' => $data['model_type'],
                    'model_id' => $data['model_id'],
                    'description_length' => strlen($data['description'] ?? ''),
                ],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Transfer request sent successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed transfer request creation
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'single_transfer_failed',
                'entity_type' => 'TransferRequest',
                'action_summary' => 'Failed to create single transfer request',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'model_type' => $data['model_type'] ?? null,
                    'model_id' => $data['model_id'] ?? null,
                    'to_user_id' => $data['to_user_id'] ?? null,
                ],
            ]);

            Log::error('Transfer request creation failed', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            return redirect()->back()->with('error', __('Transfer failed. Please try again or contact support.'));
        }
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'model_ids' => 'required|array|min:1',
            'model_ids.*' => 'integer',
            'description' => 'nullable|string',
            'model_type' => 'required|in:App\Models\Order,App\Models\Merchant,App\Models\SupportTicket,App\Models\SchedulePayment,App\Models\RefundRequest,App\Models\Transaction,App\Models\Customer',
        ]);

        DB::beginTransaction();

        try {
            $batch_uuid = (string) Str::uuid();
            $createdRequests = [];
            $failedRequests = [];

            // Log bulk transfer initiation
            $this->auditTrailService->log([
                'event_category' => 'transfer_requests',
                'event_type' => 'bulk_transfer_initiated',
                'entity_type' => 'TransferRequest',
                'action_summary' => "Initiated bulk transfer of {$data['model_type']}",
                'properties' => [
                    'from_user_id' => Auth::id(),
                    'to_user_id' => $data['to_user_id'],
                    'model_type' => $data['model_type'],
                    'total_models' => count($data['model_ids']),
                    'batch_uuid' => $batch_uuid,
                    'description_length' => strlen($data['description'] ?? ''),
                ],
            ]);

            foreach ($data['model_ids'] as $modelId) {
                try {
                    $transferRequest = TransferRequest::create([
                        'from_user_id' => Auth::id(),
                        'to_user_id' => $data['to_user_id'],
                        'model_type' => $data['model_type'],
                        'model_id' => $modelId,
                        'description' => $data['description'],
                        'status' => 'pending',
                    ]);

                    $this->updateModelAssignedTo($data['model_type'], $modelId, $data['to_user_id']);

                    $this->createNotificationAndSendFirebase(
                        $data['to_user_id'],
                        $data['model_type'],
                        $modelId,
                        $data['description'],
                        $transferRequest->id
                    );

                    $this->logTransferRequestCreation(
                        Auth::user(),
                        $transferRequest->id,
                        $data['model_type'],
                        $modelId,
                        $data['to_user_id'],
                        $data['description'],
                        $batch_uuid
                    );

                    // Add audit trail for each successful transfer in bulk
                    $this->auditTrailService->log([
                        'event_category' => 'transfer_requests',
                        'event_type' => 'bulk_item_transferred',
                        'entity_type' => 'TransferRequest',
                        'entity_id' => $transferRequest->id,
                        'action_summary' => 'Transferred item in bulk operation',
                        'properties' => [
                            'from_user_id' => Auth::id(),
                            'to_user_id' => $data['to_user_id'],
                            'model_type' => $data['model_type'],
                            'model_id' => $modelId,
                            'batch_uuid' => $batch_uuid,
                            'transfer_request_id' => $transferRequest->id,
                        ],
                    ]);

                    $createdRequests[] = $transferRequest->id;
                } catch (\Exception $itemException) {
                    $failedRequests[] = [
                        'model_id' => $modelId,
                        'error' => $itemException->getMessage(),
                    ];

                    // Log individual item failure in bulk
                    $this->auditTrailService->log([
                        'event_category' => 'error_events',
                        'event_type' => 'bulk_item_failed',
                        'entity_type' => 'TransferRequest',
                        'action_summary' => 'Failed to transfer item in bulk operation',
                        'properties' => [
                            'model_type' => $data['model_type'],
                            'model_id' => $modelId,
                            'to_user_id' => $data['to_user_id'],
                            'error_message' => $itemException->getMessage(),
                            'batch_uuid' => $batch_uuid,
                        ],
                    ]);
                }
            }

            // Log bulk transfer completion
            $this->auditTrailService->log([
                'event_category' => 'transfer_requests',
                'event_type' => 'bulk_transfer_completed',
                'entity_type' => 'TransferRequest',
                'action_summary' => 'Completed bulk transfer operation',
                'properties' => [
                    'from_user_id' => Auth::id(),
                    'to_user_id' => $data['to_user_id'],
                    'model_type' => $data['model_type'],
                    'batch_uuid' => $batch_uuid,
                    'successful_transfers' => count($createdRequests),
                    'failed_transfers' => count($failedRequests),
                    'total_attempted' => count($data['model_ids']),
                ],
            ]);

            DB::commit();

            if (count($failedRequests) > 0) {
                return back()->with('warning', __('Bulk transfer completed with some failures. Successful: ').count($createdRequests).', Failed: '.count($failedRequests));
            }

            return back()->with('success', __('Bulk transfer successful.'));
        } catch (\Throwable $e) {
            DB::rollBack();

            // Log bulk transfer failure
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'bulk_transfer_failed',
                'entity_type' => 'TransferRequest',
                'action_summary' => 'Bulk transfer failed',
                'properties' => [
                    'model_type' => $data['model_type'] ?? null,
                    'to_user_id' => $data['to_user_id'] ?? null,
                    'total_models' => count($data['model_ids'] ?? []),
                    'error_message' => $e->getMessage(),
                ],
            ]);

            Log::error('Bulk transfer failed', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            return back()->with('error', __('Bulk transfer failed. Please try again or contact support.'));
        }
    }

    public function fetch(Request $request)
    {
        $data = $request->validate([
            'model_id' => 'required|integer',
            'model_type' => 'required|string',
        ]);

        $requests = TransferRequest::with('fromUser', 'toUser')
            ->where('model_id', $data['model_id'])
            ->where('model_type', $data['model_type'])
            ->latest()
            ->get();

        // Log fetch of transfer requests
        $this->auditTrailService->log([
            'event_category' => 'transfer_requests',
            'event_type' => 'fetch_transfers',
            'entity_type' => 'TransferRequest',
            'action_summary' => 'Fetched transfer requests for model',
            'properties' => [
                'model_type' => $data['model_type'],
                'model_id' => $data['model_id'],
                'fetched_by' => Auth::id(),
                'results_count' => $requests->count(),
            ],
        ]);

        return response()->json(['data' => $requests->toArray()]);
    }

    // ---------- Helper private methods ----------

    private function updateModelAssignedTo(string $modelType, int $modelId, int $toUserId): void
    {
        if (class_exists($modelType)) {
            $modelInstance = $modelType::find($modelId);
            if ($modelInstance && in_array('assigned_to', $modelInstance->getFillable())) {
                $modelInstance->assigned_to = $toUserId;
                $modelInstance->save();

                // Log model assignment update
                $this->auditTrailService->log([
                    'event_category' => 'model_assignment',
                    'event_type' => 'assigned_to_updated',
                    'entity_type' => $modelType,
                    'entity_id' => $modelId,
                    'action_summary' => 'Updated assigned_to field for model',
                    'properties' => [
                        'model_type' => $modelType,
                        'model_id' => $modelId,
                        'assigned_to' => $toUserId,
                        'updated_by' => Auth::id(),
                    ],
                ]);
            }
        }
    }

    private function createNotificationAndSendFirebase(
        int $toUserId,
        string $modelType,
        int $modelId,
        ?string $description,
        int $transferRequestId
    ): void {
        $title = 'New Assignment';
        $desc = 'You have been assigned a new item.';
        $clickAction = route('suppliers');

        if ($modelType === \App\Models\Merchant::class) {
            $title = 'New Supplier Assigned';
            $desc = 'A new supplier has been assigned to you. Please review their profile.';
            $clickAction = route('supplierProfile', ['id' => $modelId]);
        }

        // Create database notification (using your helper function)
        create_notification(
            $toUserId,
            'transfer_request_created',
            [
                'title' => $title,
                'description' => $desc,
                'transfer_request_id' => $transferRequestId,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'note' => $description ?? '',
                'click_action' => $clickAction,
            ]
        );

        // Send Firebase notification
        $this->firebase->sendCustomNotification(
            $toUserId,
            $title,
            $desc,
            ['click_action' => $clickAction]
        );

        // Log notification creation
        $this->auditTrailService->log([
            'event_category' => 'notifications',
            'event_type' => 'transfer_notification_sent',
            'entity_type' => 'TransferRequest',
            'entity_id' => $transferRequestId,
            'action_summary' => 'Sent notification for transfer request',
            'properties' => [
                'transfer_request_id' => $transferRequestId,
                'to_user_id' => $toUserId,
                'notification_title' => $title,
                'notification_type' => $modelType === \App\Models\Merchant::class ? 'supplier_assignment' : 'general_assignment',
                'firebase_sent' => true,
            ],
        ]);
    }

    private function logTransferRequestCreation(
        $user,
        int $transferRequestId,
        string $modelType,
        int $modelId,
        int $toUserId,
        ?string $description,
        ?string $batchUuid = null
    ): void {
        // Original logging method (keep as is)
        $user->logModelAction(
            event: 'create_transfer_request',
            description: $user->first_name.' '.$user->last_name." created a transfer request for model: {$modelType} with ID: {$modelId}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => $batchUuid ?? (string) Str::uuid(),
                'transfer_request_id' => $transferRequestId,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'to_user_id' => $toUserId,
                'description' => $description,
            ],
        );
    }
}
