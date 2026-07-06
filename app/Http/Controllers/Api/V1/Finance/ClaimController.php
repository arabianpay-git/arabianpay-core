<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\ClaimResource;
use App\Models\Claim;
use App\Models\SchedulePayment;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrail
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Claim::with(['schedulePayment', 'user', 'assignedTo']);

        if ($request->filled('status')) {
            $query->where('claim_status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->boolean('escalation')) {
            $query->requiresEscalation();
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $claims = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ClaimResource::collection($claims),
            'meta' => [
                'current_page' => $claims->currentPage(),
                'last_page' => $claims->lastPage(),
                'per_page' => $claims->perPage(),
                'total' => $claims->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_payment_id' => 'required|exists:schedule_payments,id',
            'claim_type' => 'required|in:call,sms,email,whatsapp,visit,letter,legal',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
            'next_follow_up' => 'nullable|date',
        ]);

        $claim = Claim::create($validated + ['claim_status' => 'pending']);
        $claim->load(['schedulePayment', 'user', 'assignedTo']);

        return response()->json([
            'success' => true,
            'data' => new ClaimResource($claim),
            'message' => 'Claim created',
        ], 201);
    }

    public function show(Claim $claim): JsonResponse
    {
        $claim->load(['schedulePayment.checkout.user', 'assignedTo']);

        return response()->json([
            'success' => true,
            'data' => new ClaimResource($claim),
        ]);
    }

    public function attempt(Request $request, Claim $claim): JsonResponse
    {
        $validated = $request->validate([
            'contact_method' => 'required|string|max:50',
            'customer_response' => 'nullable|string',
            'promised_payment_date' => 'nullable|date',
            'promised_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $claim->increment('attempt_count');
        $claim->fill([
            'contact_method' => $validated['contact_method'],
            'customer_response' => $validated['customer_response'] ?? null,
            'promised_payment_date' => $validated['promised_payment_date'] ?? null,
            'promised_amount' => $validated['promised_amount'] ?? null,
            'notes' => $validated['notes'] ?? $claim->notes,
            'attempted_at' => now(),
            'claim_status' => $validated['customer_response'] ? 'contacted' : 'attempted',
        ]);

        if ($validated['promised_payment_date']) {
            $claim->next_follow_up = $validated['promised_payment_date'];
        }

        $claim->save();

        return response()->json([
            'success' => true,
            'data' => new ClaimResource($claim->fresh()->load(['schedulePayment', 'assignedTo'])),
            'message' => 'Claim attempt recorded',
        ]);
    }

    public function resolve(Claim $claim): JsonResponse
    {
        $claim->update(['claim_status' => 'resolved']);

        return response()->json([
            'success' => true,
            'data' => new ClaimResource($claim->fresh()->load(['schedulePayment', 'assignedTo'])),
            'message' => 'Claim resolved',
        ]);
    }

    public function escalate(Request $request, Claim $claim): JsonResponse
    {
        $validated = $request->validate([
            'escalation_reason' => 'required|string|max:1000',
        ]);

        $claim->escalate($validated['escalation_reason']);

        return response()->json([
            'success' => true,
            'data' => new ClaimResource($claim->fresh()->load(['schedulePayment', 'assignedTo'])),
            'message' => 'Claim escalated',
        ]);
    }

    public function bySchedulePayment(SchedulePayment $schedulePayment): JsonResponse
    {
        $claims = Claim::with(['assignedTo'])
            ->where('schedule_payment_id', $schedulePayment->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => ClaimResource::collection($claims),
        ]);
    }
}
