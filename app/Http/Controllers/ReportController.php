<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserSearch;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function productStock()
    {
        $products = Product::select('id', 'name', 'thumbnail', 'brand_id', 'unit_price', 'current_stock', 'sku', 'published')
            ->with('brand:id,name')
            ->latest()
            ->paginate(10);

        return view('admin.reports.stock', compact('products'));
    }

    public function productWishlist()
    {
        $products = Product::select('id', 'name', 'thumbnail', 'brand_id')
            ->with('brand:id,name')
            ->withCount('wishlists')
            ->latest()
            ->paginate(10);

        return view('admin.reports.wishlist', compact('products'));
    }

    public function userSearch()
    {
        $userSearches = UserSearch::with(['user:id,first_name,last_name,business_name'])
            ->latest()
            ->paginate(10);

        return view('admin.reports.searches', compact('userSearches'));
    }
}
