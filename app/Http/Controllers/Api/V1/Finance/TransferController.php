<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\TransferRequestResource;
use App\Models\TransferRequest;
use App\Services\AuditTrailService;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    protected const ALLOWED_TYPES = [
        'Order', 'Merchant', 'SupportTicket', 'SchedulePayment',
        'RefundRequest', 'Transaction', 'Customer',
    ];

    public function __construct(
        protected AuditTrailService $auditTrail,
        protected FirebaseService $firebase
    ) {}

    public function index(): JsonResponse
    {
        $query = TransferRequest::with(['fromUser', 'toUser', 'model']);

        if (! Auth::user()->hasRole('admin')) {
            $query->where('to_user_id', Auth::id());
        }

        $perPage = min((int) request('per_page', 10), 100);
        $transfers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TransferRequestResource::collection($transfers),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'model_type' => 'required|string|in:'.implode(',', self::ALLOWED_TYPES),
            'model_id' => 'required|integer',
            'description' => 'nullable|string|max:500',
        ]);

        $modelClass = 'App\\Models\\'.$validated['model_type'];
        $model = $modelClass::findOrFail($validated['model_id']);

        $transfer = DB::transaction(function () use ($validated, $model) {
            $transfer = TransferRequest::create([
                'from_user_id' => Auth::id(),
                'to_user_id' => $validated['to_user_id'],
                'model_type' => $validated['model_type'],
                'model_id' => $validated['model_id'],
                'description' => $validated['description'] ?? null,
                'status' => 'completed',
            ]);

            if ($model->is(\App\Models\Assignable::class) || method_exists($model, 'assigned')) {
                $model->update(['assigned_to' => $validated['to_user_id']]);
            }

            return $transfer;
        });

        $transfer->load(['fromUser', 'toUser']);

        return response()->json([
            'success' => true,
            'data' => new TransferRequestResource($transfer),
            'message' => 'Transfer completed',
        ], 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'model_type' => 'required|string|in:'.implode(',', self::ALLOWED_TYPES),
            'model_ids' => 'required|array',
            'model_ids.*' => 'integer',
            'description' => 'nullable|string|max:500',
        ]);

        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($validated['model_ids'] as $modelId) {
            try {
                $this->store(new Request([
                    'to_user_id' => $validated['to_user_id'],
                    'model_type' => $validated['model_type'],
                    'model_id' => $modelId,
                    'description' => $validated['description'] ?? null,
                ]));
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['model_id' => $modelId, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Transferred {$processed} items".($failed > 0 ? " ({$failed} failed)" : ''),
            'data' => compact('processed', 'failed', 'errors'),
        ]);
    }

    public function byModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_type' => 'required|string|in:'.implode(',', self::ALLOWED_TYPES),
            'model_id' => 'required|integer',
        ]);

        $transfers = TransferRequest::with(['fromUser', 'toUser'])
            ->where('model_type', $validated['model_type'])
            ->where('model_id', $validated['model_id'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => TransferRequestResource::collection($transfers),
        ]);
    }
}
