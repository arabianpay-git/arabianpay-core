<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->paginate(15);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
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
            return redirect()->back()->withErrors($validator)->withInput();
        }

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

        Coupon::create($validated);

        return redirect()->route('coupons.index')->with('success', 'Coupon created successfully!');
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);

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
            'code' => 'required|regex:/^[a-zA-Z\s]*$/|unique:coupons,code,' . $coupon->id,
            'discount' => 'required|numeric',
            'discount_type' => 'required|in:percent,amount',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'products' => 'required_if:type,product_base|array|min:1',
            'min_order_amount' => 'nullable|numeric',
            'max_discount_amount' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
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

        $validated['details'] = $details;

        $coupon->update($validated);

        return redirect()->route('coupons.index')->with('success', 'Coupon updated successfully!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->back()->with('success', 'Coupon deleted successfully!');
    }
}
