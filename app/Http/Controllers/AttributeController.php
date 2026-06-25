<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Rules\NoHtml;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        // Log view attributes list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Attribute',
            'Viewed attributes list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
            ]
        );

        $attributes = Attribute::with(['values', 'values.translations'])->select('attributes.*')->paginate(10);

        return view('admin.attributes.index', compact('attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:attributes,name'],
        ]);

        DB::beginTransaction();

        try {
            $attribute = Attribute::create([
                'name' => $request->name,
            ]);

            // Log attribute creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New attribute created for product categorization',
                'business_operation',
                ['name']
            );

            $this->auditTrailService->logCreated(
                $attribute,
                "Created new attribute '{$attribute->name}'",
                $justificationData
            );

            DB::commit();

            return redirect()->route('attributes.index')->with('success', 'Attribute created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'creation_failed',
                'entity_type' => 'Attribute',
                'action_summary' => 'Failed to create attribute',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except('_token'),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function edit(Attribute $attribute)
    {
        // Log view edit form
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'Attribute',
            'entity_id' => $attribute->id,
            'action_summary' => "Viewed edit form for attribute '{$attribute->name}'",
        ]);

        return view('admin.attributes.edit', compact('attribute'));
    }

    public function editAttributeValue(Attribute $attribute)
    {
        // Log view attribute values form
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_attribute_values',
            'entity_type' => 'Attribute',
            'entity_id' => $attribute->id,
            'action_summary' => "Viewed attribute values for '{$attribute->name}'",
        ]);

        $attribute->load(['values', 'values.translations']);

        return view('admin.attributes.attribute-value-create', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name')->ignore($attribute->id),
            ],
            'name.ar' => [
                'nullable',
                new NoHtml,
            ],
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $attribute->toArray();

            $attribute->update([
                'name' => $request->name['en'],
            ]);

            // Log attribute update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Attribute updated to improve product classification',
                'data_correction',
                ['name']
            );

            $this->auditTrailService->logUpdated(
                $attribute,
                $oldData,
                "Updated attribute '{$attribute->name}'",
                $justificationData
            );

            $this->storeOrUpdateTranslations($attribute, $request);

            DB::commit();

            return redirect()->route('attributes.index')->with('success', 'Attribute updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'update_failed',
                'entity_type' => 'Attribute',
                'entity_id' => $attribute->id,
                'action_summary' => "Failed to update attribute '{$attribute->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method']),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function destroy(Attribute $attribute)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $attributeData = $attribute->toArray();

            // Log before deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Attribute removed to streamline product catalog',
                'data_cleanup',
                ['name']
            );

            $this->auditTrailService->logDeleted(
                $attribute,
                "Deleted attribute '{$attribute->name}'",
                $justificationData
            );

            $attribute->delete();

            DB::commit();

            return redirect()->route('attributes.index')->with('success', 'Attribute deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'deletion_failed',
                'entity_type' => 'Attribute',
                'entity_id' => $attribute->id,
                'action_summary' => "Failed to delete attribute '{$attribute->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    private function storeOrUpdateTranslations(Attribute $attribute, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            DB::beginTransaction();

            try {
                $oldTranslation = $attribute->translations()->where('locale', 'ar')->first();
                $oldData = $oldTranslation ? $oldTranslation->toArray() : null;

                $attribute->translations()->updateOrCreate(
                    ['locale' => 'ar'],
                    ['name' => $request->name['ar']]
                );

                // Log translation update
                $this->auditTrailService->log([
                    'event_category' => 'localization',
                    'event_type' => 'translation_update',
                    'entity_type' => 'Attribute',
                    'entity_id' => $attribute->id,
                    'action_summary' => "Updated Arabic translation for attribute '{$attribute->name}'",
                    'before_state' => $oldData,
                    'after_state' => ['locale' => 'ar', 'name' => $request->name['ar']],
                    'properties' => [
                        'locale' => 'ar',
                        'attribute_id' => $attribute->id,
                    ],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                // Log failed translation update
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'translation_update_failed',
                    'entity_type' => 'Attribute',
                    'entity_id' => $attribute->id,
                    'action_summary' => "Failed to update Arabic translation for attribute '{$attribute->name}'",
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'locale' => 'ar',
                    ],
                ]);

                throw $e;
            }
        }
    }
}
