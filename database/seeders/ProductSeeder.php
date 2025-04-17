<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::create([
            'name' => 'Sample Product',
            'slug' => 'sample-product',
            'added_by' => 'admin',
            'user_id' => 1,
            'category_id' => 1,
            'brand_id' => 1,
            'thumbnail' => 'path/to/thumbnail.jpg',
            'photos' => json_encode(['path/to/photo1.jpg', 'path/to/photo2.jpg']),
            'tags' => json_encode(['tag1', 'tag2']),
            'short_description' => 'Short description of the sample product.',
            'description' => 'Detailed description of the sample product.',
            'unit_price' => 100,
            'purchase_price' => 80,
            'discount' => 10.00,
            'discount_type' => 'percent',
            'discount_start_date' => now(),
            'discount_end_date' => now()->addDays(10),
            'published' => 'published',
            'approved' => 'approved',
            'reson_reject' => null,
            'featured' => true,
            'stock_visibility_state' => 'quantity',
            'current_stock' => 50,
            'unit' => 'Piece',
            'weight' => 0.5,
            'min_qty' => 1,
            'low_stock_quantity' => 5,
            'tax' => 5.00,
            'tax_type' => 'percent',
            'shipping_type' => 'flat_rate',
            'shipping_cost' => 20.00,
            'is_quantity_multiplied' => false,
            'est_shipping_days' => '3-5 days',
            'number_ofsales' => 100,
            'meta_title' => 'Sample Product Meta Title',
            'meta_description' => 'Sample product meta description.',
            'meta_img' => 'path/to/meta-image.jpg',
            'refundable' => true,
            'rating' => 4.5,
            'views' => 150,
        ]);

        // Attach attribute values with correct pivot structure
        $sizeValues = AttributeValue::where('attribute_id', Attribute::where('name', 'Size')->first()->id)->get();
        $colorValues = AttributeValue::where('attribute_id', Attribute::where('name', 'Color')->first()->id)->get();

        foreach ($sizeValues as $value) {
            $product->attributes()->attach($value->attribute->id, [
                'attribute_value_id' => $value->id,
            ]);
        }

        foreach ($colorValues as $value) {
            $product->attributes()->attach($value->attribute->id, [
                'attribute_value_id' => $value->id,
            ]);
        }

        // Add Arabic translation
        $product->translations()->create([
            'locale' => 'ar',
            'name' => 'منتج عينة',
            'unit' => 'قطعة',
            'reson_reject' => 'سبب الرفض',
            'meta_title' => 'عنوان ميتا للمنتج العينة',
            'meta_description' => 'وصف ميتا للمنتج العينة.',
            'short_description' => 'وصف قصير للمنتج العينة.',
            'description' => 'وصف مفصل للمنتج العينة.',
            'tags' => json_encode(['علامة1', 'علامة2']),
        ]);
    }
}
