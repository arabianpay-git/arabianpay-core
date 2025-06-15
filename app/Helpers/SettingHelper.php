<?php

use App\Models\CustomerCreditLimit;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

// Get Settings
if (!function_exists('get_setting')) {
    function get_setting($label, $default = null)
    {
        $setting = Setting::where('label', $label)->first();
        return $setting ? $setting->value : $default;
    }
}

// Get Tax setting
if (!function_exists('get_tax')) {
    function get_tax($default = 0)
    {
        return get_setting('tax', $default);
    }
}

// Get Commission Tax setting
if (!function_exists('get_commission_tax')) {
    function get_commission_tax($default = 0)
    {
        return get_setting('commission_tax', $default);
    }
}

if (!function_exists('get_credit_limit')) {
    function get_credit_limit($user_id, $default = 0.00)
    {
        $creditLimit = CustomerCreditLimit::where('user_id', $user_id)->latest()->first();
        return $creditLimit ? $creditLimit->limit_arabianpay_after : $default;
    }
}

if (!function_exists('get_seller_commission')) {
    function get_seller_commission($user_id, $default = 0.00)
    {
        $creditLimit = CustomerCreditLimit::where('user_id', $user_id)->latest()->first();
        return $creditLimit ? $creditLimit->comission : $default;
    }
}

if (!function_exists('get_system_commission')) {
    function get_system_commission($default = 0)
    {
        return get_setting('system_commission', $default);
    }
}


if (!function_exists('calculate_order_tax')) {
    function calculate_order_tax($order)
    {
        $totalTax = 0;
        $items = json_decode($order->product_details, true);

        foreach ($items as $item) {
            $product = App\Models\Product::find($item['product_id']);

            if (!$product || !isset($product->tax_type)) {
                continue;
            }

            // Get tax value, use setting fallback if null
            $productTax = $product->tax ?? get_setting('tax', 0);

            // Calculate item total price (sum attribute prices × quantity)
            $attributePrice = collect($item['attributes'])->sum('price');
            $totalPrice = $attributePrice * $item['quantity'];

            if ($product->tax_type === 'percent') {
                $tax = ($productTax / 100) * $totalPrice;
            } else {
                $tax = $productTax * $item['quantity'];
            }

            $totalTax += $tax;
        }

        return $totalTax;
    }
}

if (! function_exists('map_product_details')) {
    function map_product_details(string $jsonDetails): Collection
    {
        $items = json_decode($jsonDetails, true) ?: [];

        return collect($items)->map(function (array $item) {
            // find product
            $product = Product::find($item['product_id']);

            if ($product) {
                // price: first attribute price or fallback to unit_price
                $price = collect($item['attributes'] ?? [])
                    ->pluck('price')
                    ->first()
                    ?: $product->unit_price
                    ?: 0;

                $quantity = $item['quantity'] ?? 0;

                return (object)[
                    'product'    => $product,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'attributes' => $item['attributes'] ?? [],
                    'total'      => $price * $quantity,
                ];
            }
        });
    }

    if (!function_exists('currentUser')) {
        function currentUser()
        {
            return Auth::user();
        }
    }


    if (!function_exists('getEmployees')) {
        function getEmployees($department = null)
        {
            $user = Auth::user();

            $query = User::where('user_type', 'employee');

            if ($user) {
                // Exclude current logged-in user
                $query->where('id', '!=', $user->id);

                if ($user->user_type !== 'admin') {
                    if ($user->is_manager) {
                        // Manager: see all employees in their department
                        $query->where('department', $user->department);
                    } else {
                        // Not manager: see only managers in their department
                        $query->where('is_manager', true)
                            ->where('department', $user->department);
                    }
                } elseif ($department !== null) {
                    // Admin with department filter
                    $query->where('department', $department);
                }
            }

            return $query->orderBy('first_name')->get();
        }
    }
}


use Illuminate\Support\Facades\Storage;

if (!function_exists('getImageUrl')) {
    function getImageUrl($path)
    {
        // Local path check
        if (Storage::disk('public')->exists($path)) {
            return asset($path);
        }

        // Check if exists on partners subdomain
        $partnersUrl = 'https://partners.araboanpay.com/' . $path;
        if (urlExists($partnersUrl)) {
            return $partnersUrl;
        }

        // Fallback: core domain
        $coreUrl = 'https://core.araboanpay.com/' . $path;
        if (urlExists($coreUrl)) {
            return $coreUrl;
        }

        // Optional fallback image
        return asset('images/no-image.png');
    }

    function urlExists($url)
    {
        try {
            $headers = get_headers($url);
            return strpos($headers[0], '200') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
