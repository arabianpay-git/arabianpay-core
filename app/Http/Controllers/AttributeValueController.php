<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = AttributeValue::with('translations')->select('attribute_values.*');
            return DataTables::of($data)
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('attribute-values.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('attribute-values.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.attribute-values.index');
    }

    public function create()
    {
        return view('admin.attribute-values.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'value.en' => 'required|string|max:255',
            'attribute_id' => 'required|exists:attributes,id',
        ]);

        $attributeValue = AttributeValue::create([
            'attribute_id' => $request->attribute_id,
            'value' => $request->value['en'],
            'color_code' => $request->color_code,
        ]);

        $this->storeOrUpdateTranslations($attributeValue, $request);

        return redirect()->route('attribute-values.index')->with('success', 'Attribute Value created successfully.');
    }

    public function edit(AttributeValue $attributeValue)
    {
        return view('admin.attribute-values.edit', compact('attributeValue'));
    }

    public function update(Request $request, AttributeValue $attributeValue)
    {
        $request->validate([
            'value.en' => 'required|string|max:255',
            'attribute_id' => 'required|exists:attributes,id',
        ]);

        $attributeValue->update([
            'attribute_id' => $request->attribute_id,
            'value' => $request->value['en'],
            'color_code' => $request->color_code,
        ]);

        $this->storeOrUpdateTranslations($attributeValue, $request);

        return redirect()->route('attribute-values.index')->with('success', 'Attribute Value updated successfully.');
    }

    public function destroy(AttributeValue $attributeValue)
    {
        $attributeValue->delete();

        return redirect()->route('attribute-values.index')->with('success', 'Attribute Value deleted successfully.');
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
