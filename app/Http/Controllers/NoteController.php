<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index($userId)
    {
        try {
            $currentUser = Auth::user();
            $targetUser = User::find($userId);

            if (!$targetUser) {
                // Log invalid user access attempt
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'user_not_found',
                    'entity_type' => 'User',
                    'entity_id' => $userId,
                    'action_summary' => "Attempted to access notes for non-existent user ID: {$userId}",
                    'properties' => [
                        'requested_user_id' => $userId,
                        'accessing_user_id' => $currentUser->id,
                        'accessing_user_email' => $currentUser->email,
                    ],
                ]);

                abort(404, 'User not found');
            }

            $notes = Note::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Log view operation using the new method (no entity_id needed)
            $this->auditTrailService->logViewOperation(
                'view_user_notes',
                'Note',
                "Viewed notes for user: {$targetUser->email}",
                [
                    'user_id' => $targetUser->id,
                    'user_email' => $targetUser->email,
                    'notes_count' => $notes->count(),
                    'accessing_user_id' => $currentUser->id,
                    'accessing_user_role' => $currentUser->roles->first()?->name,
                ]
            );

            return view('notes.index', compact('notes', 'userId'));
        } catch (\Exception $e) {
            // Log error event
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'notes_index_error',
                'entity_type' => 'Note',
                'action_summary' => "Error accessing notes for user ID: {$userId}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'error_line' => $e->getLine(),
                    'user_id' => $userId,
                ],
            ]);

            return redirect()->back()->with('error', 'Unable to load notes. Please try again.');
        }
    }

    // AJAX store
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'note' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            // Log validation failure
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'validation_failed',
                'entity_type' => 'Note',
                'action_summary' => 'Note creation failed validation',
                'properties' => [
                    'validation_errors' => $validator->errors()->toArray(),
                    'requested_user_id' => $request->user_id,
                    'note_length' => strlen($request->note),
                ],
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $currentUser = Auth::user();
            $targetUser = User::find($request->user_id);

            // Create the note
            $note = Note::create([
                'user_id' => $request->user_id,
                'employee_id' => $currentUser->id,
                'note' => $request->note,
            ]);

            // Log note creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Note added for user record keeping and communication tracking',
                'business_operation',
                ['user_id', 'employee_id', 'note_content']
            );

            $this->auditTrailService->logCreated(
                $note,
                "Added note for user: {$targetUser->email}",
                array_merge($justificationData, [
                    'note_preview' => substr($note->note, 0, 150) . (strlen($note->note) > 150 ? '...' : ''),
                    'note_length' => strlen($note->note),
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'employee_id' => $currentUser->id,
                    'employee_email' => $currentUser->email,
                ])
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note added successfully',
                'note' => $note->load('employee')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'creation_failed',
                'entity_type' => 'Note',
                'action_summary' => 'Failed to create note',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'requested_user_id' => $request->user_id,
                    'input_data' => $request->except(['_token']),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    // AJAX update
    public function update(Request $request, Note $note)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            // Log validation failure
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'validation_failed',
                'entity_type' => 'Note',
                'entity_id' => $note->id,
                'action_summary' => "Note update failed validation for ID: {$note->id}",
                'properties' => [
                    'validation_errors' => $validator->errors()->toArray(),
                    'note_id' => $note->id,
                    'new_note_length' => strlen($request->note),
                ],
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $currentUser = Auth::user();
            $targetUser = User::find($note->user_id);

            // Get old data for audit trail
            $oldData = $note->toArray();

            // Update the note
            $note->update(['note' => $request->note]);

            // Log note update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Note updated to maintain accurate user records and communication history',
                'data_correction',
                ['note_content']
            );

            $this->auditTrailService->logUpdated(
                $note,
                $oldData,
                "Updated note for user: {$targetUser->email}",
                array_merge($justificationData, [
                    'content_changed' => $oldData['note'] !== $note->note,
                    'old_content_preview' => substr($oldData['note'], 0, 150) . (strlen($oldData['note']) > 150 ? '...' : ''),
                    'new_content_preview' => substr($note->note, 0, 150) . (strlen($note->note) > 150 ? '...' : ''),
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'updated_by_employee_id' => $currentUser->id,
                    'updated_by_employee_email' => $currentUser->email,
                ])
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note updated successfully',
                'note' => $note
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'update_failed',
                'entity_type' => 'Note',
                'entity_id' => $note->id,
                'action_summary' => "Failed to update note ID: {$note->id}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'note_id' => $note->id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    // AJAX delete
    public function destroy(Note $note)
    {
        DB::beginTransaction();

        try {
            $currentUser = Auth::user();
            $targetUser = User::find($note->user_id);

            // Get data before deletion for audit trail
            $noteData = $note->toArray();

            // Log note deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Note removed as part of data cleanup and privacy compliance',
                'data_cleanup',
                ['note_content', 'user_id', 'employee_id']
            );

            $this->auditTrailService->logDeleted(
                $note,
                "Deleted note for user: {$targetUser->email}",
                array_merge($justificationData, [
                    'note_content_preview' => substr($noteData['note'], 0, 150) . (strlen($noteData['note']) > 150 ? '...' : ''),
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'original_employee_id' => $noteData['employee_id'],
                    'deleted_by_employee_id' => $currentUser->id,
                    'deleted_by_employee_email' => $currentUser->email,
                    'note_age_days' => $note->created_at->diffInDays(now()),
                ])
            );

            $note->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Note deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'deletion_failed',
                'entity_type' => 'Note',
                'entity_id' => $note->id,
                'action_summary' => "Failed to delete note ID: {$note->id}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'note_id' => $note->id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
