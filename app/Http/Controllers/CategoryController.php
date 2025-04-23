<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('parent')->select('categories.*')->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/', 'unique:categories,name'],
            'order_level' => ['nullable', 'numeric'],
            'meta_title' => ['nullable', 'string', 'min:5', 'max:100', 'regex:/^[a-zA-Z\s]*$/'],
            'meta_description' => ['nullable', 'string', 'min:10', 'max:255', 'regex:/^[a-zA-Z\s]*$/'],
        ]);

        DB::beginTransaction();

        try {
            $category = Category::create([
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
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
            'name.en' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'order_level' => ['nullable', 'numeric'],
            'meta_title.en' => ['nullable', 'string', 'min:5', 'max:100', 'regex:/^[a-zA-Z\s]*$/'],
            'meta_description.en' => ['nullable', 'string', 'min:10', 'max:255', 'regex:/^[a-zA-Z\s]*$/'],
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
