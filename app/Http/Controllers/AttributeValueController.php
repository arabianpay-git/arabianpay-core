<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Rules\NoHtml;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'value' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:attribute_values,value'],
            'attribute_id' => 'required|exists:attributes,id',
        ]);

        $attributeValue = AttributeValue::create([
            'attribute_id' => $request->attribute_id,
            'value' => $request->value,
            'color_code' => $request->color_code ?? null,
        ]);

        return redirect()->back()->with('success', 'Attribute Value created successfully.');
    }

    public function edit(AttributeValue $attributeValue)
    {
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
            ]
        ]);

        $attributeValue->update([
            'value' => $request->value['en'],
            'color_code' => $request->color_code ?? $request->color_code,
        ]);

        $this->storeOrUpdateTranslations($attributeValue, $request);

        return redirect()->back()->with('success', 'Attribute Value updated successfully.');
    }

    public function destroy(AttributeValue $attributeValue)
    {
        $attributeValue->delete();

        return redirect()->back()->with('success', 'Attribute Value deleted successfully.');
    }

    private function storeOrUpdateTranslations(AttributeValue $attributeValue, Request $request)
    {
        if ($request->has('value') && isset($request->value['ar'])) {
            $attributeValue->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['value' => $request->value['ar']]
            );
        }
    }
}
