<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaticsController extends Controller
{
    public function products(Request $request)
    {
        $products = Product::with(['user:id,business_name'])
            ->select(['id', 'name', 'thumbnail', 'unit_price', 'user_id', 'views', 'rating', 'number_of_sales', 'approved', 'created_at'])
            ->latest()
            ->paginate(10);

        return view('admin.statics.products', compact('products'));
    }

    public function brands(Request $request)
    {
        $brands = Brand::select('brands.id', 'brands.name', 'brands.created_at')
            ->leftJoinSub(
                DB::table('products')
                    ->select('brand_id', DB::raw('SUM(number_of_sales) as total_sales'))
                    ->groupBy('brand_id'),
                'sales',
                'brands.id',
                '=',
                'sales.brand_id'
            )
            ->orderByDesc('total_sales')
            ->paginate(10);

        return view('admin.statics.brands', compact('brands'));
    }

    public function categories(Request $request)
    {
        $categories = Category::select('categories.id', 'categories.name', 'categories.created_at')
            ->leftJoinSub(
                DB::table('products')
                    ->select('category_id', DB::raw('SUM(number_of_sales) as total_sales'))
                    ->groupBy('category_id'),
                'sales',
                'categories.id',
                '=',
                'sales.category_id'
            )
            ->orderByDesc('total_sales')
            ->paginate(10);

        return view('admin.statics.categories', compact('categories'));
    }

    public function reviews()
    {
        // TODO: implement reviews statistics view [CORE-P0-04]
    }
}
