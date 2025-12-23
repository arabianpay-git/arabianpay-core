<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\{Attribute, AttributeValue, Product, Category, Brand, User};
use App\Services\AuditTrailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        try {
            $query = Product::with(['category:id,name', 'brand:id,name', 'user:id,business_name'])
                ->select([
                    'id',
                    'name',
                    'thumbnail',
                    'unit_price',
                    'brand_id',
                    'user_id',
                    'current_stock',
                    'approved',
                    'published',
                    'reason_reject',
                    'created_at'
                ]);

            $request->validate([
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date', 'after_or_equal:from'],
            ], [
                'to.after_or_equal' => '"To Date" must be equal or after "From Date".',
            ]);

            $filters = [];
            if ($request->filled('from') && $request->filled('to')) {
                $query->whereBetween('created_at', [
                    $request->input('from') . ' 00:00:00',
                    $request->input('to') . ' 23:59:59',
                ]);
                $filters['date_range'] = $request->input('from') . ' to ' . $request->input('to');
            } else {
                if ($request->filled('from')) {
                    $query->whereDate('created_at', '>=', $request->input('from'));
                    $filters['from_date'] = $request->input('from');
                }

                if ($request->filled('to')) {
                    $query->whereDate('created_at', '<=', $request->input('to'));
                    $filters['to_date'] = $request->input('to');
                }
            }

            if ($request->filled('merchant_id')) {
                $query->where('user_id', $request->input('merchant_id'));
                $filters['merchant_id'] = $request->input('merchant_id');
            }

            $products = $query->latest()->paginate(10);

            // Calculate counts safely
            $approvedCount = $products->filter(function ($product) {
                return $product->approved === 'approved';
            })->count();

            $pendingCount = $products->filter(function ($product) {
                return $product->approved !== 'approved';
            })->count();

            // Log product list view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Product list view required for inventory management, pricing oversight, and merchant monitoring',
                'legitimate_interest',
                ['product_names', 'prices', 'merchant_ids']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'inventory_operations',
                'event_type' => 'product_list_view',
                'entity_type' => 'Product',
                'action_summary' => 'Viewed product list with filters',
                'properties' => [
                    'total_products' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'filters_applied' => $filters,
                    'approved_products_count' => $approvedCount,
                    'pending_products_count' => $pendingCount,
                    'viewed_by' => Auth::id(),
                    'viewed_by_type' => Auth::user()->user_type
                ]
            ], $justificationData));

            return view('admin.products.index', compact('products'));
        } catch (\Exception $e) {
            Log::error('Failed to load product list', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_list_failed',
                'entity_type' => 'Product',
                'action_summary' => 'Failed to load product list',
                'properties' => [
                    'error' => $e->getMessage(),
                    'filters' => $request->all(),
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load products. Please try again.');
        }
    }

    public function productApproval(Request $request)
    {
        try {
            $products = Product::with(['category:id,name', 'brand:id,name', 'user:id,business_name'])
                ->where('approved', '!=', 'approved')
                ->select(['id', 'name', 'thumbnail', 'unit_price', 'brand_id', 'user_id', 'current_stock', 'approved', 'published', 'created_at'])
                ->latest()
                ->paginate(10);

            // Calculate approval status distribution safely
            $approvalDistribution = [];
            foreach ($products as $product) {
                $status = $product->approved ?? 'unknown';
                if (!isset($approvalDistribution[$status])) {
                    $approvalDistribution[$status] = 0;
                }
                $approvalDistribution[$status]++;
            }

            // Log product approval queue view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Product approval queue view required for quality control, compliance verification, and merchant onboarding',
                'legitimate_interest',
                ['product_names', 'merchant_information', 'product_details']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'inventory_operations',
                'event_type' => 'product_approval_queue_view',
                'entity_type' => 'Product',
                'action_summary' => 'Viewed product approval queue',
                'properties' => [
                    'total_pending_approval' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'approval_status_distribution' => $approvalDistribution,
                    'viewed_by' => Auth::id(),
                    'viewed_by_type' => Auth::user()->user_type
                ]
            ], $justificationData));

            return view('admin.products.index', compact('products'));
        } catch (\Exception $e) {
            Log::error('Failed to load product approval queue', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_approval_queue_failed',
                'entity_type' => 'Product',
                'action_summary' => 'Failed to load product approval queue',
                'properties' => [
                    'error' => $e->getMessage(),
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load approval queue. Please try again.');
        }
    }

    public function productReviews(Request $request)
    {
        try {
            $products = Product::with(['user:id,business_name'])
                ->select(['id', 'name', 'thumbnail', 'brand_id', 'user_id', 'current_stock', 'approved', 'rating', 'created_at'])
                ->latest()
                ->paginate(10);

            // Calculate rating statistics safely
            $ratings = $products->pluck('rating')->filter(function ($rating) {
                return !is_null($rating);
            });

            $minRating = $ratings->isNotEmpty() ? $ratings->min() : 0;
            $maxRating = $ratings->isNotEmpty() ? $ratings->max() : 0;
            $avgRating = $ratings->isNotEmpty() ? $ratings->avg() : 0;

            // Log product reviews view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Product reviews view required for quality monitoring, customer feedback analysis, and product improvement',
                'legitimate_interest',
                ['product_names', 'customer_ratings', 'merchant_information']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'inventory_operations',
                'event_type' => 'product_reviews_view',
                'entity_type' => 'Product',
                'action_summary' => 'Viewed product reviews',
                'properties' => [
                    'total_products_with_reviews' => $products->total(),
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'average_rating_range' => [
                        'min' => $minRating,
                        'max' => $maxRating,
                        'avg' => $avgRating
                    ],
                    'viewed_by' => Auth::id(),
                    'viewed_by_type' => Auth::user()->user_type
                ]
            ], $justificationData));

            return view('admin.products.reviews', compact('products'));
        } catch (\Exception $e) {
            Log::error('Failed to load product reviews', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_reviews_view_failed',
                'entity_type' => 'Product',
                'action_summary' => 'Failed to load product reviews',
                'properties' => [
                    'error' => $e->getMessage(),
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load product reviews. Please try again.');
        }
    }

    public function create()
    {
        try {
            $user = Auth::user();

            // Log product creation form view
            $this->auditTrailService->log([
                'event_category' => 'inventory_operations',
                'event_type' => 'product_creation_form_view',
                'entity_type' => 'Product',
                'action_summary' => 'Viewed product creation form',
                'properties' => [
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'categories_count' => Category::count(),
                    'brands_count' => Brand::count(),
                    'attributes_count' => Attribute::count(),
                    'merchants_count' => User::where('user_type', 'merchant')->count(),
                    'timestamp' => now()->toISOString()
                ]
            ]);

            $categories = Category::orderBy('parent_id')
                ->orderBy('name')
                ->get();

            return view('admin.products.create', [
                'categories' => $categories,
                'brands' => Brand::orderBy('name')->get(),
                'attributes' => Attribute::orderBy('name')->get(),
                'merchants' => User::where('user_type', 'merchant')->select('id', 'business_name')->get(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load product creation form', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_creation_form_failed',
                'entity_type' => 'Product',
                'action_summary' => 'Failed to load product creation form',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->route('products.index')->with('error', 'Failed to load creation form. Please try again.');
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

            if ($error = $this->validateBusinessRules($data)) {
                // Log validation failure
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'product_creation_validation_failed',
                    'entity_type' => 'Product',
                    'action_summary' => 'Product creation failed business validation',
                    'properties' => [
                        'validation_errors' => $error,
                        'product_name' => $data['name']['en'] ?? 'Unknown',
                        'merchant_id' => $data['user_id'] ?? 'Unknown',
                        'unit_price' => $data['unit_price'] ?? 0,
                        'attempted_by' => $user->id
                    ]
                ]);

                return back()->withErrors($error)->withInput();
            }

            DB::beginTransaction();
            try {
                $data = $this->normalizeProductData($data, $request);

                $product = Product::create($data);

                // Sync attributes and their values on create
                $attributeIds = $data['attribute_id'] ?? [];
                $this->syncProductAttributes($product, $attributeIds, $request->input('attribute_value_id', []));

                // Store Arabic translations
                if ($request->filled('name.ar')) {
                    $product->translations()->create([
                        'locale' => 'ar',
                        'name' => $request->input('name.ar'),
                        'short_description' => $request->input('short_description.ar') ?? null,
                        'description' => $request->input('description.ar') ?? null,
                        'unit' => $request->input('unit.ar') ?? null,
                        'tags' => is_array($request->input('tags.ar', [])) && count($request->input('tags.ar'))
                            ? json_encode(array_map('trim', $request->input('tags.ar')))
                            : null,
                        'meta_title' => $request->input('meta_title.ar') ?? null,
                        'meta_description' => $request->input('meta_description.ar') ?? null,
                    ]);
                }

                // Log product creation with justification
                $justificationData = $this->auditTrailService->withJustification(
                    'Product creation required for inventory expansion, merchant support, and marketplace growth',
                    'legitimate_interest',
                    ['product_name', 'product_description', 'pricing_information', 'merchant_details']
                );

                $photosInput = $request->input('photos', []);
                $photosCount = is_array($photosInput) ? count($photosInput) : 0;

                $this->auditTrailService->logCreated(
                    $product,
                    'Created new product: ' . $product->name,
                    array_merge([
                        'event_category' => 'inventory_operations',
                        'event_type' => 'product_created',
                        'entity_type' => 'Product',
                        'properties' => [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'product_name_ar' => $request->input('name.ar'),
                            'merchant_id' => $product->user_id,
                            'category_id' => $product->category_id,
                            'brand_id' => $product->brand_id,
                            'unit_price' => $product->unit_price,
                            'purchase_price' => $product->purchase_price,
                            'current_stock' => $product->current_stock,
                            'attributes_count' => count($attributeIds),
                            'photos_count' => $photosCount,
                            'is_featured' => $product->featured,
                            'published_status' => $product->published,
                            'approval_status' => $product->approved,
                            'created_by' => $user->id,
                            'created_by_type' => $user->user_type,
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent()
                        ]
                    ], $justificationData)
                );

                DB::commit();

                // Log the activity using existing model method
                $batchUuid = (string) Str::uuid();
                $product->logModelAction(
                    event: 'create',
                    description: $user->first_name . " " . $user->last_name . " created product: {$product->name} [$product->id]",
                    properties: [
                        'reason' => $request->input('reason', null),
                        'ip' => request()->ip(),
                        'batch_uuid' => $batchUuid,
                    ],
                );

                return redirect()->route('products.index')->with('success', 'Product created successfully.');
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);

                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'product_creation_failed',
                    'entity_type' => 'Product',
                    'action_summary' => 'Failed to create product',
                    'properties' => [
                        'error' => $e->getMessage(),
                        'product_name' => $data['name']['en'] ?? 'Unknown',
                        'merchant_id' => $data['user_id'] ?? 'Unknown',
                        'attempted_by' => $user->id
                    ]
                ]);

                return back()->withErrors(['error' => 'Something went wrong.'])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Failed to store product', [
                'error' => $e->getMessage(),
                'request_data_keys' => array_keys($request->all()),
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_store_failed',
                'entity_type' => 'Product',
                'action_summary' => 'Failed to store product due to validation or system error',
                'properties' => [
                    'error' => $e->getMessage(),
                    'has_name' => $request->has('name'),
                    'has_category' => $request->has('category_id'),
                    'attempted_by' => Auth::id()
                ]
            ]);

            return back()->withErrors(['error' => 'Failed to create product. Please try again.'])->withInput();
        }
    }

    public function edit(Product $product)
    {
        try {
            $user = Auth::user();

            // Log product edit form view
            $justificationData = $this->auditTrailService->withJustification(
                'Product edit form view required for inventory updates, pricing adjustments, and content management',
                'legitimate_interest',
                ['product_name', 'product_details', 'pricing_information']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'inventory_operations',
                'event_type' => 'product_edit_form_view',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'action_summary' => 'Viewed product edit form',
                'properties' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'merchant_id' => $product->user_id,
                    'current_status' => [
                        'approved' => $product->approved ?? 'unknown',
                        'published' => $product->published ?? 'unknown',
                        'featured' => (bool) ($product->featured ?? false),
                        'stock' => $product->current_stock ?? 0,
                        'price' => $product->unit_price ?? 0
                    ],
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'is_product_owner' => $product->user_id == $user->id,
                    'has_attributes' => $product->attributes->count() > 0
                ]
            ], $justificationData));

            $product->load(['attributes.values']);

            $attributes = Attribute::with('values')->orderBy('name')->get();
            $attributeValues = [];
            foreach ($attributes as $attribute) {
                if ($attribute->name === 'Color') {
                    // For colors, include value and color_code
                    $attributeValues[$attribute->id] = $attribute->values->map(function ($v) {
                        return [
                            'value' => $v->value,
                            'color_code' => $v->color_code,
                            'ar' => $v->translations->where('locale', 'ar')->first()?->value ?? $v->value,
                        ];
                    })->toArray();
                } else {
                    // For other attributes, just use value
                    $attributeValues[$attribute->id] = $attribute->values->pluck('value')->toArray();
                }
            }

            $selectedAttributes = $product->attributes->mapWithKeys(function ($attribute) {
                return [$attribute->id => $attribute->pivot->attribute_value_id ?? null];
            });

            return view('admin.products.edit', [
                'product' => $product,
                'categories' => Category::orderBy('parent_id')->orderBy('name')->get(),
                'brands' => Brand::orderBy('name')->get(),
                'attributes' => $attributes,
                'merchants' => User::where('user_type', 'merchant')->get(),
                'attributeValues' => $attributeValues,
                'selectedAttributes' => $selectedAttributes,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load product edit form', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_edit_form_failed',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'action_summary' => 'Failed to load product edit form',
                'properties' => [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->route('products.index')->with('error', 'Failed to load edit form. Please try again.');
        }
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

            if ($error = $this->validateBusinessRules($data)) {
                // Log validation failure
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'product_update_validation_failed',
                    'entity_type' => 'Product',
                    'entity_id' => $product->id,
                    'action_summary' => 'Product update failed business validation',
                    'properties' => [
                        'validation_errors' => $error,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'attempted_price' => $data['unit_price'] ?? $product->unit_price,
                        'attempted_by' => $user->id
                    ]
                ]);

                return back()->withErrors($error)->withInput();
            }

            DB::beginTransaction();
            try {
                // Capture before state for audit
                $beforeState = $product->toArray();
                $beforeAttributes = $product->attributes->pluck('id')->toArray();

                $data = $this->normalizeProductData($data, $request);
                foreach ($product->getTranslatableFields() as $field) {
                    if (isset($data[$field]) && is_array($data[$field])) {
                        $data[$field] = $data[$field]['en'] ?? null;
                    }
                }

                $product->update($data);

                // Store Arabic translations
                if ($request->filled('name.ar')) {
                    $product->translations()->updateOrCreate(
                        ['locale' => 'ar'],
                        [
                            'name' => $request->input('name.ar'),
                            'short_description' => $request->input('short_description.ar') ?? null,
                            'description' => $request->input('description.ar') ?? null,
                            'unit' => $request->input('unit.ar') ?? null,
                            'tags' => is_array($request->input('tags.ar', [])) && count($request->input('tags.ar'))
                                ? json_encode(array_map('trim', $request->input('tags.ar')))
                                : null,
                            'meta_title' => $request->input('meta_title.ar') ?? null,
                            'meta_description' => $request->input('meta_description.ar') ?? null,
                        ]
                    );
                }

                // Sync attributes on update
                $attributeIds = $request->input('attribute_id', []);
                $valueIds = [];
                foreach ($request->input('attribute_value_id', []) as $i => $valueName) {
                    if (!empty($attributeIds[$i]) && !empty($valueName)) {
                        $attributeValue = AttributeValue::where('attribute_id', $attributeIds[$i])
                            ->where('value', $valueName)
                            ->first();
                        $valueIds[$i] = $attributeValue->id ?? null;
                    }
                }
                $this->syncProductAttributes($product, $attributeIds, $valueIds);

                // Variants handling
                if ($request->filled('variants')) {
                    $product->update(['variants' => $this->buildStructuredVariants($request->input('variants'))]);
                } else {
                    $product->update(['variants' => null]);
                }

                // Reload product to get updated attributes
                $product->load('attributes');
                $afterAttributes = $product->attributes->pluck('id')->toArray();

                // Log product update with justification
                $justificationData = $this->auditTrailService->withJustification(
                    'Product update required for inventory management, pricing updates, and content accuracy',
                    'legitimate_interest',
                    ['product_name', 'product_details', 'pricing_information']
                );

                $changedFields = $this->getProductChangedFields($beforeState, $product->toArray());

                $this->auditTrailService->logUpdated(
                    $product,
                    $beforeState,
                    'Updated product: ' . $product->name,
                    array_merge([
                        'event_category' => 'inventory_operations',
                        'event_type' => 'product_updated',
                        'entity_type' => 'Product',
                        'properties' => [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'merchant_id' => $product->user_id,
                            'changed_fields' => $changedFields,
                            'price_changes' => [
                                'old_unit_price' => $beforeState['unit_price'] ?? null,
                                'new_unit_price' => $product->unit_price,
                                'old_purchase_price' => $beforeState['purchase_price'] ?? null,
                                'new_purchase_price' => $product->purchase_price
                            ],
                            'stock_changes' => [
                                'old_stock' => $beforeState['current_stock'] ?? null,
                                'new_stock' => $product->current_stock
                            ],
                            'attribute_changes' => [
                                'old_attributes' => $beforeAttributes,
                                'new_attributes' => $afterAttributes,
                                'attributes_added' => array_diff($afterAttributes, $beforeAttributes),
                                'attributes_removed' => array_diff($beforeAttributes, $afterAttributes)
                            ],
                            'status_changes' => [
                                'old_approved' => $beforeState['approved'] ?? null,
                                'new_approved' => $product->approved,
                                'old_published' => $beforeState['published'] ?? null,
                                'new_published' => $product->published,
                                'old_featured' => $beforeState['featured'] ?? null,
                                'new_featured' => $product->featured
                            ],
                            'updated_by' => $user->id,
                            'updated_by_type' => $user->user_type,
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent()
                        ]
                    ], $justificationData)
                );

                DB::commit();

                // Log the activity using existing model method
                $batchUuid = (string) Str::uuid();
                $product->logModelAction(
                    event: 'update',
                    description: $user->first_name . " " . $user->last_name . " updated product: {$product->name} [$product->id]",
                    properties: [
                        'reason' => $request->input('reason', null),
                        'ip' => request()->ip(),
                        'batch_uuid' => $batchUuid,
                    ],
                );

                $queryParams = $request->only(['page', 'from', 'to', 'merchant_id']);

                return redirect()->route('products.index', $queryParams)
                    ->with('success', 'Product updated successfully.');
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);

                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'product_update_failed',
                    'entity_type' => 'Product',
                    'entity_id' => $product->id,
                    'action_summary' => 'Failed to update product',
                    'properties' => [
                        'error' => $e->getMessage(),
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'attempted_by' => $user->id
                    ]
                ]);

                return back()->withErrors(['error' => 'Something went wrong.'])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_update_process_failed',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'action_summary' => 'Failed to process product update',
                'properties' => [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return back()->withErrors(['error' => 'Failed to update product. Please try again.'])->withInput();
        }
    }

    public function show($userId)
    {
        try {
            $user = Auth::user();
            $merchant = User::find($userId);

            if (!$merchant) {
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'merchant_products_not_found',
                    'entity_type' => 'Product',
                    'action_summary' => 'Attempted to view products for non-existent merchant',
                    'properties' => [
                        'merchant_id' => $userId,
                        'requested_by' => $user->id
                    ]
                ]);

                return response()->json(['error' => 'Merchant not found'], 404);
            }

            $products = Product::where('user_id', $userId)
                ->select('id', 'name')
                ->get();

            // Log merchant products view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Merchant products view required for inventory auditing, merchant support, and product verification',
                'legitimate_interest',
                ['merchant_information', 'product_names']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'inventory_operations',
                'event_type' => 'merchant_products_view',
                'entity_type' => 'Product',
                'action_summary' => 'Viewed products for merchant',
                'properties' => [
                    'merchant_id' => $userId,
                    'merchant_name' => $merchant->business_name,
                    'products_count' => $products->count(),
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type,
                    'api_request' => true
                ]
            ], $justificationData));

            return response()->json([
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch merchant products', [
                'error' => $e->getMessage(),
                'merchant_id' => $userId,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'merchant_products_fetch_failed',
                'entity_type' => 'Product',
                'action_summary' => 'Failed to fetch merchant products',
                'properties' => [
                    'error' => $e->getMessage(),
                    'merchant_id' => $userId,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            $user = Auth::user();

            // Capture before state for audit
            $beforeState = $product->toArray();

            // Log product deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Product deletion required for inventory cleanup, compliance, and merchant request fulfillment',
                'legal_obligation',
                ['product_name', 'merchant_information', 'product_history']
            );

            $this->auditTrailService->logDeleted(
                $product,
                'Deleted product: ' . $product->name,
                array_merge([
                    'event_category' => 'inventory_operations',
                    'event_type' => 'product_deleted',
                    'entity_type' => 'Product',
                    'properties' => [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'merchant_id' => $product->user_id,
                        'category_id' => $product->category_id,
                        'brand_id' => $product->brand_id,
                        'unit_price' => $product->unit_price,
                        'current_stock' => $product->current_stock,
                        'attributes_count' => $product->attributes->count(),
                        'approval_status' => $product->approved,
                        'published_status' => $product->published,
                        'deleted_by' => $user->id,
                        'deleted_by_type' => $user->user_type,
                        'deleted_at' => now()->toISOString(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent()
                    ]
                ], $justificationData)
            );

            // Log the activity using existing model method
            $batchUuid = (string) Str::uuid();
            $product->logModelAction(
                event: 'delete',
                description: $user->first_name . " " . $user->last_name . " deleted product: {$product->name} [$product->id]",
                properties: [
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid,
                ],
            );

            $product->delete();

            return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete product', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'product_deletion_failed',
                'entity_type' => 'Product',
                'entity_id' => $product->id,
                'action_summary' => 'Failed to delete product',
                'properties' => [
                    'error' => $e->getMessage(),
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return redirect()->route('products.index')->with('error', 'Failed to delete product. Please try again.');
        }
    }

    // Private Helpers
    private function validateBusinessRules(array $data): ?array
    {
        if (!empty($data['purchase_price']) && $data['purchase_price'] > $data['unit_price']) {
            return ['purchase_price' => 'Cost per item cannot be greater than the unit price.'];
        }
        if (!empty($data['discount']) && $data['discount'] > $data['unit_price']) {
            return ['discount' => 'Discount cannot be greater than the unit price.'];
        }
        if (!empty($data['discount_start_date']) && !empty($data['discount_end_date'])) {
            $start = Carbon::parse($data['discount_start_date']);
            $end = Carbon::parse($data['discount_end_date']);
            if ($start->greaterThan($end)) {
                return ['discount_end_date' => 'Discount end date must be after start date.'];
            }
        }
        // Validate against credit limit
        //$creditLimit = get_credit_limit($data['user_id']);
        //if (!is_null($creditLimit) && !empty($data['unit_price']) && $data['unit_price'] > $creditLimit) {
        //  return ['unit_price' => 'Unit price cannot exceed the credit limit.'];
        //}
        return null;
    }

    private function normalizeProductData(array $data, Request $request): array
    {
        $data['featured'] = !empty($data['featured']);

        if ($request->has('published')) {
            $data['published'] = $request->published;
        } else {
            $data['published'] = $request->action === 'publish' ? 'published' : 'pending';
        }

        if (!empty($data['tags']['en']) && is_array($data['tags']['en'])) {
            $data['tags'] = json_encode(array_map('trim', $data['tags']['en']));
        } else {
            $data['tags'] = null;
        }

        if (!empty($data['photos']) && is_array($data['photos'])) {
            $data['photos'] = json_encode(array_map('strval', $data['photos']));
        }

        return $data;
    }

    private function syncProductAttributes(Product $product, array $attributeIds, array $valueIds = []): void
    {
        $syncData = [];
        foreach ($attributeIds as $i => $attributeId) {
            if (!empty($attributeId)) {
                $syncData[$attributeId] = ['attribute_value_id' => $valueIds[$i] ?? null];
            }
        }
        $product->attributes()->sync($syncData);
    }

    private function buildStructuredVariants(array $rawVariants): array
    {
        $structured = [];
        foreach ($rawVariants as $key => $data) {
            if (!empty($data['attribute_id']) && !empty($data['value'])) {
                $attrIds = explode(',', $data['attribute_id']);
                $valueNames = explode(' / ', $data['value'] ?? '');
                $attributes = [];

                foreach ($attrIds as $i => $aid) {
                    if (!empty($aid)) {
                        $attribute = Attribute::find($aid);
                        $valueName = $valueNames[$i] ?? null;
                        $attributeValue = null;

                        if (!empty($valueName)) {
                            $attributeValue = AttributeValue::where('attribute_id', $aid)
                                ->where('value', $valueName)
                                ->first();
                        }

                        $attributes[] = [
                            'attribute_id' => (int)$aid,
                            'attribute_value_id' => $attributeValue ? $attributeValue->id : null,
                            'attribute' => $attribute ? $attribute->name : null,
                            'value' => $valueName,
                        ];
                    }
                }

                $structured[] = [
                    'id' => $key,
                    'attributes' => $attributes,
                    'price' => $data['price'] ?? null,
                    'sku' => $data['sku'] ?? null,
                    'variant_image' => $data['variant_image'] ?? null,
                ];
            }
        }

        return $structured;
    }

    /**
     * Helper method to identify changed fields in product updates
     *
     * @param array $beforeState
     * @param array $afterState
     * @return array
     */
    private function getProductChangedFields(array $beforeState, array $afterState): array
    {
        $changed = [];
        $sensitiveFields = ['user_id', 'name', 'description', 'short_description'];

        foreach ($beforeState as $key => $value) {
            if (isset($afterState[$key]) && $afterState[$key] != $value) {
                if (in_array($key, $sensitiveFields)) {
                    if ($key === 'name' || $key === 'description' || $key === 'short_description') {
                        $changed[$key] = [
                            'old_length' => strlen($value ?? ''),
                            'new_length' => strlen($afterState[$key] ?? ''),
                            'changed' => true
                        ];
                    } else {
                        $changed[$key] = [
                            'old' => '***MASKED***',
                            'new' => '***MASKED***',
                            'changed' => true
                        ];
                    }
                } else {
                    $changed[$key] = [
                        'old' => $value,
                        'new' => $afterState[$key]
                    ];
                }
            }
        }

        // Check for new fields that weren't in before state
        foreach ($afterState as $key => $value) {
            if (!isset($beforeState[$key])) {
                if (in_array($key, $sensitiveFields)) {
                    if ($key === 'name' || $key === 'description' || $key === 'short_description') {
                        $changed[$key] = [
                            'old' => null,
                            'new_length' => strlen($value ?? '')
                        ];
                    } else {
                        $changed[$key] = [
                            'old' => null,
                            'new' => '***MASKED***'
                        ];
                    }
                } else {
                    $changed[$key] = [
                        'old' => null,
                        'new' => $value
                    ];
                }
            }
        }

        return $changed;
    }
}
