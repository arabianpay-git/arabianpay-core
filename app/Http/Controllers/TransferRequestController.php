<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('to_user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return view('admin.transfer_requests.index', compact('transferRequests'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Create transfer request
            $transferRequest = TransferRequest::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $request->to_user_id,
                'model_type' => $request->model_type,
                'model_id' => $request->model_id,
                'description' => $request->description,
                'status' => 'pending',
            ]);

            // Update assigned_to on the related model
            $modelClass = $request->model_type;
            $modelId = $request->model_id;

            if (class_exists($modelClass)) {
                $modelInstance = $modelClass::find($modelId);

                if ($modelInstance && in_array('assigned_to', $modelInstance->getFillable())) {
                    $modelInstance->assigned_to = $request->to_user_id;
                    $modelInstance->save();
                }
            }

            DB::commit();

            $title = 'New Assignment';
            $description = 'You have been assigned a new item.';

            if ($request->model_type === \App\Models\Merchant::class) {
                $title = 'New Supplier Assigned';
                $description = "A new supplier has been assigned to you. Please review their profile.";
                $clickAction = route('supplierProfile', ['id' => $modelId]);
            } else {
                $clickAction = route('suppliers');
            }

            $this->firebase->sendCustomNotification(
                $request->to_user_id,
                $title,
                $description,
                ['click_action' => $clickAction]
            );

            // Log the transfer request creation
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->logModelAction(
                event: 'create_transfer_request',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " created a transfer request for model: {$modelClass} with ID: {$modelId}",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => (string) Str::uuid(),
                    'transfer_request_id' => $transferRequest->id,
                    'model_type' => $modelClass,
                    'model_id' => $modelId,
                    'to_user_id' => $request->to_user_id,
                    'description' => $request->description,
                ],
            );

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
            'model_type'  => 'required|string', // Make sure to validate this too
        ]);

        DB::beginTransaction();
        try {
            $modelClass = $data['model_type'];
            $batch_uuid = (string) Str::uuid();
            foreach ($data['model_ids'] as $modelId) {
                // Create each TransferRequest
                TransferRequest::create([
                    'from_user_id' => Auth::id(),
                    'to_user_id'   => $data['to_user_id'],
                    'model_type'   => $modelClass,
                    'model_id'     => $modelId,
                    'description'  => $data['description'],
                    'status'       => 'pending',
                ]);

                // Update assigned_to on the related model dynamically
                if (class_exists($modelClass)) {
                    $modelInstance = $modelClass::find($modelId);

                    if ($modelInstance && in_array('assigned_to', $modelInstance->getFillable())) {
                        $modelInstance->assigned_to = $data['to_user_id'];
                        $modelInstance->save();
                    }
                }

                $title = 'New Assignment';
                $description = 'You have been assigned a new item.';

                if ($request->model_type === \App\Models\Merchant::class) {
                    $title = 'New Supplier Assigned';
                    $description = "A new supplier has been assigned to you. Please review their profile.";
                    $clickAction = route('supplierProfile', ['id' => $modelId]);
                } else {
                    $clickAction = route('suppliers');
                }

                $this->firebase->sendCustomNotification(
                    $request->to_user_id,
                    $title,
                    $description,
                    ['click_action' => $clickAction]
                );

                // Log the transfer request creation
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $user->logModelAction(
                    event: 'create_transfer_request',
                    description: Auth::user()->first_name . " " . Auth::user()->last_name . " created a bulk transfer request for model: {$modelClass} with ID: {$modelId}",
                    properties: [
                        'ip' => request()->ip(),
                        'batch_uuid' => $batch_uuid,
                        'model_type' => $modelClass,
                        'model_id' => $modelId,
                        'to_user_id' => $data['to_user_id'],
                        'description' => $data['description'],
                    ],
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
        $request->validate([
            'model_id' => 'required|integer',
            'model_type' => 'required|string',
        ]);

        $requests = TransferRequest::with('fromUser', 'toUser')
            ->where('model_id', $request->model_id)
            ->where('model_type', $request->model_type)
            ->latest()
            ->get();

        return response()->json(['data' => $requests->toArray()]);
    }
}
