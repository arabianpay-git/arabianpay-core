<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        try {
            $user = Auth::user();

            $tickets = SupportTicket::selectRaw('MAX(id) as id, ticket_number, MAX(user_id) as user_id, MAX(subject) as subject, MAX(details) as details, MAX(files) as files, MAX(reply) as reply, MAX(status) as status, MAX(created_at) as created_at')
                ->groupBy('ticket_number')
                ->orderByDesc('id')
                ->paginate(10);

            // Log support tickets list view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Support tickets list view required for customer service management, issue tracking, and operational oversight',
                'legitimate_interest',
                ['ticket_subjects', 'ticket_statuses', 'customer_references']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'support_operations',
                'event_type' => 'support_tickets_list_view',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Viewed all support tickets list',
                'properties' => [
                    'total_tickets' => $tickets->total(),
                    'current_page' => $tickets->currentPage(),
                    'per_page' => $tickets->perPage(),
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'is_admin_view' => true,
                    'status_distribution' => $tickets->groupBy('status')->map->count()
                ]
            ], $justificationData));

            return view('admin.support-ticket.index', compact('tickets'));
        } catch (\Exception $e) {
            Log::error('Failed to load support tickets list', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'support_tickets_list_failed',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Failed to load support tickets list',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load support tickets. Please try again.');
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $ticket = SupportTicket::findOrFail($id);

            // Check if user has permission to view this ticket
            if ($user->user_type !== 'admin' && $ticket->user_id !== $user->id && $ticket->assigned_to !== $user->id) {
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_ticket_view',
                    'entity_type' => 'SupportTicket',
                    'entity_id' => $ticket->id,
                    'action_summary' => 'User attempted to view unauthorized support ticket',
                    'properties' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_owner_id' => $ticket->user_id,
                        'ticket_assigned_to' => $ticket->assigned_to,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'unauthorized_access' => true
                    ]
                ]);

                return redirect()->route('tickets')
                    ->with('error', 'You are not authorized to view this support ticket.');
            }

            $activities = SupportTicket::whereEncrypted('ticket_number', $ticket->ticket_number)
                ->orderBy('created_at')
                ->get();

            $activityData = collect();

            foreach ($activities as $activity) {
                $activityUser = $activity->user;
                $activityData->push([
                    'id'    => $activity->id,
                    'type' => $activity->user_id == Auth::id(),
                    'user_name' => $activityUser ? ($activityUser->first_name . ' ' . $activityUser->last_name) : 'N/A',
                    'user_business' => $activityUser ? ($activityUser->business_name) : 'N/A',
                    'message' => $activity->details,
                    'subject' => $activity->subject,
                    'created_at' => $activity->created_at,
                    'files' => $activity->files ?? [],
                    'reply' => $activity->reply,
                ]);
            }

            // Log support ticket details view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Support ticket details view required for customer service, issue resolution, and communication tracking',
                'legitimate_interest',
                ['ticket_details', 'customer_communication', 'attached_files']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'support_operations',
                'event_type' => 'support_ticket_details_view',
                'entity_type' => 'SupportTicket',
                'entity_id' => $ticket->id,
                'action_summary' => 'Viewed support ticket details',
                'properties' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_subject' => $ticket->subject,
                    'ticket_status' => $ticket->status,
                    'ticket_owner_id' => $ticket->user_id,
                    'ticket_assigned_to' => $ticket->assigned_to,
                    'activities_count' => $activities->count(),
                    'has_files' => !empty($ticket->files),
                    'files_count' => count($ticket->files ?? []),
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'is_ticket_owner' => $ticket->user_id == $user->id,
                    'is_assigned_to' => $ticket->assigned_to == $user->id
                ]
            ], $justificationData));

            return view('admin.support-ticket.show', compact('ticket', 'activityData'));
        } catch (\Exception $e) {
            Log::error('Failed to load support ticket details', [
                'error' => $e->getMessage(),
                'ticket_id' => $id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'support_ticket_details_failed',
                'entity_type' => 'SupportTicket',
                'entity_id' => $id,
                'action_summary' => 'Failed to load support ticket details',
                'properties' => [
                    'error' => $e->getMessage(),
                    'ticket_id' => $id,
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->route('tickets')
                ->with('error', 'Failed to load support ticket details. Please try again.');
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();

            // Log ticket creation form view
            $this->auditTrailService->log([
                'event_category' => 'support_operations',
                'event_type' => 'ticket_creation_form_view',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Viewed support ticket creation form',
                'properties' => [
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'timestamp' => now()->toISOString()
                ]
            ]);

            return view('admin.support-ticket.create');
        } catch (\Exception $e) {
            Log::error('Failed to load ticket creation form', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->route('tickets')
                ->with('error', 'Failed to load ticket creation form.');
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'details' => 'required|string|max:700',
                'files' => 'nullable|array',
                'files.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            ]);

            DB::beginTransaction();

            $ticketNumber = 'TKT-' . now()->format('Ymd') . '-' . rand(1000, 9999);
            $filesData = [];

            // Handle file uploads if present
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $filename = Str::random(40) . '.' . $extension;
                    $folder = 'support_tickets';
                    $filePath = "{$folder}/{$filename}";

                    $file->storeAs($folder, $filename, 'public');
                    $filesData[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name' => $filename,
                        'path' => $filePath,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()->toISOString()
                    ];
                }
            }

            $ticket = SupportTicket::create([
                'assigned_to' => $request->assigned_to,
                'user_id' => $user->id,
                'ticket_number' => $ticketNumber,
                'subject' => $validated['subject'],
                'details' => $validated['details'],
                'files' => $filesData,
                'status' => 'active',
            ]);

            // Log ticket creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Support ticket creation required for customer issue reporting, service request tracking, and support management',
                'legitimate_interest',
                ['ticket_subject', 'ticket_details', 'attached_files']
            );

            $this->auditTrailService->logCreated(
                $ticket,
                'Created new support ticket: ' . $ticket->subject,
                array_merge([
                    'event_category' => 'support_operations',
                    'event_type' => 'support_ticket_created',
                    'entity_type' => 'SupportTicket',
                    'properties' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_subject' => $ticket->subject,
                        'ticket_details_length' => strlen($ticket->details),
                        'ticket_status' => $ticket->status,
                        'created_by_id' => $user->id,
                        'created_by_email' => $user->email,
                        'assigned_to' => $ticket->assigned_to,
                        'files_count' => count($filesData),
                        'file_names' => array_map(fn($f) => $f['original_name'], $filesData),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('tickets')
                ->with('success', 'Ticket created successfully! Ticket Number: ' . $ticketNumber);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create support ticket', [
                'error' => $e->getMessage(),
                'subject' => $request->subject ?? 'unknown',
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'support_ticket_creation_failed',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Failed to create support ticket',
                'properties' => [
                    'error' => $e->getMessage(),
                    'subject' => $request->subject ?? 'unknown',
                    'details_length' => strlen($request->details ?? ''),
                    'has_files' => $request->hasFile('files'),
                    'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create support ticket. Please try again.');
        }
    }

    public function reply(Request $request, $ticket_id)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'reply' => 'required|string|max:700',
                'files' => 'nullable|array',
                'files.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
            ]);

            $ticket = SupportTicket::findOrFail($ticket_id);

            // Check if user has permission to reply to this ticket
            $canReply = $user->user_type === 'admin' ||
                $user->id === $ticket->assigned_to ||
                $user->id === $ticket->user_id;

            if (!$canReply) {
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_ticket_reply',
                    'entity_type' => 'SupportTicket',
                    'entity_id' => $ticket->id,
                    'action_summary' => 'User attempted to reply to unauthorized support ticket',
                    'properties' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_owner_id' => $ticket->user_id,
                        'ticket_assigned_to' => $ticket->assigned_to,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'unauthorized_reply' => true
                    ]
                ]);

                return redirect()->back()
                    ->with('error', 'You are not authorized to reply to this ticket.');
            }

            DB::beginTransaction();

            $filesData = $ticket->files ?? [];

            // Handle file uploads for reply
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $filename = Str::random(40) . '.' . $extension;
                    $folder = 'support_tickets';
                    $filePath = "{$folder}/{$filename}";

                    $file->storeAs($folder, $filename, 'public');
                    $filesData[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name' => $filename,
                        'path' => $filePath,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()->toISOString(),
                        'uploaded_by' => $user->id,
                        'is_reply_attachment' => true
                    ];
                }
            }

            // Capture before state for audit
            $beforeState = $ticket->toArray();

            $ticket->update([
                'reply' => $validated['reply'],
                'files' => $filesData,
                'replied_by' => $user->id,
                'replied_at' => now(),
            ]);

            // Create a new activity entry for the reply
            $replyActivity = SupportTicket::create([
                'ticket_number' => $ticket->ticket_number,
                'user_id' => $user->id,
                'subject' => $ticket->subject,
                'details' => $validated['reply'],
                'files' => $request->hasFile('files') ? array_slice($filesData, count($beforeState['files'] ?? [])) : [],
                'status' => $ticket->status,
                'is_reply' => true,
                'original_ticket_id' => $ticket->id,
            ]);

            // Log ticket reply with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Support ticket reply required for customer communication, issue resolution, and service documentation',
                'legitimate_interest',
                ['ticket_details', 'reply_content', 'attached_files']
            );

            $this->auditTrailService->logUpdated(
                $ticket,
                $beforeState,
                'Replied to support ticket: ' . $ticket->ticket_number,
                array_merge([
                    'event_category' => 'support_operations',
                    'event_type' => 'support_ticket_replied',
                    'entity_type' => 'SupportTicket',
                    'properties' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_subject' => $ticket->subject,
                        'reply_length' => strlen($validated['reply']),
                        'reply_by_id' => $user->id,
                        'reply_by_email' => $user->email,
                        'reply_by_type' => $user->user_type,
                        'is_customer_reply' => $user->id === $ticket->user_id,
                        'is_support_reply' => $user->id !== $ticket->user_id,
                        'files_attached' => $request->hasFile('files'),
                        'new_files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
                        'reply_activity_id' => $replyActivity->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'changes_made' => $this->getTicketChangedFields($beforeState, $ticket->toArray())
                    ]
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('showTickets', $ticket->id)
                ->with('success', 'Your reply has been submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reply to support ticket', [
                'error' => $e->getMessage(),
                'ticket_id' => $ticket_id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'support_ticket_reply_failed',
                'entity_type' => 'SupportTicket',
                'entity_id' => $ticket_id,
                'action_summary' => 'Failed to reply to support ticket',
                'properties' => [
                    'error' => $e->getMessage(),
                    'ticket_id' => $ticket_id,
                    'reply_length' => strlen($request->reply ?? ''),
                    'has_files' => $request->hasFile('files'),
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to submit reply. Please try again.');
        }
    }

    public function updateStatus(Request $request, $ticket_number)
    {
        try {
            $request->validate([
                'status' => 'required|in:active,solved,draft,canceled',
            ]);

            $user = Auth::user();
            $ticket = SupportTicket::whereEncrypted('ticket_number', $ticket_number)->first();

            if (!$ticket) {
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'ticket_status_update_not_found',
                    'entity_type' => 'SupportTicket',
                    'action_summary' => 'Attempted to update status of non-existent ticket',
                    'properties' => [
                        'ticket_number' => $ticket_number,
                        'requested_status' => $request->status,
                        'attempted_by' => $user->id
                    ]
                ]);

                return redirect()->back()->with('error', 'Ticket not found.');
            }

            // Check authorization
            $isAuthorized = $ticket->user_id === $user->id ||
                $user->user_type === 'admin' ||
                ($user->user_type === 'employee' && $user->is_manager);

            if (!$isAuthorized) {
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_ticket_status_update',
                    'entity_type' => 'SupportTicket',
                    'entity_id' => $ticket->id,
                    'action_summary' => 'User attempted unauthorized ticket status update',
                    'properties' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_owner_id' => $ticket->user_id,
                        'requested_status' => $request->status,
                        'user_id' => $user->id,
                        'user_type' => $user->user_type,
                        'is_manager' => $user->is_manager,
                        'unauthorized_update' => true
                    ]
                ]);

                return redirect()->back()->with('error', 'You are not authorized to update this ticket status.');
            }

            DB::beginTransaction();

            $batchUuid = (string) Str::uuid();
            $tickets = SupportTicket::whereEncrypted('ticket_number', $ticket_number)->get();
            $updatedCount = 0;

            foreach ($tickets as $t) {
                // Capture before state for each ticket
                $beforeState = $t->toArray();

                $t->update(['status' => $request->status]);

                // Log status update for each ticket with justification
                $justificationData = $this->auditTrailService->withJustification(
                    'Support ticket status update required for workflow management, SLA tracking, and operational reporting',
                    'legitimate_interest',
                    ['ticket_status', 'ticket_reference']
                );

                $this->auditTrailService->logUpdated(
                    $t,
                    $beforeState,
                    'Updated ticket status to ' . $request->status,
                    array_merge([
                        'event_category' => 'support_operations',
                        'event_type' => 'support_ticket_status_updated',
                        'entity_type' => 'SupportTicket',
                        'properties' => [
                            'ticket_id' => $t->id,
                            'ticket_number' => $t->ticket_number,
                            'old_status' => $beforeState['status'] ?? 'unknown',
                            'new_status' => $request->status,
                            'updated_by_id' => $user->id,
                            'updated_by_email' => $user->email,
                            'updated_by_type' => $user->user_type,
                            'batch_uuid' => $batchUuid,
                            'is_ticket_owner' => $t->user_id === $user->id,
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'changes_made' => $this->getTicketChangedFields($beforeState, $t->toArray())
                        ]
                    ], $justificationData)
                );

                $updatedCount++;
            }

            DB::commit();

            // Log batch update summary
            $this->auditTrailService->log([
                'event_category' => 'support_operations',
                'event_type' => 'ticket_status_batch_update_complete',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Completed batch status update for ticket',
                'properties' => [
                    'ticket_number' => $ticket_number,
                    'old_status' => $ticket->status,
                    'new_status' => $request->status,
                    'tickets_updated' => $updatedCount,
                    'batch_uuid' => $batchUuid,
                    'updated_by' => $user->id,
                    'timestamp' => now()->toISOString()
                ]
            ]);

            return redirect()->back()->with('success', 'Ticket status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update ticket status', [
                'error' => $e->getMessage(),
                'ticket_number' => $ticket_number,
                'requested_status' => $request->status,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'ticket_status_update_failed',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Failed to update ticket status',
                'properties' => [
                    'error' => $e->getMessage(),
                    'ticket_number' => $ticket_number,
                    'requested_status' => $request->status,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to update ticket status. Please try again.');
        }
    }

    public function internelTickets()
    {
        try {
            $user = Auth::user();

            $tickets = SupportTicket::selectRaw('
                MAX(id) as id,
                ticket_number,
                MAX(user_id) as user_id,
                MAX(assigned_to) as assigned_to,
                MAX(subject) as subject,
                MAX(details) as details,
                MAX(files) as files,
                MAX(reply) as reply,
                MAX(status) as status,
                MAX(created_at) as created_at
            ')
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhere('user_id', $user->id);
                })
                ->with(['assigned', 'user'])
                ->groupBy('ticket_number')
                ->orderByDesc('id')
                ->paginate(10);

            // Log internal tickets view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Internal support tickets view required for assigned task management and personal ticket tracking',
                'legitimate_interest',
                ['ticket_subjects', 'ticket_statuses']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'support_operations',
                'event_type' => 'internal_tickets_view',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Viewed internal support tickets',
                'properties' => [
                    'total_tickets' => $tickets->total(),
                    'current_page' => $tickets->currentPage(),
                    'per_page' => $tickets->perPage(),
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'ticket_criteria' => 'assigned_to OR created_by',
                    'assigned_tickets' => $tickets->where('assigned_to', $user->id)->count(),
                    'owned_tickets' => $tickets->where('user_id', $user->id)->count(),
                    'status_distribution' => $tickets->groupBy('status')->map->count()
                ]
            ], $justificationData));

            return view('admin.support-ticket.index', compact('tickets'));
        } catch (\Exception $e) {
            Log::error('Failed to load internal tickets', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'internal_tickets_view_failed',
                'entity_type' => 'SupportTicket',
                'action_summary' => 'Failed to load internal support tickets',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load internal tickets. Please try again.');
        }
    }

    /**
     * Helper method to identify changed fields in ticket updates
     *
     * @param array $beforeState
     * @param array $afterState
     * @return array
     */
    private function getTicketChangedFields(array $beforeState, array $afterState): array
    {
        $changed = [];
        $sensitiveFields = ['user_id', 'assigned_to', 'details', 'reply'];

        foreach ($beforeState as $key => $value) {
            if (isset($afterState[$key]) && $afterState[$key] != $value) {
                if (in_array($key, $sensitiveFields)) {
                    if ($key === 'details' || $key === 'reply') {
                        $changed[$key] = [
                            'old_length' => strlen($value ?? ''),
                            'new_length' => strlen($afterState[$key] ?? ''),
                            'changed' => true
                        ];
                    } else {
                        $changed[$key] = [
                            'old' => '***MASKED***',
                            'new' => '***MASKED***',
                            'changed' => true
                        ];
                    }
                } else {
                    $changed[$key] = [
                        'old' => $value,
                        'new' => $afterState[$key]
                    ];
                }
            }
        }

        // Check for new fields that weren't in before state
        foreach ($afterState as $key => $value) {
            if (!isset($beforeState[$key])) {
                if (in_array($key, $sensitiveFields)) {
                    if ($key === 'details' || $key === 'reply') {
                        $changed[$key] = [
                            'old' => null,
                            'new_length' => strlen($value ?? '')
                        ];
                    } else {
                        $changed[$key] = [
                            'old' => null,
                            'new' => '***MASKED***'
                        ];
                    }
                } else {
                    $changed[$key] = [
                        'old' => null,
                        'new' => $value
                    ];
                }
            }
        }

        return $changed;
    }
}
