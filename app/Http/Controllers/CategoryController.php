<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::with('parent')->select('categories.*');
            return DataTables::of($data)
                ->addColumn('parent', fn($row) => $row->parent?->name ?? '-')
                ->addColumn('actions', fn($row) => '
                    <a href="' . route('categories.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>
                    <a href="' . route('categories.destroy', $row->id) . '" class="btn btn-sm btn-danger delete-btn" data-id="' . $row->id . '">Delete</a>
                ')
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.categories.index');
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $category = Category::create([
                'parent_id' => $request->parent_id,
                'name' => $request->name['en'],
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'],
                'meta_description' => $request->meta_description['en'],
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(Category $category)
    {
        $categories = Category::where('id', '!=', $category->id)->get();
        return view('admin.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name.en' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $category->update([
                'parent_id' => $request->parent_id,
                'name' => $request->name['en'],
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'],
                'meta_description' => $request->meta_description['en'],
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }

    private function storeOrUpdateTranslation(Category $category, Request $request)
    {
        if (isset($request->name['ar'])) {
            $category->translations()->updateOrCreate(
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
