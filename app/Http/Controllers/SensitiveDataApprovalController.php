<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SensitiveDataApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Services\AuditTrailService;

class SensitiveDataApprovalController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Show paginated approvals
     */
    public function index(Request $request)
    {
        $approvals = SensitiveDataApproval::with(['requester', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Log view of approvals list
        $this->auditTrailService->log([
            'event_category' => 'data_access',
            'event_type' => 'sensitive_approvals_viewed',
            'entity_type' => 'SensitiveDataApproval',
            'action_summary' => 'Viewed sensitive data approvals list',
            'properties' => [
                'page' => $request->get('page', 1),
                'per_page' => 15,
                'viewed_by' => Auth::id(),
            ],
        ]);

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

        // Log view of specific approval
        $this->auditTrailService->log([
            'event_category' => 'data_access',
            'event_type' => 'sensitive_approval_viewed',
            'entity_type' => 'SensitiveDataApproval',
            'entity_id' => $approval->id,
            'action_summary' => "Viewed sensitive data approval request",
            'properties' => [
                'approval_id' => $approval->id,
                'requester_id' => $approval->requester_id,
                'status' => $approval->status,
                'viewed_by' => Auth::id(),
            ],
        ]);

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
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'decision_notes' => ['required', 'string', 'max:2000'],
        ]);

        // Get old data for audit trail
        $oldData = [
            'status' => $approval->status,
            'decision_notes' => $approval->decision_notes,
            'start_at' => $approval->start_at,
            'end_at' => $approval->end_at,
            'approved_by' => $approval->approved_by,
            'approved_at' => $approval->approved_at,
        ];

        // apply updates
        $approval->status = $validated['status'];
        $approval->decision_notes = $validated['decision_notes'] ?? null;
        $approval->start_at = isset($validated['start_at']) ? Carbon::parse($validated['start_at']) : $approval->start_at;
        $approval->end_at = isset($validated['end_at']) ? Carbon::parse($validated['end_at']) : $approval->end_at;

        // set approver + timestamp when approved/revoked/rejected
        $approval->approved_by = Auth::id();
        $approval->approved_at = Carbon::now();

        $approval->save();

        // Log the decision with justification
        $justificationData = $this->auditTrailService->withJustification(
            $validated['decision_notes'],
            'data_access_control',
            ['sensitive_permissions'] // PII fields involved
        );

        $this->auditTrailService->log([
            'event_category' => 'data_access',
            'event_type' => 'sensitive_approval_decision',
            'entity_type' => 'SensitiveDataApproval',
            'entity_id' => $approval->id,
            'action_summary' => "Made decision on sensitive data approval: {$validated['status']}",
            'before_state' => $oldData,
            'after_state' => [
                'status' => $approval->status,
                'decision_notes' => $approval->decision_notes,
                'start_at' => $approval->start_at,
                'end_at' => $approval->end_at,
                'approved_by' => $approval->approved_by,
                'approved_at' => $approval->approved_at,
            ],
            'properties' => [
                'approval_id' => $approval->id,
                'requester_id' => $approval->requester_id,
                'old_status' => $oldData['status'],
                'new_status' => $approval->status,
                'decision_by' => Auth::id(),
                'timeframe' => [
                    'start' => $approval->start_at,
                    'end' => $approval->end_at,
                ],
            ],
        ] + $justificationData);

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
