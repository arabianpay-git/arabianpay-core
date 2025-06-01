<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\{Attribute, AttributeValue, Product, Category, Brand, User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category:id,name', 'brand:id,name'])
            ->select(['id', 'name', 'thumbnail', 'unit_price', 'brand_id', 'current_stock', 'approved', 'published', 'created_at'])
            ->latest()
            ->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    public function productApproval(Request $request)
    {
        $products = Product::with(['category:id,name', 'brand:id,name'])
            ->where('approved', '!=', 'approved')
            ->select(['id', 'name', 'thumbnail', 'unit_price', 'brand_id', 'current_stock', 'approved', 'published', 'created_at'])
            ->latest()
            ->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    public function productReviews(Request $request)
    {
        $products = Product::with(['brand:id,name'])
            ->select(['id', 'name', 'thumbnail', 'brand_id', 'current_stock', 'approved', 'rating', 'created_at'])
            ->latest()
            ->paginate(10);

        return view('admin.products.reviews', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create', [
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'attributes' => Attribute::orderBy('name')->get(),
            'merchants' => User::where('user_type', 'merchant')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($error = $this->validateBusinessRules($data)) {
            return back()->withErrors($error)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $this->normalizeProductData($data, $request);

            $product = Product::create($data);

            // Sync attributes and their values on create
            $this->syncProductAttributes($product, $data['attribute_id'] ?? [], $request->input('attribute_value_id', []));

            DB::commit();

            // Log the activity
            $batchUuid = (string) Str::uuid();
            $product->logModelAction(
                event: 'create',
                description: auth()->user()->first_name." ".auth()->user()->last_name." created product: {$product->name} [$product->id]",
                properties: [
                    'reason' => $request->input('reason', null), // reson can be optional
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid, // Add batch UUID for consistency
                ],
            );

            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['error' => 'Something went wrong.'])->withInput();
        }
    }

    public function edit(Product $product)
    {
        $product->load(['attributes.values']);

        $attributes = Attribute::with('values')->orderBy('name')->get();
        $attributeValues = [];
        foreach ($attributes as $attribute) {
            $attributeValues[$attribute->id] = $attribute->values->pluck('value')->toArray();
        }

        $selectedAttributes = $product->attributes->mapWithKeys(function ($attribute) {
            return [$attribute->id => $attribute->pivot->attribute_value_id];
        });

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'attributes' => $attributes,
            'merchants' => User::where('user_type', 'merchant')->get(),
            'attributeValues' => $attributeValues,
            'selectedAttributes' => $selectedAttributes,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        // dd($request->all());
        $data = $request->validated();

        if ($error = $this->validateBusinessRules($data)) {
            return back()->withErrors($error)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $this->normalizeProductData($data, $request);
            foreach ($product->getTranslatableFields() as $field) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    $data[$field] = $data[$field]['en'] ?? null;
                }
            }

            $product->update($data);

            // Store Arabic translations
            $product->translations()->updateOrCreate(
                ['locale' => 'ar'],
                [
                    'name' => $request->input('name.ar'),
                    'short_description' => $request->input('short_description.ar'),
                    'description' => $request->input('description.ar'),
                    'unit' => $request->input('unit.ar'),
                    'tags' => is_array($request->input('tags.ar', [])) && count($request->input('tags.ar'))
                        ? json_encode(array_map('trim', $request->input('tags.ar')))
                        : null,
                    'meta_title' => $request->input('meta_title.ar'),
                    'meta_description' => $request->input('meta_description.ar'),
                ]
            );

            // Sync attributes on update
            $attributeIds = $request->input('attribute_id', []);
            $valueIds = [];
            foreach ($request->input('attribute_value_id', []) as $i => $valueName) {
                $attributeValue = AttributeValue::where('attribute_id', $attributeIds[$i] ?? null)
                    ->where('value', $valueName)
                    ->first();
                $valueIds[$i] = $attributeValue->id ?? null;
            }
            $this->syncProductAttributes($product, $attributeIds, $valueIds);

            // Variants handling
            if ($request->filled('variants')) {
                $product->update(['variants' => $this->buildStructuredVariants($request->input('variants'))]);
            } else {
                $product->update(['variants' => null]);
            }

            DB::commit();
            // Log the activity
            $batchUuid = (string) Str::uuid();

            $product->logModelAction(
                event: 'update',
                description: auth()->user()->first_name." ".auth()->user()->last_name." update product: {$product->name} [$product->id]",
                properties: [
                    'reason' => $reason ?? null, // reson can be optional
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid, // Add batch UUID for consistency
                ],
            );

            return redirect()->route('products.index')->with('success', 'Product updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['error' => 'Something went wrong.'])->withInput();
        }
    }

    public function show($userId)
    {
        $merchant = User::find($userId);

        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 404);
        }

        $products = Product::where('user_id', $userId)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'products' => $products
        ]);
    }

    public function destroy(Product $product)
    {
        
        // Log the activity
        $batchUuid = (string) Str::uuid();
        $product->logModelAction(
            event: 'delete',
            description: auth()->user()->first_name." ".auth()->user()->last_name." deleted product: {$product->name} [$product->id]",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => $batchUuid, // Add batch UUID for consistency
            ],
        );

        $product->delete();
        
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
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
            $syncData[$attributeId] = ['attribute_value_id' => $valueIds[$i] ?? null];
        }
        $product->attributes()->sync($syncData);
    }

    private function buildStructuredVariants(array $rawVariants): array
    {
        return collect($rawVariants)->map(function ($data, $key) {
            $attrIds = explode(',', $data['attribute_id']);
            $valueNames = explode(' / ', $data['value'] ?? '');
            $attributes = collect($attrIds)->map(function ($aid, $i) use ($valueNames) {
                $attributeName = optional(Attribute::find($aid))->name;
                $valueName = $valueNames[$i] ?? null;
                $attributeValue = AttributeValue::where('attribute_id', $aid)
                    ->where('value', $valueName)
                    ->first();
                return [
                    'attribute_id' => (int)$aid,
                    'attribute_value_id' => optional($attributeValue)->id,
                    'attribute' => $attributeName,
                    'value' => $valueName,
                ];
            });
            return [
                'id' => $key,
                'attributes' => $attributes,
                'price' => $data['price'] ?? null,
                'sku' => $data['sku'] ?? null,
                'variant_image' => $data['variant_image'] ?? null,
            ];
        })->toArray();
    }
}
