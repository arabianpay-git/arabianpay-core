<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Rules\NoHtml;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttributeValueController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'value' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:attribute_values,value'],
            'attribute_id' => 'required|exists:attributes,id',
        ]);

        DB::beginTransaction();

        try {
            $attributeValue = AttributeValue::create([
                'attribute_id' => $request->attribute_id,
                'value' => $request->value,
                'color_code' => $request->color_code ?? null,
            ]);

            // Log attribute value creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New attribute value created for product variant options',
                'business_operation',
                ['value']
            );

            $this->auditTrailService->logCreated(
                $attributeValue,
                "Created attribute value '{$attributeValue->value}' for attribute ID: {$request->attribute_id}",
                $justificationData
            );

            DB::commit();

            return redirect()->back()->with('success', 'Attribute Value created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'attribute_value_creation_failed',
                'entity_type' => 'AttributeValue',
                'action_summary' => 'Failed to create attribute value',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except('_token'),
                    'attribute_id' => $request->attribute_id,
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function edit(AttributeValue $attributeValue)
    {
        // Log view edit form for attribute value
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'AttributeValue',
            'entity_id' => $attributeValue->id,
            'action_summary' => "Viewed edit form for attribute value '{$attributeValue->value}'",
            'properties' => [
                'attribute_value_id' => $attributeValue->id,
                'attribute_id' => $attributeValue->attribute_id,
                'current_value' => $attributeValue->value,
            ],
        ]);

        $attributeValue->load('translations');

        return view('admin.attributes.attribute-value-edit', compact('attributeValue'));
    }

    public function update(Request $request, AttributeValue $attributeValue)
    {
        $request->validate([
            'value.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('attribute_values', 'value')->ignore($attributeValue->id),
            ],
            'value.ar' => [
                'nullable',
                new NoHtml,
            ],
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $attributeValue->toArray();

            $attributeValue->update([
                'value' => $request->value['en'],
                'color_code' => $request->color_code ?? $attributeValue->color_code,
            ]);

            // Log attribute value update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Attribute value updated to improve product variant clarity',
                'data_correction',
                ['value']
            );

            $this->auditTrailService->logUpdated(
                $attributeValue,
                $oldData,
                "Updated attribute value from '{$oldData['value']}' to '{$attributeValue->value}'",
                $justificationData
            );

            $this->storeOrUpdateTranslations($attributeValue, $request);

            DB::commit();

            return redirect()->back()->with('success', 'Attribute Value updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'attribute_value_update_failed',
                'entity_type' => 'AttributeValue',
                'entity_id' => $attributeValue->id,
                'action_summary' => "Failed to update attribute value '{$attributeValue->value}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method']),
                    'old_value' => $attributeValue->value,
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function destroy(AttributeValue $attributeValue)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $attributeValueData = $attributeValue->toArray();
            $attributeName = $attributeValue->attribute->name ?? 'Unknown Attribute';

            // Log before deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Attribute value removed to clean up unused product variants',
                'data_cleanup',
                ['value']
            );

            $this->auditTrailService->logDeleted(
                $attributeValue,
                "Deleted attribute value '{$attributeValue->value}' from attribute '{$attributeName}'",
                $justificationData
            );

            $attributeValue->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Attribute Value deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'attribute_value_deletion_failed',
                'entity_type' => 'AttributeValue',
                'entity_id' => $attributeValue->id,
                'action_summary' => "Failed to delete attribute value '{$attributeValue->value}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'attribute_value_id' => $attributeValue->id,
                    'attribute_id' => $attributeValue->attribute_id,
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    private function storeOrUpdateTranslations(AttributeValue $attributeValue, Request $request)
    {
        if ($request->has('value') && isset($request->value['ar'])) {
            DB::beginTransaction();

            try {
                $oldTranslation = $attributeValue->translations()->where('locale', 'ar')->first();
                $oldTranslationData = $oldTranslation ? $oldTranslation->toArray() : null;

                $attributeValue->translations()->updateOrCreate(
                    ['locale' => 'ar'],
                    ['value' => $request->value['ar']]
                );

                // Log translation update
                $this->auditTrailService->log([
                    'event_category' => 'localization',
                    'event_type' => 'attribute_value_translation_update',
                    'entity_type' => 'AttributeValue',
                    'entity_id' => $attributeValue->id,
                    'action_summary' => "Updated Arabic translation for attribute value '{$attributeValue->value}'",
                    'before_state' => $oldTranslationData,
                    'after_state' => [
                        'locale' => 'ar',
                        'value' => $request->value['ar'],
                        'attribute_value_id' => $attributeValue->id,
                    ],
                    'properties' => [
                        'locale' => 'ar',
                        'attribute_value_id' => $attributeValue->id,
                        'english_value' => $attributeValue->value,
                        'arabic_value' => $request->value['ar'],
                    ],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                // Log failed translation update
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'attribute_value_translation_failed',
                    'entity_type' => 'AttributeValue',
                    'entity_id' => $attributeValue->id,
                    'action_summary' => "Failed to update Arabic translation for attribute value '{$attributeValue->value}'",
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'locale' => 'ar',
                        'attribute_value_id' => $attributeValue->id,
                    ],
                ]);

                throw $e;
            }
        }
    }
}
