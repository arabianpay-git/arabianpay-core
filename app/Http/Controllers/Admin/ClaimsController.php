<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\SchedulePayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClaimsController extends Controller
{
    /**
     * Display a listing of the claims.
     */
    public function index(Request $request)
    {
        $query = Claim::with(['schedulePayment.checkout.user', 'assignedTo']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('claim_status', $request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by assigned staff
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter overdue claims
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Filter requiring escalation
        if ($request->boolean('escalation')) {
            $query->requiresEscalation();
        }

        $claims = $query->latest()->paginate(20);

        return view('admin.claims.index', compact('claims'));
    }

    /**
     * Show the form for creating a new claim.
     */
    public function create(Request $request)
    {
        $schedulePayment = null;
        if ($request->filled('schedule_payment_id')) {
            $schedulePayment = SchedulePayment::with(['checkout.user'])->findOrFail($request->schedule_payment_id);
        }

        return view('admin.claims.create', compact('schedulePayment'));
    }

    /**
     * Store a newly created claim in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_payment_id' => 'required|exists:schedule_payments,id',
            'claim_type' => 'required|in:call,sms,email,whatsapp,visit,letter,legal',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
            'next_follow_up' => 'nullable|date|after:now',
        ]);

        $schedulePayment = SchedulePayment::findOrFail($request->schedule_payment_id);

        $claim = Claim::create([
            'schedule_payment_id' => $schedulePayment->id,
            'user_id' => $schedulePayment->user_id,
            'assigned_to' => Auth::id(),
            'claim_type' => $request->claim_type,
            'priority' => $request->priority,
            'claim_status' => 'pending',
            'notes' => $request->notes,
            'next_follow_up' => $request->next_follow_up ? Carbon::parse($request->next_follow_up) : now()->addHours(2),
        ]);

        

        return response()->json([
            'success' => true,
            'message' => 'Claim created successfully',
            'claim' => $claim->load(['schedulePayment', 'user', 'assignedTo'])
        ]);
    }

    /**
     * Display the specified claim.
     */
    public function show(Claim $claim)
    {
        $claim->load(['schedulePayment.checkout.user', 'assignedTo']);
        return view('admin.claims.show', compact('claim'));
    }

    /**
     * Update claim with communication attempt
     */
    public function updateAttempt(Request $request, Claim $claim): JsonResponse
    {
        $request->validate([
            'customer_response' => 'required|in:no_answer,answered,busy,declined,promised,disputed,paid',
            'notes' => 'nullable|string',
            'promised_payment_date' => 'nullable|date',
            'promised_amount' => 'nullable|numeric|min:0',
            'next_follow_up' => 'nullable|date',
        ]);

        $updateData = [
            'attempted_at' => now(),
            'customer_response' => $request->customer_response,
            'notes' => $request->notes,
            'attempt_count' => $claim->attempt_count + 1,
        ];

        // If customer was reached
        if (in_array($request->customer_response, ['answered', 'promised', 'disputed'])) {
            $updateData['contacted_at'] = now();
            $updateData['claim_status'] = $request->customer_response === 'promised' ? 'promised' : 'contacted';
        } else {
            $updateData['claim_status'] = 'attempted';
        }

        // Handle promises
        if ($request->customer_response === 'promised') {
            $updateData['promised_payment_date'] = $request->promised_payment_date;
            $updateData['promised_amount'] = $request->promised_amount;
        }

        // Set next follow-up
        if ($request->next_follow_up) {
            $updateData['next_follow_up'] = Carbon::parse($request->next_follow_up);
        }

        $claim->update($updateData);

     

        return response()->json([
            'success' => true,
            'message' => 'Claim updated successfully',
            'claim' => $claim->fresh()
        ]);
    }

    /**
     * Mark claim as resolved
     */
    public function resolve(Claim $claim): JsonResponse
    {
        $claim->update([
            'claim_status' => 'resolved',
            'next_follow_up' => null,
        ]);

        $claim->addCommunicationLog('resolved', [
            'action' => 'Claim resolved',
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Claim marked as resolved'
        ]);
    }

    /**
     * Escalate claim
     */
    public function escalate(Request $request, Claim $claim): JsonResponse
    {
        $request->validate([
            'escalation_reason' => 'required|string',
        ]);

        $claim->escalate($request->escalation_reason);

        $claim->addCommunicationLog('escalated', [
            'reason' => $request->escalation_reason,
            'user' => Auth::user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Claim escalated successfully'
        ]);
    }

    /**
     * Get claims for a specific schedule payment
     */
    public function forSchedulePayment(SchedulePayment $schedulePayment): JsonResponse
    {
        $claims = $schedulePayment->claims()
            ->with(['assignedTo'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'claims' => $claims
        ]);
    }

    /**
     * Show schedule payment details with claims
     */
    public function showSchedulePayment(SchedulePayment $schedulePayment)
    {
        $schedulePayment->load([
            'checkout.user',
            'checkout.investmentPool',
            'payment',
            'claims.assignedTo'
        ]);

        return view('admin.schedule-payment.show', compact('schedulePayment'));
    }
}
