<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Product::with(['category:id,name', 'brand:id,name'])
                ->select('id', 'name', 'unit_price', 'category_id', 'brand_id', 'published', 'approved', 'featured')
                ->get();

            return DataTables::of($data)
                ->addColumn('category', function ($row) {
                    return $row->category ? $row->category->name : 'N/A';
                })
                ->addColumn('brand', function ($row) {
                    return $row->brand ? $row->brand->name : 'N/A';
                })
                ->addColumn('actions', function ($row) {
                    return '
                    <a href="' . route('products.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('products.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.products.index');
    }


    public function create()
    {
        $categories = Category::orderBy('name', 'asc')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        return view('admin.products.create', compact('categories', 'brands'));
    }

    public function store(StoreProductRequest $request)
    {
        DB::beginTransaction();

        try {
            $product = Product::create($request->validated());

            $this->storeOrUpdateTranslations($product, $request);

            // // Handle photos (optional)
            // if ($request->hasFile('photos')) {
            //     $product->addMultipleMediaFromRequest('photos')->toMediaCollection('product_images');
            // }

            // // Handle thumbnail (optional)
            // if ($request->hasFile('thumbnail')) {
            //     $product->addMediaFromRequest('thumbnail')->toMediaCollection('product_thumbnail');
            // }

            DB::commit();

            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error while creating product: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create the product. Please try again.');
        }
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $brands = Brand::all();
        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $product->update($request->validated());

            $this->storeOrUpdateTranslations($product, $request);

            // // Handle photos (optional) - Remove old media if required
            // if ($request->hasFile('photos')) {
            //     // Remove old media before adding new ones (optional)
            //     $product->clearMediaCollection('product_images');
            //     $product->addMultipleMediaFromRequest('photos')->toMediaCollection('product_images');
            // }

            // // Handle thumbnail (optional) - Remove old media if required
            // if ($request->hasFile('thumbnail')) {
            //     // Remove old thumbnail if necessary
            //     $product->clearMediaCollection('product_thumbnail');
            //     $product->addMediaFromRequest('thumbnail')->toMediaCollection('product_thumbnail');
            // }

            DB::commit();

            return redirect()->route('products.index')->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error while updating product: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update the product. Please try again.');
        }
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    private function storeOrUpdateTranslations(Product $product, Request $request)
    {
        foreach (['en', 'ar'] as $locale) {
            if ($request->has($locale)) {
                $product->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $request->input("name.{$locale}"),
                        'short_description' => $request->input("short_description.{$locale}"),
                        'description' => $request->input("description.{$locale}"),
                        'tags' => json_encode($request->input("tags.{$locale}")),
                        'meta_title' => $request->input("meta_title.{$locale}"),
                        'meta_description' => $request->input("meta_description.{$locale}"),
                    ]
                );
            }
        }
    }
}
