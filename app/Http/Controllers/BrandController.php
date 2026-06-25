<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Models\Brand;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        $brands = Brand::select('brands.*')->paginate(10);

        // Log view event using the new method
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Brand',
            'Viewed brands list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
            ]
        );

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

        // Log search event
        $this->auditTrailService->logSearch(
            'Brand',
            $query,
            $brands->count(),
            [
                'properties' => [
                    'search_type' => 'manual_filter',
                    'page' => $page,
                ],
            ]
        );

        if ($request->ajax()) {
            return view('admin.brands.partials.table', ['brands' => $paginated])->render();
        }

        return view('admin.brands.index', ['brands' => $paginated]);
    }

    public function create()
    {
        // Log view create form using the new method
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'Brand',
            'Viewed brand creation form'
        );

        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request)
    {

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

            // Log brand creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New brand created for business expansion',
                'business_operation',
                ['name', 'logo']
            );

            $this->auditTrailService->logCreated(
                $brand,
                "Created new brand '{$brand->name}'",
                $justificationData
            );

            DB::commit();

            return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'creation_failed',
                'entity_type' => 'Brand',
                'action_summary' => 'Failed to create brand',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', 'logo']),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function edit(Brand $brand)
    {
        // Log view edit form - this has an entity_id
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'Brand',
            'entity_id' => $brand->id,
            'action_summary' => "Viewed edit form for brand '{$brand->name}'",
        ]);

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
            ],
            'logo' => ['required'],
            'order_level' => ['required', 'numeric'],
        ]);

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $brand->toArray();

            $brand->update([
                'name' => $request->name['en'],
                'logo' => $request->logo,
                'order_level' => $request->order_level,
                'featured' => $request->boolean('featured'),
                'meta_title' => $request->meta_title['en'] ?? null,
                'meta_description' => $request->meta_description['en'] ?? null,
            ]);

            $this->storeOrUpdateTranslations($brand, $request);

            // Log brand update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Brand information updated to reflect current business requirements',
                'data_correction',
                ['name', 'order_level', 'logo']
            );

            $this->auditTrailService->logUpdated(
                $brand,
                $oldData,
                "Updated brand '{$brand->name}'",
                $justificationData
            );

            DB::commit();

            return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'update_failed',
                'entity_type' => 'Brand',
                'entity_id' => $brand->id,
                'action_summary' => "Failed to update brand '{$brand->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method', 'logo']),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function destroy(Brand $brand)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $brandData = $brand->toArray();

            // Log before deletion
            $justificationData = $this->auditTrailService->withJustification(
                'Brand removed due to business restructuring',
                'data_cleanup',
                ['name', 'logo']
            );

            $this->auditTrailService->logDeleted(
                $brand,
                "Deleted brand '{$brand->name}'",
                $justificationData
            );

            $brand->delete();

            DB::commit();

            return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'deletion_failed',
                'entity_type' => 'Brand',
                'entity_id' => $brand->id,
                'action_summary' => "Failed to delete brand '{$brand->name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
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

            // Log translation update
            $this->auditTrailService->log([
                'event_category' => 'localization',
                'event_type' => 'translation_update',
                'entity_type' => 'Brand',
                'entity_id' => $brand->id,
                'action_summary' => "Updated Arabic translation for brand '{$brand->name}'",
                'properties' => [
                    'locale' => 'ar',
                    'translated_fields' => ['name', 'meta_title', 'meta_description'],
                ],
            ]);
        }
    }
}
