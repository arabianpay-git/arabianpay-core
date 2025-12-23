<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        // Log view categories list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Category',
            'Viewed categories list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
                'order_by' => 'id_desc',
            ]
        );

        $categories = Category::with('parent')->select('categories.*')->orderBy('id', 'desc')->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function search(Request $request)
    {
        $query = $request->input('query', '');

        // Log search operation
        $this->auditTrailService->logViewOperation(
            'search_categories',
            'Category',
            "Searched categories with query: '{$query}'",
            [
                'search_type' => 'manual_filter',
                'query' => $query,
            ]
        );

        // Get all categories
        $categories = Category::with('parent')->get();

        // Filter for encrypted fields or any other field
        $categories = $categories->filter(function ($category) use ($query) {
            $q = strtolower($query);
            return str_contains(strtolower($category->name), $q)
                || str_contains(strtolower($category->parent?->name ?? ''), $q);
        });

        // Paginate filtered results
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $categories->forPage($page, $perPage),
            $categories->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        // Log search results
        $this->auditTrailService->log([
            'event_category' => 'search_operations',
            'event_type' => 'category_search_results',
            'entity_type' => 'Category',
            'action_summary' => "Category search completed - Found {$categories->count()} results for query: '{$query}'",
            'properties' => [
                'search_query' => $query,
                'results_count' => $categories->count(),
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);

        if ($request->ajax()) {
            return view('admin.categories.partials.table', ['categories' => $paginated])->render();
        }

        return view('admin.categories.index', ['categories' => $paginated]);
    }

    public function create()
    {
        // Log view create form
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'Category',
            'Viewed category creation form'
        );

        $categories = Category::all();
        return view('admin.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'order_level' => ['required', 'numeric'],
            'meta_title' => ['nullable', 'string', 'min:5', 'max:100'],
            'meta_description' => ['nullable', 'string', 'min:10', 'max:255'],
            'unit' => ['nullable', 'array'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        DB::beginTransaction();

        try {
            $category = Category::create([
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'order_level' => $request->order_level ?? 1,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'unit' => collect($request->unit)->flatMap(fn($item) => explode(',', $item))->map('trim')->filter()->values(),
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            // Log category creation with justification
            $parentName = $category->parent ? $category->parent->name : 'Root';
            $justificationData = $this->auditTrailService->withJustification(
                'New category created for product organization',
                'business_operation',
                ['name', 'meta_title', 'meta_description']
            );

            $this->auditTrailService->logCreated(
                $category,
                "Created category '{$category->name}' under parent: {$parentName}",
                $justificationData
            );

            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'category_creation_failed',
                'entity_type' => 'Category',
                'action_summary' => 'Failed to create category',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', 'banner', 'icon']),
                    'parent_id' => $request->parent_id,
                    'featured' => $request->boolean('featured'),
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit(Category $category)
    {
        // Log view edit form
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'Category',
            'entity_id' => $category->id,
            'action_summary' => "Viewed edit form for category '{$category->name}'",
            'properties' => [
                'category_id' => $category->id,
                'current_name' => $category->name,
                'parent_id' => $category->parent_id,
                'order_level' => $category->order_level,
                'featured' => $category->featured,
            ],
        ]);

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
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'meta_title.en' => ['nullable', 'string', 'max:255'],
            'meta_description.en' => ['nullable', 'string', 'max:1000'],
            'unit' => ['nullable', 'array'],
            'order_level' => ['required', 'numeric'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $category->toArray();
            $oldParentId = $category->parent_id;

            $category->update([
                'parent_id' => $request->parent_id,
                'name' => $request->name['en'],
                'order_level' => $request->order_level,
                'banner' => $request->banner,
                'icon' => $request->icon,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'],
                'meta_description' => $request->meta_description['en'],
                'unit' => collect($request->unit)->flatMap(fn($item) => explode(',', $item))->map('trim')->filter()->values(),
            ]);

            $this->storeOrUpdateTranslation($category, $request);

            // Prepare update summary with changes
            $changes = [];
            if ($oldData['name'] !== $category->name) {
                $changes[] = "name: '{$oldData['name']}' to '{$category->name}'";
            }
            if ($oldParentId !== $request->parent_id) {
                $oldParent = $oldParentId ? Category::find($oldParentId) : null;
                $newParent = $request->parent_id ? Category::find($request->parent_id) : null;
                $oldParentName = $oldParent ? $oldParent->name : 'Root';
                $newParentName = $newParent ? $newParent->name : 'Root';
                $changes[] = "parent: '{$oldParentName}' to '{$newParentName}'";
            }
            if ($oldData['order_level'] != $category->order_level) {
                $changes[] = "order level: {$oldData['order_level']} to {$category->order_level}";
            }
            if ($oldData['featured'] != $request->boolean('featured')) {
                $oldStatus = $oldData['featured'] ? 'featured' : 'not featured';
                $newStatus = $request->boolean('featured') ? 'featured' : 'not featured';
                $changes[] = "featured status: {$oldStatus} to {$newStatus}";
            }
            if ($oldData['meta_title'] !== $category->meta_title) {
                $changes[] = "meta title updated";
            }
            if ($oldData['meta_description'] !== $category->meta_description) {
                $changes[] = "meta description updated";
            }

            $changeSummary = !empty($changes) ? ' (' . implode(', ', $changes) . ')' : '';

            // Log category update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Category updated to improve product organization and SEO',
                'data_correction',
                ['name', 'meta_title', 'meta_description']
            );

            $this->auditTrailService->logUpdated(
                $category,
                $oldData,
                "Updated category '{$category->name}'{$changeSummary}",
                $justificationData
            );

            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'category_update_failed',
                'entity_type' => 'Category',
                'entity_id' => $category->id,
                'action_summary' => "Failed to update category '{$category->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method', 'banner', 'icon']),
                    'old_name' => $category->name,
                    'parent_change' => $oldParentId !== $request->parent_id,
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function destroy(Category $category)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $categoryData = $category->toArray();

            // Check if category has subcategories
            $subcategoryCount = Category::where('parent_id', $category->id)->count();
            $usageWarning = $subcategoryCount > 0 ? " (Warning: Has {$subcategoryCount} subcategor(ies))" : '';

            // Log before deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Category removed to streamline product catalog',
                'data_cleanup',
                ['name', 'meta_title', 'meta_description']
            );

            $this->auditTrailService->logDeleted(
                $category,
                "Deleted category '{$category->name}'{$usageWarning}",
                $justificationData
            );

            $category->delete();

            DB::commit();

            return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'category_deletion_failed',
                'entity_type' => 'Category',
                'entity_id' => $category->id,
                'action_summary' => "Failed to delete category '{$category->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'category_id' => $category->id,
                    'subcategory_count' => $subcategoryCount,
                ],
            ]);

            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    private function storeOrUpdateTranslation(Category $category, Request $request)
    {
        if (isset($request->name['ar'])) {
            DB::beginTransaction();

            try {
                $oldTranslation = $category->translations()->where('locale', 'ar')->first();
                $oldTranslationData = $oldTranslation ? $oldTranslation->toArray() : null;

                $category->translations()->updateOrCreate(
                    ['locale' => 'ar'],
                    [
                        'name' => $request->name['ar'],
                        'meta_title' => $request->meta_title['ar'] ?? null,
                        'meta_description' => $request->meta_description['ar'] ?? null,
                    ]
                );

                // Log translation update
                $action = $oldTranslation ? 'updated' : 'created';

                $this->auditTrailService->log([
                    'event_category' => 'localization',
                    'event_type' => 'category_translation_' . $action,
                    'entity_type' => 'Category',
                    'entity_id' => $category->id,
                    'action_summary' => "{$action} Arabic translation for category '{$category->name}'",
                    'before_state' => $oldTranslationData,
                    'after_state' => [
                        'locale' => 'ar',
                        'name' => $request->name['ar'],
                        'meta_title' => $request->meta_title['ar'] ?? null,
                        'meta_description' => $request->meta_description['ar'] ?? null,
                        'category_id' => $category->id,
                    ],
                    'properties' => [
                        'locale' => 'ar',
                        'category_id' => $category->id,
                        'english_name' => $category->name,
                        'arabic_name' => $request->name['ar'],
                        'translation_action' => $action,
                    ],
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                // Log failed translation update
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'category_translation_failed',
                    'entity_type' => 'Category',
                    'entity_id' => $category->id,
                    'action_summary' => "Failed to update Arabic translation for category '{$category->name}'",
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'locale' => 'ar',
                        'category_id' => $category->id,
                    ],
                ]);

                throw $e;
            }
        }
    }

    public function getUnits($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['units' => []]);
        }

        // Log units retrieval
        $this->auditTrailService->log([
            'event_category' => 'data_operations',
            'event_type' => 'get_category_units',
            'entity_type' => 'Category',
            'entity_id' => $category->id,
            'action_summary' => "Retrieved units for category '{$category->name}'",
            'properties' => [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'parent_id' => $category->parent_id,
            ],
        ]);

        // Use parent's units if parent exists
        if ($category->parent_id) {
            $parentCategory = Category::find($category->parent_id);
            $units = $parentCategory ? $parentCategory->unit : null;
        } else {
            $units = $category->unit;
        }

        // Convert units string or array to array of trimmed strings
        if (is_string($units)) {
            $units = explode(',', $units);
        } elseif (is_array($units)) {
            if (count($units) === 1 && str_contains($units[0], ',')) {
                $units = explode(',', $units[0]);
            }
        } else {
            $units = [];
        }

        return response()->json([
            'units' => array_map('trim', $units),
        ]);
    }
}
