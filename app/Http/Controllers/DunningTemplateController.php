<?php

namespace App\Http\Controllers;

use App\Models\DunningTemplate;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DunningTemplateController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        try {
            $query = DunningTemplate::query();

            // Apply filters
            $filters = [];
            if ($request->filled('type')) {
                $query->where('type', $request->type);
                $filters['type'] = $request->type;
            }

            if ($request->filled('dpd_bucket')) {
                $query->where('dpd_bucket', $request->dpd_bucket);
                $filters['dpd_bucket'] = $request->dpd_bucket;
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
                $filters['search'] = $search;
            }

            $items = $query->latest()->paginate(10);

            // Log the view operation for listing templates
            $this->auditTrailService->logViewOperation(
                'template_list_view',
                'DunningTemplate',
                'Viewed dunning template list with filters',
                [
                    'filters_applied' => $filters,
                    'total_templates' => $items->total(),
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                ]
            );

            return view('admin.collections.dunning', compact('items'));
        } catch (\Exception $e) {
            Log::error('Failed to load dunning templates list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'template_list_failed',
                'entity_type' => 'DunningTemplate',
                'action_summary' => 'Failed to load dunning template list',
                'properties' => [
                    'error' => $e->getMessage(),
                    'filters' => $request->all(),
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to load templates. Please try again.');
        }
    }

    public function show($id)
    {
        try {
            $dunning = DunningTemplate::find($id);

            if (! $dunning) {
                // Log failed lookup attempt
                $this->auditTrailService->log([
                    'event_category' => 'data_access',
                    'event_type' => 'template_not_found',
                    'entity_type' => 'DunningTemplate',
                    'entity_id' => $id,
                    'action_summary' => 'Attempted to view non-existent dunning template',
                    'properties' => [
                        'requested_id' => $id,
                        'http_method' => 'GET',
                    ],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Template not found',
                ], 404);
            }

            // Log successful template view with PII justification
            $justificationData = $this->auditTrailService->withJustification(
                'Template view required for collections management and operational oversight',
                'legitimate_interest',
                ['template_name', 'dpd_bucket', 'message_content']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'data_access',
                'event_type' => 'template_view',
                'entity_type' => 'DunningTemplate',
                'entity_id' => $dunning->id,
                'action_summary' => 'Viewed dunning template details',
                'properties' => [
                    'template_name' => $dunning->name,
                    'dpd_bucket' => $dunning->dpd_bucket,
                    'type' => $dunning->type,
                    'language' => $dunning->language,
                ],
            ], $justificationData));

            return response()->json([
                'success' => true,
                'data' => $dunning,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve dunning template', [
                'error' => $e->getMessage(),
                'template_id' => $id,
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'template_retrieval_failed',
                'entity_type' => 'DunningTemplate',
                'entity_id' => $id,
                'action_summary' => 'Failed to retrieve dunning template',
                'properties' => [
                    'error' => $e->getMessage(),
                    'template_id' => $id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve template',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:dunning_templates,name',
                'dpd_bucket' => 'required|string|max:20',
                'type' => 'required|string|max:50',
                'language' => 'required|string|max:10',
                'throttling' => 'nullable|string|max:50',
                'message' => 'required|min:3|max:700|string',
            ]);

            if ($validator->fails()) {
                // Log validation failure
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'template_creation_validation_failed',
                    'entity_type' => 'DunningTemplate',
                    'action_summary' => 'Dunning template creation failed validation',
                    'properties' => [
                        'validation_errors' => $validator->errors()->toArray(),
                        'input_data' => $request->except('message'), // Exclude full message from logs
                        'message_length' => strlen($request->input('message', '')),
                    ],
                ]);

                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }

            $template = DunningTemplate::create($request->all());

            // Log successful template creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Dunning template creation for collections process automation',
                'legitimate_interest',
                ['template_name', 'dpd_bucket', 'message_content']
            );

            $this->auditTrailService->logCreated(
                $template,
                'Created new dunning template for '.$request->dpd_bucket.' DPD bucket',
                array_merge([
                    'event_category' => 'crud_operations',
                    'event_type' => 'template_created',
                    'entity_type' => 'DunningTemplate',
                ], $justificationData)
            );

            return response()->json([
                'success' => true,
                'data' => $template,
                'message' => 'Dunning template created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create dunning template', [
                'error' => $e->getMessage(),
                'input' => $request->except('message'), // Exclude sensitive message content
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'template_creation_failed',
                'entity_type' => 'DunningTemplate',
                'action_summary' => 'Failed to create dunning template',
                'properties' => [
                    'error' => $e->getMessage(),
                    'template_name' => $request->input('name'),
                    'dpd_bucket' => $request->input('dpd_bucket'),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create template. Please try again.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $template = DunningTemplate::findOrFail($id);

            // Capture before state for audit
            $beforeState = $template->toArray();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:dunning_templates,name,'.$id,
                'dpd_bucket' => 'required|string|max:20',
                'type' => 'required|string|max:50',
                'language' => 'required|string|max:10',
                'throttling' => 'nullable|string|max:50',
                'message' => 'required|min:3|max:700|string',
            ]);

            if ($validator->fails()) {
                // Log validation failure for update
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'template_update_validation_failed',
                    'entity_type' => 'DunningTemplate',
                    'entity_id' => $id,
                    'action_summary' => 'Dunning template update failed validation',
                    'properties' => [
                        'validation_errors' => $validator->errors()->toArray(),
                        'template_id' => $id,
                        'current_name' => $template->name,
                    ],
                ]);

                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }

            $template->update($request->all());

            // Log successful template update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Dunning template update required for collections process optimization',
                'legitimate_interest',
                ['template_name', 'dpd_bucket', 'message_content']
            );

            $this->auditTrailService->logUpdated(
                $template,
                $beforeState,
                'Updated dunning template: '.$template->name,
                array_merge([
                    'event_category' => 'crud_operations',
                    'event_type' => 'template_updated',
                    'entity_type' => 'DunningTemplate',
                    'properties' => [
                        'changed_fields' => $this->getChangedFields($beforeState, $template->toArray()),
                        'old_dpd_bucket' => $beforeState['dpd_bucket'] ?? null,
                        'new_dpd_bucket' => $template->dpd_bucket,
                    ],
                ], $justificationData)
            );

            return response()->json([
                'success' => true,
                'data' => $template,
                'message' => 'Dunning template updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update dunning template', [
                'error' => $e->getMessage(),
                'template_id' => $id,
                'input' => $request->except('message'),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'template_update_failed',
                'entity_type' => 'DunningTemplate',
                'entity_id' => $id,
                'action_summary' => 'Failed to update dunning template',
                'properties' => [
                    'error' => $e->getMessage(),
                    'template_id' => $id,
                    'template_name' => $request->input('name', 'Unknown'),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update template. Please try again.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $template = DunningTemplate::findOrFail($id);

            // Capture before state for audit
            $beforeState = $template->toArray();

            $template->delete();

            // Log template deletion with security justification
            $justificationData = $this->auditTrailService->withJustification(
                'Dunning template deletion for compliance with retention policies and process cleanup',
                'legal_obligation',
                ['template_name', 'dpd_bucket', 'message_content']
            );

            $this->auditTrailService->logDeleted(
                $template,
                'Deleted dunning template: '.$template->name,
                array_merge([
                    'event_category' => 'crud_operations',
                    'event_type' => 'template_deleted',
                    'entity_type' => 'DunningTemplate',
                    'properties' => [
                        'template_name' => $beforeState['name'] ?? null,
                        'dpd_bucket' => $beforeState['dpd_bucket'] ?? null,
                        'type' => $beforeState['type'] ?? null,
                        'language' => $beforeState['language'] ?? null,
                    ],
                ], $justificationData)
            );

            return response()->json([
                'success' => true,
                'message' => 'Dunning template deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete dunning template', [
                'error' => $e->getMessage(),
                'template_id' => $id,
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'template_deletion_failed',
                'entity_type' => 'DunningTemplate',
                'entity_id' => $id,
                'action_summary' => 'Failed to delete dunning template',
                'properties' => [
                    'error' => $e->getMessage(),
                    'template_id' => $id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete template. Please try again.',
            ], 500);
        }
    }

    /**
     * Helper method to identify changed fields for audit logs
     */
    private function getChangedFields(array $beforeState, array $afterState): array
    {
        $changed = [];

        foreach ($beforeState as $key => $value) {
            if (isset($afterState[$key]) && $afterState[$key] != $value) {
                $changed[$key] = [
                    'old' => $value,
                    'new' => $afterState[$key],
                ];
            }
        }

        // Check for new fields that weren't in before state
        foreach ($afterState as $key => $value) {
            if (! isset($beforeState[$key])) {
                $changed[$key] = [
                    'old' => null,
                    'new' => $value,
                ];
            }
        }

        return $changed;
    }
}
