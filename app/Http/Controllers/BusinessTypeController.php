<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BusinessTypeController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        // Log view business types list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'BusinessType',
            'Viewed business types list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
                'order_by' => 'id_desc',
            ]
        );

        $businessTypes = BusinessType::with(['businessCategories'])->select('business_types.*')->orderBy('id', 'desc')->paginate(10);
        return view('admin.business_types.index', compact('businessTypes'));
    }

    public function create()
    {
        // Log view create form
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'BusinessType',
            'Viewed business type creation form'
        );

        return view('admin.business_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:business_types,name'],
            'risk_level' => ['required', 'in:low,medium-low,medium,high,very-high'],
            'order_level' => ['required', 'string', 'max:255'],
            'banner' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();

        try {
            $businessType = BusinessType::create([
                'name' => $request->name,
                'risk_level' => $request->risk_level,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            // Log business type creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New business type created for supplier risk classification',
                'business_operation',
                ['name', 'risk_level']
            );

            $this->auditTrailService->logCreated(
                $businessType,
                "Created business type '{$businessType->name}' with risk level: {$businessType->risk_level}",
                $justificationData
            );

            DB::commit();

            return redirect()->route('business-types.index')->with('success', 'Business Type created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'business_type_creation_failed',
                'entity_type' => 'BusinessType',
                'action_summary' => 'Failed to create business type',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', 'banner', 'icon']),
                    'risk_level' => $request->risk_level,
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(BusinessType $businessType)
    {
        // Log view edit form
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'BusinessType',
            'entity_id' => $businessType->id,
            'action_summary' => "Viewed edit form for business type '{$businessType->name}'",
            'properties' => [
                'business_type_id' => $businessType->id,
                'current_name' => $businessType->name,
                'risk_level' => $businessType->risk_level,
                'categories_count' => $businessType->businessCategories->count(),
            ],
        ]);

        return view('admin.business_types.edit', compact('businessType'));
    }

    public function update(Request $request, BusinessType $businessType)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('business_types', 'name')->ignore($businessType->id),
            ],
            'risk_level' => ['required', 'in:low,medium-low,medium,high,very-high'],
            'order_level' => ['required', 'string', 'max:255'],
            'banner' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $businessType->toArray();

            $businessType->update([
                'name' => $request->name['en'],
                'risk_level' => $request->risk_level,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($businessType, $request);

            // Prepare update summary with changes
            $changes = [];
            if ($oldData['name'] !== $businessType->name) {
                $changes[] = "name: '{$oldData['name']}' to '{$businessType->name}'";
            }
            if ($oldData['risk_level'] !== $businessType->risk_level) {
                $changes[] = "risk level: {$oldData['risk_level']} to {$businessType->risk_level}";
            }
            if ($oldData['order_level'] != $businessType->order_level) {
                $changes[] = "order level: {$oldData['order_level']} to {$businessType->order_level}";
            }
            if ($oldData['featured'] != $request->boolean('featured')) {
                $oldStatus = $oldData['featured'] ? 'featured' : 'not featured';
                $newStatus = $request->boolean('featured') ? 'featured' : 'not featured';
                $changes[] = "featured status: {$oldStatus} to {$newStatus}";
            }

            $changeSummary = !empty($changes) ? ' (' . implode(', ', $changes) . ')' : '';

            // Log business type update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Business type updated for risk classification accuracy',
                'risk_management',
                ['name', 'risk_level']
            );

            $this->auditTrailService->logUpdated(
                $businessType,
                $oldData,
                "Updated business type '{$businessType->name}'{$changeSummary}",
                $justificationData
            );

            DB::commit();

            return redirect()->route('business-types.index')->with('success', 'Business Type updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'business_type_update_failed',
                'entity_type' => 'BusinessType',
                'entity_id' => $businessType->id,
                'action_summary' => "Failed to update business type '{$businessType->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method', 'banner', 'icon']),
                    'old_risk_level' => $businessType->risk_level,
                    'new_risk_level' => $request->risk_level,
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(BusinessType $businessType)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $businessTypeData = $businessType->toArray();

            // Check if business type has associated categories
            $categoryCount = $businessType->businessCategories->count();
            $usageWarning = $categoryCount > 0 ? " (Warning: Has {$categoryCount} associated categor(ies))" : '';

            // Log before deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Business type removed to streamline risk classification',
                'data_cleanup',
                ['name', 'risk_level']
            );

            $this->auditTrailService->logDeleted(
                $businessType,
                "Deleted business type '{$businessType->name}' with risk level: {$businessType->risk_level}{$usageWarning}",
                $justificationData
            );

            $businessType->delete();

            DB::commit();

            return redirect()->route('business-types.index')->with('success', 'Business Type deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'business_type_deletion_failed',
                'entity_type' => 'BusinessType',
                'entity_id' => $businessType->id,
                'action_summary' => "Failed to delete business type '{$businessType->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'business_type_id' => $businessType->id,
                    'associated_categories_count' => $categoryCount,
                    'risk_level' => $businessType->risk_level,
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    private function storeOrUpdateTranslation(BusinessType $businessType, Request $request)
    {
        if (isset($request->name['ar'])) {
            DB::beginTransaction();

            try {
                $oldTranslation = $businessType->translations()->where('locale', 'ar')->first();
                $oldTranslationData = $oldTranslation ? $oldTranslation->toArray() : null;

                $businessType->translations()->updateOrCreate(
                    ['locale' => 'ar'],
                    [
                        'name' => $request->name['ar'],
                    ]
                );

                // Log translation update
                $action = $oldTranslation ? 'updated' : 'created';

                $this->auditTrailService->log([
                    'event_category' => 'localization',
                    'event_type' => 'business_type_translation_' . $action,
                    'entity_type' => 'BusinessType',
                    'entity_id' => $businessType->id,
                    'action_summary' => "{$action} Arabic translation for business type '{$businessType->name}'",
                    'before_state' => $oldTranslationData,
                    'after_state' => [
                        'locale' => 'ar',
                        'name' => $request->name['ar'],
                        'business_type_id' => $businessType->id,
                    ],
                    'properties' => [
                        'locale' => 'ar',
                        'business_type_id' => $businessType->id,
                        'english_name' => $businessType->name,
                        'arabic_name' => $request->name['ar'],
                        'risk_level' => $businessType->risk_level,
                        'translation_action' => $action,
                    ],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                // Log failed translation update
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'business_type_translation_failed',
                    'entity_type' => 'BusinessType',
                    'entity_id' => $businessType->id,
                    'action_summary' => "Failed to update Arabic translation for business type '{$businessType->name}'",
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'locale' => 'ar',
                        'business_type_id' => $businessType->id,
                    ],
                ]);

                throw $e;
            }
        }
    }
}
