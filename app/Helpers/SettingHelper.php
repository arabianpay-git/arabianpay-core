<?php

use App\Models\Setting;

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

// Get System Commission setting
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
