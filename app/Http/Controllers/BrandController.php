<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Brand::with('translations')->get();
            return DataTables::of($data)
                ->addColumn('name', fn($row) => $row->name)
                ->addColumn('logo', fn($row) => '<img src="' . $row->logo . '" width="50" />')
                ->addColumn('order_level', fn($row) => $row->order_level)
                ->addColumn('featured', fn($row) => $row->featured ? 'Yes' : 'No')
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('brands.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('brands.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['logo', 'actions'])
                ->make(true);
        }

        return view('admin.brands.index');
    }

    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $brand = Brand::create([
                'slug' => $request->slug,
                'logo' => $request->logo,
                'order_level' => $request->order_level,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'],
                'meta_description' => $request->meta_description['en'],
            ]);

            $this->storeOrUpdateTranslations($brand, $request);

            DB::commit();

            return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.edit', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $brand->update([
                'slug' => $request->slug,
                'logo' => $request->logo,
                'order_level' => $request->order_level,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'],
                'meta_description' => $request->meta_description['en'],
            ]);

            $this->storeOrUpdateTranslations($brand, $request);

            DB::commit();

            return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // Remove the specified resource from storage.
    public function destroy(Brand $brand)
    {
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
    }

    // Handle storing or updating translations
    private function storeOrUpdateTranslations(Brand $brand, Request $request)
    {
        foreach ($request->name as $locale => $name) {
            if ($locale == 'ar') { // If Arabic translation is provided
                BrandTranslation::updateOrCreate(
                    ['brand_id' => $brand->id, 'locale' => $locale],
                    [
                        'name' => $name,
                        'meta_title' => $request->meta_title[$locale] ?? null,
                        'meta_description' => $request->meta_description[$locale] ?? null,
                    ]
                );
            }
        }
    }
}
