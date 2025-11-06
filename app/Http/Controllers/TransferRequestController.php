<?php

namespace App\Http\Controllers;

use App\Models\TransferRequest;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferRequestController extends Controller
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function index()
    {
        $user = currentUser();
        $transferRequests = TransferRequest::with(['fromUser', 'toUser', 'model'])
            ->when($user->user_type !== 'admin', fn($query) => $query->where('to_user_id', $user->id))
            ->latest()
            ->paginate(10);

        return view('admin.transfer_requests.index', compact('transferRequests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'to_user_id'  => 'required|exists:users,id',
            'model_type'  => 'required|string',
            'model_id'    => 'required|integer',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $transferRequest = TransferRequest::create([
                'from_user_id' => Auth::id(),
                'to_user_id'   => $data['to_user_id'],
                'model_type'   => $data['model_type'],
                'model_id'     => $data['model_id'],
                'description'  => $data['description'],
                'status'       => 'pending',
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

            DB::commit();

            return redirect()->back()->with('success', 'Transfer request sent successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'to_user_id'  => 'required|exists:users,id',
            'model_ids'   => 'required|array|min:1',
            'model_ids.*' => 'integer',
            'description' => 'nullable|string',
            'model_type'  => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $batch_uuid = (string) Str::uuid();

            foreach ($data['model_ids'] as $modelId) {
                $transferRequest = TransferRequest::create([
                    'from_user_id' => Auth::id(),
                    'to_user_id'   => $data['to_user_id'],
                    'model_type'   => $data['model_type'],
                    'model_id'     => $modelId,
                    'description'  => $data['description'],
                    'status'       => 'pending',
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
            }

            DB::commit();

            return back()->with('success', __('Bulk transfer successful.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Bulk transfer failed: ') . $e->getMessage());
        }
    }

    public function fetch(Request $request)
    {
        $data = $request->validate([
            'model_id'   => 'required|integer',
            'model_type' => 'required|string',
        ]);

        $requests = TransferRequest::with('fromUser', 'toUser')
            ->where('model_id', $data['model_id'])
            ->where('model_type', $data['model_type'])
            ->latest()
            ->get();

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
            $desc = "A new supplier has been assigned to you. Please review their profile.";
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
        $user->logModelAction(
            event: 'create_transfer_request',
            description: $user->first_name . " " . $user->last_name . " created a transfer request for model: {$modelType} with ID: {$modelId}",
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
