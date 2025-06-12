<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BusinessCategoryController extends Controller
{
    public function index()
    {
        $businessCategories = BusinessCategory::paginate(10);
        return view('admin.business_categories.index', compact('businessCategories'));
    }

    public function create()
    {
        $businessTypes = BusinessType::orderBy('name', 'ASC')->get();
        return view('admin.business_categories.create', compact('businessTypes'));
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
                'business_type_id' => $request->business_type_id,
                'name' => $request->name,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            DB::commit();

            // log the creation of the business category
            $category->logModelAction(
                event: 'create',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " created a new business category: {$category->name}",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => (string) Str::uuid(), // Generate a new UUID for the batch
                ],
            );

            return redirect()->route('business-categories.index')->with('success', 'Business Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(BusinessCategory $businessCategory)
    {
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

            // log the update of the business category
            $businessCategory->logModelAction(
                event: 'update',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated business category: {$businessCategory->name} [{$businessCategory->id}]",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => (string) Str::uuid(), // Generate a new UUID for the batch
                ],
            );

            return redirect()->route('business-categories.index')->with('success', 'Business Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(BusinessCategory $businessCategory)
    {
        // log the deletion of the business category
        $businessCategory->logModelAction(
            event: 'delete',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " deleted business category: {$businessCategory->name} [{$businessCategory->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(), // Generate a new UUID for the batch
            ],
        );
        // Delete the business category
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
