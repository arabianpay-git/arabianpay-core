<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessCategoryController extends Controller
{
    public function index()
    {
        $businessCategories = BusinessCategory::paginate(10);
        return view('admin.business_categories.index', compact('businessCategories'));
    }

    public function create()
    {
        return view('admin.business_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:business_categories,name'],
            'order_level' => ['nullable', 'numeric'],
        ]);

        DB::beginTransaction();

        try {
            $category = BusinessCategory::create([
                'name' => $request->name,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            DB::commit();

            return redirect()->route('business-categories.index')->with('success', 'Business Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(BusinessCategory $businessCategory)
    {
        return view('admin.business_categories.edit', compact('businessCategory'));
    }

    public function update(Request $request, BusinessCategory $businessCategory)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('business_categories', 'name')->ignore($businessCategory->id),
            ],
            'order_level' => ['nullable', 'numeric'],
        ]);

        DB::beginTransaction();

        try {
            $businessCategory->update([
                'name' => $request->name['en'],
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($businessCategory, $request);

            DB::commit();

            return redirect()->route('business-categories.index')->with('success', 'Business Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(BusinessCategory $businessCategory)
    {
        $businessCategory->delete();
        return redirect()->route('business-categories.index')->with('success', 'Business Category deleted successfully.');
    }

    private function storeOrUpdateTranslation(BusinessCategory $businessCategory, Request $request)
    {
        if (isset($request->name['ar'])) {
            $businessCategory->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['name' => $request->name['ar']]
            );
        }
    }
}
