<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessCategoryController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        // Log view business categories list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'BusinessCategory',
            'Viewed business categories list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
                'order_by' => 'id_desc',
            ]
        );

        $businessCategories = BusinessCategory::OrderBy('id', 'desc')->paginate(10);

        return view('admin.business_categories.index', compact('businessCategories'));
    }

    public function create()
    {
        // Log view create form
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'BusinessCategory',
            'Viewed business category creation form'
        );

        $businessTypes = BusinessType::orderBy('name', 'ASC')->get();

        return view('admin.business_categories.create', compact('businessTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:business_categories,name'],
            'risk' => ['nullable', 'integer', 'between:1,5'],
            'order_level' => ['nullable', 'numeric'],
            'business_type_id' => ['required', 'exists:business_types,id'],
        ]);

        DB::beginTransaction();

        try {
            $category = BusinessCategory::create([
                'business_type_id' => $request->business_type_id,
                'name' => $request->name,
                'risk' => $request->risk,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            // Log business category creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New business category created for supplier classification',
                'business_operation',
                ['name']
            );

            $this->auditTrailService->logCreated(
                $category,
                "Created business category '{$category->name}' under business type ID: {$request->business_type_id}",
                $justificationData
            );

            DB::commit();

            return redirect()->route('business-categories.index')->with('success', 'Business Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'business_category_creation_failed',
                'entity_type' => 'BusinessCategory',
                'action_summary' => 'Failed to create business category',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', 'banner', 'icon']),
                    'business_type_id' => $request->business_type_id,
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function edit(BusinessCategory $businessCategory)
    {
        // Log view edit form
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'BusinessCategory',
            'entity_id' => $businessCategory->id,
            'action_summary' => "Viewed edit form for business category '{$businessCategory->name}'",
            'properties' => [
                'business_category_id' => $businessCategory->id,
                'business_type_id' => $businessCategory->business_type_id,
                'current_name' => $businessCategory->name,
                'risk_level' => $businessCategory->risk,
            ],
        ]);

        $businessTypes = BusinessType::orderBy('name', 'ASC')->get();

        return view('admin.business_categories.edit', compact('businessCategory', 'businessTypes'));
    }

    public function update(Request $request, BusinessCategory $businessCategory)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('business_categories', 'name')->ignore($businessCategory->id),
            ],
            'risk' => ['nullable', 'integer', 'between:1,5'],
            'order_level' => ['nullable', 'numeric'],
            'business_type_id' => ['required', 'exists:business_types,id'],
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $businessCategory->toArray();
            $oldBusinessTypeId = $businessCategory->business_type_id;

            $businessCategory->update([
                'business_type_id' => $request->business_type_id,
                'name' => $request->name['en'],
                'risk' => $request->risk,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($businessCategory, $request);

            // Prepare update summary with changes
            $changes = [];
            if ($oldData['name'] !== $businessCategory->name) {
                $changes[] = "name: '{$oldData['name']}' to '{$businessCategory->name}'";
            }
            if ($oldBusinessTypeId !== (int) $request->business_type_id) {
                $oldBusinessType = BusinessType::find($oldBusinessTypeId);
                $newBusinessType = BusinessType::find($request->business_type_id);
                $oldName = $oldBusinessType ? $oldBusinessType->name : 'ID '.$oldBusinessTypeId;
                $newName = $newBusinessType ? $newBusinessType->name : 'ID '.$request->business_type_id;
                $changes[] = "business type: '{$oldName}' to '{$newName}'";
            }
            if ($oldData['risk'] != $request->risk) {
                $changes[] = "risk: {$oldData['risk']} to {$request->risk}";
            }
            if ($oldData['order_level'] != $request->order_level) {
                $changes[] = "order level: {$oldData['order_level']} to {$request->order_level}";
            }
            if ($oldData['featured'] != $request->boolean('featured')) {
                $oldStatus = $oldData['featured'] ? 'featured' : 'not featured';
                $newStatus = $request->boolean('featured') ? 'featured' : 'not featured';
                $changes[] = "featured status: {$oldStatus} to {$newStatus}";
            }

            $changeSummary = ! empty($changes) ? ' ('.implode(', ', $changes).')' : '';

            // Log business category update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Business category updated to improve supplier classification',
                'data_correction',
                ['name', 'risk']
            );

            $this->auditTrailService->logUpdated(
                $businessCategory,
                $oldData,
                "Updated business category '{$businessCategory->name}'{$changeSummary}",
                $justificationData
            );

            DB::commit();

            return redirect()->route('business-categories.index')->with('success', 'Business Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'business_category_update_failed',
                'entity_type' => 'BusinessCategory',
                'entity_id' => $businessCategory->id,
                'action_summary' => "Failed to update business category '{$businessCategory->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method', 'banner', 'icon']),
                    'old_name' => $businessCategory->name,
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function destroy(BusinessCategory $businessCategory)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $categoryData = $businessCategory->toArray();

            // Check if category is being used by any merchants
            $merchantCount = \App\Models\Merchant::where('business_category_id', 'LIKE', '%'.$businessCategory->id.'%')->count();
            $usageWarning = $merchantCount > 0 ? " (Warning: Used by {$merchantCount} merchant(s))" : '';

            // Log before deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Business category removed to streamline supplier classification',
                'data_cleanup',
                ['name']
            );

            $this->auditTrailService->logDeleted(
                $businessCategory,
                "Deleted business category '{$businessCategory->name}'{$usageWarning}",
                $justificationData
            );

            // Delete the business category
            $businessCategory->delete();

            DB::commit();

            return redirect()->route('business-categories.index')->with('success', 'Business Category deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'business_category_deletion_failed',
                'entity_type' => 'BusinessCategory',
                'entity_id' => $businessCategory->id,
                'action_summary' => "Failed to delete business category '{$businessCategory->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'business_category_id' => $businessCategory->id,
                    'merchant_usage_count' => $merchantCount ?? 0,
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    private function storeOrUpdateTranslation(BusinessCategory $businessCategory, Request $request)
    {
        if (isset($request->name['ar'])) {
            DB::beginTransaction();

            try {
                $oldTranslation = $businessCategory->translations()->where('locale', 'ar')->first();
                $oldTranslationData = $oldTranslation ? $oldTranslation->toArray() : null;

                $businessCategory->translations()->updateOrCreate(
                    ['locale' => 'ar'],
                    ['name' => $request->name['ar']]
                );

                // Log translation update
                $action = $oldTranslation ? 'updated' : 'created';

                $this->auditTrailService->log([
                    'event_category' => 'localization',
                    'event_type' => 'business_category_translation_'.$action,
                    'entity_type' => 'BusinessCategory',
                    'entity_id' => $businessCategory->id,
                    'action_summary' => "{$action} Arabic translation for business category '{$businessCategory->name}'",
                    'before_state' => $oldTranslationData,
                    'after_state' => [
                        'locale' => 'ar',
                        'name' => $request->name['ar'],
                        'business_category_id' => $businessCategory->id,
                    ],
                    'properties' => [
                        'locale' => 'ar',
                        'business_category_id' => $businessCategory->id,
                        'english_name' => $businessCategory->name,
                        'arabic_name' => $request->name['ar'],
                        'translation_action' => $action,
                    ],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                // Log failed translation update
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'business_category_translation_failed',
                    'entity_type' => 'BusinessCategory',
                    'entity_id' => $businessCategory->id,
                    'action_summary' => "Failed to update Arabic translation for business category '{$businessCategory->name}'",
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'locale' => 'ar',
                        'business_category_id' => $businessCategory->id,
                    ],
                ]);

                throw $e;
            }
        }
    }
}
