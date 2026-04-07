<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\Privacy\DataSubjectRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PDPLController extends Controller
{
    public function __construct(
        private DataSubjectRequestService $dsrService,
        private AuditTrailService $auditTrailService,
    ) {}

    /**
     * List all data subject requests with filters.
     */
    public function index(Request $request)
    {
        $query = DataSubjectRequest::with(['user', 'requester', 'reviewer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('request_type', $request->type);
        }

        if ($request->filled('overdue') && $request->overdue === '1') {
            $query->overdue();
        }

        $requests = $query->paginate(15);
        $overdueCount = DataSubjectRequest::overdue()->count();
        $pendingCount = DataSubjectRequest::pending()->count();

        try {
            $this->auditTrailService->logViewOperation(
                'pdpl_requests_list',
                'DataSubjectRequest',
                'Viewed PDPL requests list',
                ['filter_status' => $request->status, 'filter_type' => $request->type]
            );
        } catch (\Throwable $e) {
            // Audit logging must not block the main request
        }

        return view('admin.pdpl.index', compact(
            'requests', 'overdueCount', 'pendingCount'
        ));
    }

    /**
     * Show a single data subject request.
     */
    public function show(DataSubjectRequest $dataSubjectRequest)
    {
        $dataSubjectRequest->load(['user', 'requester', 'reviewer']);

        try {
            $this->auditTrailService->logViewOperation(
                'pdpl_request_detail',
                'DataSubjectRequest',
                'Viewed PDPL request #' . $dataSubjectRequest->uuid,
                ['request_id' => $dataSubjectRequest->id]
            );
        } catch (\Throwable $e) {
            // Audit logging must not block the main request
        }

        return view('admin.pdpl.show', [
            'dsr' => $dataSubjectRequest,
        ]);
    }

    /**
     * Create a new data subject request (admin-initiated on behalf of user).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'request_type' => 'required|in:' . implode(',', array_column(DataRequestType::cases(), 'value')),
            'description' => 'required|string|max:2000',
            'affected_data' => 'nullable|array',
        ]);

        $subject = User::findOrFail($validated['user_id']);

        $dsr = $this->dsrService->createRequest(
            subject: $subject,
            type: DataRequestType::from($validated['request_type']),
            description: $validated['description'],
            requestedBy: Auth::user(),
            affectedData: $validated['affected_data'] ?? null,
        );

        return redirect()->route('pdpl.requests.show', $dsr)
            ->with('success', 'Data subject request created. Deadline: ' . $dsr->deadline_at->format('M d, Y'));
    }

    /**
     * Approve a pending request.
     */
    public function approve(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $this->dsrService->reviewRequest(
            $dataSubjectRequest,
            Auth::user(),
            DataRequestStatus::Approved,
            $request->admin_notes,
        );

        return back()->with('success', 'Request approved.');
    }

    /**
     * Reject a pending request.
     */
    public function reject(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:2000',
        ]);

        $this->dsrService->reviewRequest(
            $dataSubjectRequest,
            Auth::user(),
            DataRequestStatus::Rejected,
            $request->admin_notes,
        );

        return back()->with('success', 'Request rejected.');
    }

    /**
     * Mark an approved request as completed.
     */
    public function complete(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $this->dsrService->reviewRequest(
            $dataSubjectRequest,
            Auth::user(),
            DataRequestStatus::Completed,
            $request->admin_notes,
        );

        return back()->with('success', 'Request marked as completed.');
    }
}
