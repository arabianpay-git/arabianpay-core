<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SensitiveDataApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class SensitiveDataApprovalController extends Controller
{
    /**
     * Show paginated approvals
     */
    public function index(Request $request)
    {
        $approvals = SensitiveDataApproval::with(['requester', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.approvals.index', compact('approvals'));
    }

    /**
     * Return approval JSON (for modal)
     */
    public function show(SensitiveDataApproval $approval)
    {
        $approval->load(['requester', 'approver']);

        // ensure sensitive_permissions is array
        $approval->sensitive_permissions = $approval->sensitive_permissions ?? [];

        return response()->json([
            'success' => true,
            'data' => $approval,
        ]);
    }

    /**
     * Admin decision: approve/reject/revoke with optional timeframe and notes
     */
    public function decision(Request $request, SensitiveDataApproval $approval)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,revoked'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // apply updates
        $approval->status = $validated['status'];
        $approval->decision_notes = $validated['decision_notes'] ?? null;
        $approval->start_at = isset($validated['start_at']) ? Carbon::parse($validated['start_at']) : $approval->start_at;
        $approval->end_at = isset($validated['end_at']) ? Carbon::parse($validated['end_at']) : $approval->end_at;

        // set approver + timestamp when approved/revoked/rejected
        $approval->approved_by = Auth::id();
        $approval->approved_at = Carbon::now();

        $approval->save();

        return response()->json([
            'success' => true,
            'message' => __('Approval updated successfully.'),
            'data' => [
                'id' => $approval->id,
                'status' => $approval->status,
            ],
        ]);
    }
}
