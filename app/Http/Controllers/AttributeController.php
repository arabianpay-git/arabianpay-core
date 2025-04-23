<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::with(['values', 'values.translations'])->select('attributes.*')->paginate(10);

        return view('admin.attributes.index', compact('attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:attributes,name'],
        ]);

        $attribute = Attribute::create([
            'name' => $request->name,
        ]);

        return redirect()->route('attributes.index')->with('success', 'Attribute created successfully.');
    }

    public function edit(Attribute $attribute)
    {
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function editAttributeValue(Attribute $attribute)
    {
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
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('attributes', 'name')->ignore($attribute->id),
            ],
        ]);

        $attribute->update([
            'name' => $request->name['en'],
        ]);

        $this->storeOrUpdateTranslations($attribute, $request);

        return redirect()->route('attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()->route('attributes.index')->with('success', 'Attribute deleted successfully.');
    }

    private function storeOrUpdateTranslations(Attribute $attribute, Request $request)
    {
        if ($request->has('name') && isset($request->name['ar'])) {
            $attribute->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
