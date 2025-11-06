<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Seed attribute values for each attribute
        $sizeAttribute = Attribute::where('name', 'Size')->first();
        $colorAttribute = Attribute::where('name', 'Color')->first();
        $materialAttribute = Attribute::where('name', 'Material')->first();

        // Size values
        $sizeValues = [
            ['value' => 'Small', 'ar' => 'صغير'],
            ['value' => 'Medium', 'ar' => 'متوسط'],
            ['value' => 'Large', 'ar' => 'كبير'],
            ['value' => 'X-Large', 'ar' => 'X-كبير'],
        ];
        foreach ($sizeValues as $value) {
            $attributeValue = AttributeValue::create([
                'attribute_id' => $sizeAttribute->id,
                'value' => $value['value'],
            ]);

            // Create translation for the value in Arabic
            $attributeValue->translations()->create([
                'locale' => 'ar',
                'value' => $value['ar'],
            ]);
        }

        // Color values
        $colorValues = [
            ['value' => 'Red', 'ar' => 'أحمر'],
            ['value' => 'Blue', 'ar' => 'أزرق'],
            ['value' => 'Green', 'ar' => 'أخضر'],
            ['value' => 'Black', 'ar' => 'أسود'],
            ['value' => 'White', 'ar' => 'أبيض'],
        ];
        foreach ($colorValues as $value) {
            $attributeValue = AttributeValue::create([
                'attribute_id' => $colorAttribute->id,
                'value' => $value['value'],
            ]);

            // Create translation for the value in Arabic
            $attributeValue->translations()->create([
                'locale' => 'ar',
                'value' => $value['ar'],
            ]);
        }

        // Material values
        $materialValues = [
            ['value' => 'Cotton', 'ar' => 'قطن'],
            ['value' => 'Leather', 'ar' => 'جلد'],
            ['value' => 'Polyester', 'ar' => 'بوليستر'],
            ['value' => 'Wool', 'ar' => 'صوف'],
        ];
        foreach ($materialValues as $value) {
            $attributeValue = AttributeValue::create([
                'attribute_id' => $materialAttribute->id,
                'value' => $value['value'],
            ]);

            // Create translation for the value in Arabic
            $attributeValue->translations()->create([
                'locale' => 'ar',
                'value' => $value['ar'],
            ]);
        }
    }
}
