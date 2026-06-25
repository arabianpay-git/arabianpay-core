<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function index()
    {
        // Log view operation for coupon list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Coupon',
            'Viewed coupons list',
            [
                'page' => request()->get('page', 1),
                'per_page' => 15,
            ]
        );

        $coupons = Coupon::latest()->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        // Log view create form
        $this->auditTrailService->logViewOperation(
            'view_create_form',
            'Coupon',
            'Viewed coupon creation form'
        );

        return view('admin.coupons.create', [
            'merchants' => User::where('user_type', 'merchant')->select('id', 'business_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'type' => 'required|in:product_base,total_order',
            'code' => 'required|regex:/^[a-zA-Z\s]*$/|unique:coupons,code',
            'discount' => 'required|numeric',
            'discount_type' => 'required|in:percent,amount',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'products' => 'required_if:type,product_base|array|min:1',
        ]);

        if ($validator->fails()) {
            // Log validation failure
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'coupon_validation_failed',
                'entity_type' => 'Coupon',
                'action_summary' => 'Coupon creation validation failed',
                'properties' => [
                    'errors' => $validator->errors()->toArray(),
                    'input_data' => $request->except(['_token']),
                ],
            ]);

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $validated = $validator->validated();
            $validated['added_by'] = Auth::user()->user_type;

            if ($request->has('user_id')) {
                $merchant = User::find($request->user_id);
                $validated['owner'] = $merchant ? $merchant->user_type : null;
            }

            $details = [];

            if ($request->has('products')) {
                $details['products'] = $request->input('products');
            }

            if ($request->has('min_order_amount')) {
                $details['min_order_amount'] = $request->input('min_order_amount');
            }

            if ($request->has('max_discount_amount')) {
                $details['max_discount_amount'] = $request->input('max_discount_amount');
            }

            $validated['details'] = json_encode($details);

            $coupon = Coupon::create($validated);

            // Log coupon creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New coupon created for marketing campaign',
                'business_operation',
                ['code'] // Coupon code is business data
            );

            $this->auditTrailService->logCreated(
                $coupon,
                "Created new coupon: {$coupon->code}",
                array_merge([
                    'discount' => $coupon->discount,
                    'discount_type' => $coupon->discount_type,
                    'type' => $coupon->type,
                    'start_date' => $coupon->start_date,
                    'end_date' => $coupon->end_date,
                    'added_by' => $coupon->added_by,
                    'owner' => $coupon->owner,
                    'product_count' => count($request->input('products', [])),
                    'min_order_amount' => $request->input('min_order_amount'),
                    'max_discount_amount' => $request->input('max_discount_amount'),
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('coupons.index')->with('success', 'Coupon created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'coupon_creation_failed',
                'entity_type' => 'Coupon',
                'action_summary' => 'Failed to create coupon',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token']),
                    'coupon_code' => $request->input('code'),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);

        // Log view edit form with entity_id
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_edit_form',
            'entity_type' => 'Coupon',
            'entity_id' => $coupon->id,
            'action_summary' => "Viewed edit form for coupon: {$coupon->code}",
        ]);

        $merchants = User::where('user_type', 'merchant')->select('id', 'business_name')->get();

        $products = [];

        if ($coupon->type === 'product_base' && $coupon->details) {
            $details = $coupon->details;
            $products = $details['products'] ?? [];
        }

        $min_order_amount = $details['min_order_amount'] ?? null;
        $max_discount_amount = $details['max_discount_amount'] ?? null;

        $allProducts = Product::all();

        return view('admin.coupons.edit', compact('coupon', 'merchants', 'products', 'allProducts', 'min_order_amount', 'max_discount_amount'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'type' => 'required|in:product_base,total_order',
            'code' => 'required|regex:/^[a-zA-Z\s]*$/|unique:coupons,code,'.$coupon->id,
            'discount' => 'required|numeric',
            'discount_type' => 'required|in:percent,amount',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'products' => 'required_if:type,product_base|array|min:1',
            'min_order_amount' => 'nullable|numeric',
            'max_discount_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            // Log validation failure
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'coupon_update_validation_failed',
                'entity_type' => 'Coupon',
                'entity_id' => $coupon->id,
                'action_summary' => "Coupon update validation failed for coupon: {$coupon->code}",
                'properties' => [
                    'errors' => $validator->errors()->toArray(),
                    'input_data' => $request->except(['_token', '_method']),
                ],
            ]);

            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            // Get old data for audit trail
            $oldData = $coupon->toArray();

            // Handle details properly - check if it's already an array or needs decoding
            $oldDetails = [];
            if (! empty($coupon->details)) {
                if (is_array($coupon->details)) {
                    $oldDetails = $coupon->details;
                } else {
                    $oldDetails = json_decode($coupon->details, true) ?? [];
                }
            }

            $validated = $validator->validated();
            $validated['added_by'] = Auth::user()->user_type;

            if ($request->has('user_id')) {
                $merchant = User::find($request->user_id);
                $validated['owner'] = $merchant ? $merchant->user_type : null;
            }

            if ($request->type === 'product_base') {
                $request->merge([
                    'min_order_amount' => null,
                    'max_discount_amount' => null,
                ]);
            }

            $details = [];

            if ($request->has('products')) {
                $details['products'] = $request->input('products');
            }

            if ($request->has('min_order_amount')) {
                $details['min_order_amount'] = $request->input('min_order_amount');
            }

            if ($request->has('max_discount_amount')) {
                $details['max_discount_amount'] = $request->input('max_discount_amount');
            }

            $validated['details'] = $details; // Keep as array for the update (Laravel will cast to JSON)

            $coupon->update($validated);

            // Log coupon update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Coupon updated for marketing adjustments',
                'data_correction',
                ['code'] // Coupon code is business data
            );

            // Calculate changes in product associations
            $oldProducts = $oldDetails['products'] ?? [];
            $newProducts = $details['products'] ?? [];
            $productsAdded = array_diff($newProducts, $oldProducts);
            $productsRemoved = array_diff($oldProducts, $newProducts);

            $this->auditTrailService->logUpdated(
                $coupon,
                $oldData,
                "Updated coupon: {$coupon->code}",
                array_merge([
                    'old_discount' => $oldData['discount'],
                    'new_discount' => $coupon->discount,
                    'old_discount_type' => $oldData['discount_type'],
                    'new_discount_type' => $coupon->discount_type,
                    'old_type' => $oldData['type'],
                    'new_type' => $coupon->type,
                    'old_start_date' => $oldData['start_date'],
                    'new_start_date' => $coupon->start_date,
                    'old_end_date' => $oldData['end_date'],
                    'new_end_date' => $coupon->end_date,
                    'old_owner' => $oldData['owner'],
                    'new_owner' => $coupon->owner,
                    'old_product_count' => count($oldProducts),
                    'new_product_count' => count($newProducts),
                    'products_added' => $productsAdded,
                    'products_removed' => $productsRemoved,
                    'min_order_amount_changed' => ($oldDetails['min_order_amount'] ?? null) != ($details['min_order_amount'] ?? null),
                    'max_discount_amount_changed' => ($oldDetails['max_discount_amount'] ?? null) != ($details['max_discount_amount'] ?? null),
                ], $justificationData)
            );

            DB::commit();

            return redirect()->route('coupons.index')->with('success', 'Coupon updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'coupon_update_failed',
                'entity_type' => 'Coupon',
                'entity_id' => $coupon->id,
                'action_summary' => "Failed to update coupon: {$coupon->code}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $request->except(['_token', '_method']),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage())->withInput();
        }
    }

    public function destroy(Coupon $coupon)
    {
        DB::beginTransaction();

        try {
            // Get data before deletion for audit trail
            $couponData = $coupon->toArray();

            // Handle details properly
            $couponDetails = [];
            if (! empty($coupon->details)) {
                if (is_array($coupon->details)) {
                    $couponDetails = $coupon->details;
                } else {
                    $couponDetails = json_decode($coupon->details, true) ?? [];
                }
            }

            // Log coupon deletion with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Coupon removed due to expiration or policy change',
                'data_cleanup',
                ['code'] // Coupon code is business data
            );

            $this->auditTrailService->logDeleted(
                $coupon,
                "Deleted coupon: {$coupon->code}",
                array_merge([
                    'discount' => $coupon->discount,
                    'discount_type' => $coupon->discount_type,
                    'type' => $coupon->type,
                    'start_date' => $coupon->start_date,
                    'end_date' => $coupon->end_date,
                    'added_by' => $coupon->added_by,
                    'owner' => $coupon->owner,
                    'product_count' => count($couponDetails['products'] ?? []),
                    'min_order_amount' => $couponDetails['min_order_amount'] ?? null,
                    'max_discount_amount' => $couponDetails['max_discount_amount'] ?? null,
                ], $justificationData)
            );

            $coupon->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Coupon deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed deletion attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'coupon_deletion_failed',
                'entity_type' => 'Coupon',
                'entity_id' => $coupon->id,
                'action_summary' => "Failed to delete coupon: {$coupon->code}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                ],
            ]);

            return back()->with('error', 'Something went wrong: '.$e->getMessage());
        }
    }

    public function getProductsForMerchant($userId)
    {
        // Log product retrieval for merchant
        $this->auditTrailService->logViewOperation(
            'view_merchant_products',
            'Product',
            "Retrieved products for merchant ID: {$userId}",
            [
                'merchant_id' => $userId,
            ]
        );

        $products = Product::where('user_id', $userId)
            ->select('id', 'name')
            ->get();

        return response()->json(['products' => $products]);
    }
}
