<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    public function index()
    {
        return view('google-reviews');
    }

    public function getReviews(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
        ]);

        $apiKey = 'AIzaSyC0Oe6-EvwCkjpbSXt-CyDNi8QS3yPrrC0';

        // Step 1: Get Place ID
        $searchResponse = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
            'input' => $request->business_name,
            'inputtype' => 'textquery',
            'fields' => 'place_id',
            'key' => $apiKey,
        ]);

        $placeId = $searchResponse['candidates'][0]['place_id'] ?? null;

        if (!$placeId) {
            return back()->with('error', 'Business not found.');
        }

        // Step 2: Get Reviews
        $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'fields' => 'name,reviews,rating,user_ratings_total',
            'key' => $apiKey,
        ]);

        return view('google-reviews', [
            'reviews' => $detailsResponse['result']['reviews'] ?? [],
            'business' => $detailsResponse['result']['name'] ?? $request->business_name,
            'overallRating' => $detailsResponse['result']['rating'] ?? null,
            'totalReviews' => $detailsResponse['result']['user_ratings_total'] ?? 0,
        ]);
    }
}
