<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessTypeController extends Controller
{
    public function index(Request $request)
    {
        $businessTypes = BusinessType::select('business_types.*')->paginate(10);
        return view('admin.business_types.index', compact('businessTypes'));
    }

    public function create()
    {
        return view('admin.business_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:business_types,name'],
            'order_level' => ['required', 'string', 'max:255'],
            'banner' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();

        try {
            $businessType = BusinessType::create([
                'name' => $request->name,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            DB::commit();

            return redirect()->route('business-types.index')->with('success', 'Business Type created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(BusinessType $businessType)
    {
        return view('admin.business_types.edit', compact('businessType'));
    }

    public function update(Request $request, BusinessType $businessType)
    {
        $request->validate([
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('business_types', 'name')->ignore($businessType->id)
            ],
            'order_level' => ['required', 'string', 'max:255'],
            'banner' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();

        try {
            $businessType->update([
                'name' => $request->name['en'],
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
            ]);

            $this->storeOrUpdateTranslation($businessType, $request);

            DB::commit();

            return redirect()->route('business-types.index')->with('success', 'Business Type updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(BusinessType $businessType)
    {
        $businessType->delete();
        return redirect()->route('business-types.index')->with('success', 'Business Type deleted successfully.');
    }

    private function storeOrUpdateTranslation(BusinessType $businessType, Request $request)
    {
        if (isset($request->name['ar'])) {
            $businessType->translations()->updateOrCreate(
                ['locale' => 'ar'],
                [
                    'name' => $request->name['ar'],
                ]
            );
        }
    }
}
