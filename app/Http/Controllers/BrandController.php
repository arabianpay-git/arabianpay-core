<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::select('brands.*')->paginate(10);
        return view('admin.brands.index', compact('brands'));
    }

    public function search(Request $request)
    {
        $query = $request->input('query', '');

        // Get all brands (or filtered encrypted fields)
        $brands = Brand::all();

        // Filter results manually for search
        $brands = $brands->filter(function ($brand) use ($query) {
            return str_contains(strtolower($brand->name), strtolower($query));
        });

        // Paginate manually
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new LengthAwarePaginator(
            $brands->forPage($page, $perPage),
            $brands->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('admin.brands.partials.table', ['brands' => $paginated])->render();
        }

        return view('admin.brands.index', ['brands' => $paginated]);
    }


    public function create()
    {
        return view('admin.brands.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'], // remove 'regex:/^[a-zA-Z\s]*$/'
            'logo' => ['required'],
            'order_level' => ['required', 'numeric'],
            'meta_title' => ['nullable', 'string', 'min:5', 'max:100',], // remove 'regex:/^[a-zA-Z\s]*$/'
            'meta_description' => ['nullable', 'string', 'min:10', 'max:255',], // remove 'regex:/^[a-zA-Z\s]*$/'
        ]);

        DB::beginTransaction();

        try {
            $brand = Brand::create([
                'name' => $request->name,
                'logo' => $request->logo,
                'order_level' => $request->order_level,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]);

            DB::commit();

            // Log the creation of the brand
            $batchUuid = (string) Str::uuid();
            $brand->logModelAction(
                event: 'create',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " created brand: {$brand->name} [{$brand->id}]",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid, // Add batch UUID for consistency
                ],
            );

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
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand->id),
            ], // remove 'regex:/^[a-zA-Z\s]*$/',
            'logo' => ['required'],
            'order_level' => ['required', 'numeric'],
            // 'meta_title.en' => ['nullable', 'string', 'min:5', 'max:100', 'regex:/^[a-zA-Z\s]*$/'],
            // 'meta_description.en' => ['nullable', 'string', 'min:10', 'max:255', 'regex:/^[a-zA-Z\s]*$/'],
        ]);

        DB::beginTransaction();

        try {
            $brand->update([
                'name' => $request->name['en'],
                'logo' => $request->logo,
                'order_level' => $request->order_level,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'],
                'meta_description' => $request->meta_description['en'],
            ]);

            $this->storeOrUpdateTranslations($brand, $request);

            DB::commit();
            //Log the update of the brand
            $batchUuid = (string) Str::uuid();
            $brand->logModelAction(
                event: 'update',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated brand: {$brand->name} [$brand->id]",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid, // Add batch UUID for consistency
                ],
            );

            return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(Brand $brand)
    {
        // log the deletion of the brand
        $batchUuid = (string) Str::uuid();
        $brand->logModelAction(
            event: 'delete',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " deleted brand: {$brand->name} [{$brand->id}]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => $batchUuid, // Add batch UUID for consistency
            ],
        );
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
    }

    private function storeOrUpdateTranslations(Brand $brand, Request $request)
    {
        if (isset($request->name['ar'])) {
            $brand->translations()->updateOrCreate(
                ['locale' => 'ar'],
                [
                    'name' => $request->name['ar'],
                    'meta_title' => $request->meta_title['ar'] ?? null,
                    'meta_description' => $request->meta_description['ar'] ?? null,
                ]
            );
        }
    }
}
