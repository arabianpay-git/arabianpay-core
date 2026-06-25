<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class ColorAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $colorAttribute = Attribute::whereEncrypted('name', 'Color')->first();

        if (! $colorAttribute) {
            $this->command->error('Color attribute not found.');

            return;
        }

        $colors = [
            ['value' => 'Red', 'ar' => 'أحمر', 'color_code' => '#FF0000'],
            ['value' => 'Blue', 'ar' => 'أزرق', 'color_code' => '#0000FF'],
            ['value' => 'Green', 'ar' => 'أخضر', 'color_code' => '#008000'],
            ['value' => 'Black', 'ar' => 'أسود', 'color_code' => '#000000'],
            ['value' => 'White', 'ar' => 'أبيض', 'color_code' => '#FFFFFF'],
            ['value' => 'Yellow', 'ar' => 'أصفر', 'color_code' => '#FFFF00'],
            ['value' => 'Orange', 'ar' => 'برتقالي', 'color_code' => '#FFA500'],
            ['value' => 'Purple', 'ar' => 'أرجواني', 'color_code' => '#800080'],
            ['value' => 'Pink', 'ar' => 'زهري', 'color_code' => '#FFC0CB'],
            ['value' => 'Brown', 'ar' => 'بني', 'color_code' => '#A52A2A'],
            ['value' => 'Gray', 'ar' => 'رمادي', 'color_code' => '#808080'],
            ['value' => 'Cyan', 'ar' => 'سماوي', 'color_code' => '#00FFFF'],
            ['value' => 'Magenta', 'ar' => 'ماجنتا', 'color_code' => '#FF00FF'],
            ['value' => 'Lime', 'ar' => 'ليموني', 'color_code' => '#00FF00'],
            ['value' => 'Indigo', 'ar' => 'نيلي', 'color_code' => '#4B0082'],
            ['value' => 'Violet', 'ar' => 'بنفسجي', 'color_code' => '#EE82EE'],
            ['value' => 'Turquoise', 'ar' => 'فيروزي', 'color_code' => '#40E0D0'],
            ['value' => 'Silver', 'ar' => 'فضي', 'color_code' => '#C0C0C0'],
            ['value' => 'Gold', 'ar' => 'ذهبي', 'color_code' => '#FFD700'],
            ['value' => 'Beige', 'ar' => 'بيج', 'color_code' => '#F5F5DC'],
            ['value' => 'Maroon', 'ar' => 'أحمر غامق', 'color_code' => '#800000'],
            ['value' => 'Olive', 'ar' => 'زيتوني', 'color_code' => '#808000'],
            ['value' => 'Navy', 'ar' => 'كحلي', 'color_code' => '#000080'],
            ['value' => 'Teal', 'ar' => 'أخضر أزرق', 'color_code' => '#008080'],
            ['value' => 'Coral', 'ar' => 'مرجاني', 'color_code' => '#FF7F50'],
            ['value' => 'Salmon', 'ar' => 'سلموني', 'color_code' => '#FA8072'],
            ['value' => 'Chocolate', 'ar' => 'شوكولاتة', 'color_code' => '#D2691E'],
            ['value' => 'Tan', 'ar' => 'أسمر فاتح', 'color_code' => '#D2B48C'],
            ['value' => 'Khaki', 'ar' => 'كاكي', 'color_code' => '#F0E68C'],
            ['value' => 'Plum', 'ar' => 'خوخي', 'color_code' => '#DDA0DD'],
            ['value' => 'Orchid', 'ar' => 'أوركيد', 'color_code' => '#DA70D6'],
            ['value' => 'Lavender', 'ar' => 'لافندر', 'color_code' => '#E6E6FA'],
            ['value' => 'Moccasin', 'ar' => 'موكاسين', 'color_code' => '#FFE4B5'],
            ['value' => 'Azure', 'ar' => 'أزرق سماوي', 'color_code' => '#F0FFFF'],
            ['value' => 'Peach', 'ar' => 'خوخي فاتح', 'color_code' => '#FFE5B4'],
            ['value' => 'Mint', 'ar' => 'نعناعي', 'color_code' => '#98FF98'],
            ['value' => 'Olive Drab', 'ar' => 'زيتي داكن', 'color_code' => '#6B8E23'],
            ['value' => 'Sea Green', 'ar' => 'أخضر بحري', 'color_code' => '#2E8B57'],
            ['value' => 'Forest Green', 'ar' => 'أخضر غامق', 'color_code' => '#228B22'],
            ['value' => 'Linen', 'ar' => 'كتان', 'color_code' => '#FAF0E6'],
            ['value' => 'Bisque', 'ar' => 'بيسكي', 'color_code' => '#FFE4C4'],
            ['value' => 'Slate Gray', 'ar' => 'رمادي صخري', 'color_code' => '#708090'],
            ['value' => 'Light Slate Gray', 'ar' => 'رمادي فاتح', 'color_code' => '#778899'],
            ['value' => 'Dark Slate Gray', 'ar' => 'رمادي غامق', 'color_code' => '#2F4F4F'],
            ['value' => 'Alice Blue', 'ar' => 'أزرق أليس', 'color_code' => '#F0F8FF'],
            ['value' => 'Antique White', 'ar' => 'أبيض عتيق', 'color_code' => '#FAEBD7'],
            ['value' => 'Aqua', 'ar' => 'أكوا', 'color_code' => '#00FFFF'],
            ['value' => 'Aquamarine', 'ar' => 'أزمارين', 'color_code' => '#7FFFD4'],
            ['value' => 'Azure Mist', 'ar' => 'ضباب أزرق', 'color_code' => '#F0FFFF'],
            ['value' => 'Baby Blue', 'ar' => 'أزرق فاتح', 'color_code' => '#89CFF0'],
            ['value' => 'Beaver', 'ar' => 'بيفر', 'color_code' => '#9F8170'],
            ['value' => 'Blanched Almond', 'ar' => 'لوزي', 'color_code' => '#FFEBCD'],
            ['value' => 'Blue Violet', 'ar' => 'أزرق بنفسجي', 'color_code' => '#8A2BE2'],
            ['value' => 'Brick Red', 'ar' => 'أحمر طوبي', 'color_code' => '#CB4154'],
            ['value' => 'Cadet Blue', 'ar' => 'أزرق عسكري', 'color_code' => '#5F9EA0'],
            ['value' => 'Chartreuse', 'ar' => 'أصفر مخضر', 'color_code' => '#7FFF00'],
            ['value' => 'Chocolate', 'ar' => 'شوكولاتة', 'color_code' => '#D2691E'],
            ['value' => 'Coffee', 'ar' => 'قهوة', 'color_code' => '#6F4E37'],
            ['value' => 'Cream', 'ar' => 'كريمي', 'color_code' => '#FFFDD0'],
            ['value' => 'Crimson', 'ar' => 'قرمزي', 'color_code' => '#DC143C'],
            ['value' => 'Dark Blue', 'ar' => 'أزرق داكن', 'color_code' => '#00008B'],
            ['value' => 'Dark Cyan', 'ar' => 'أخضر أزرق داكن', 'color_code' => '#008B8B'],
            ['value' => 'Dark Goldenrod', 'ar' => 'ذهبي داكن', 'color_code' => '#B8860B'],
            ['value' => 'Dark Gray', 'ar' => 'رمادي داكن', 'color_code' => '#A9A9A9'],
            ['value' => 'Dark Green', 'ar' => 'أخضر داكن', 'color_code' => '#006400'],
            ['value' => 'Dark Khaki', 'ar' => 'كاكي داكن', 'color_code' => '#BDB76B'],
            ['value' => 'Dark Magenta', 'ar' => 'ماجنتا داكن', 'color_code' => '#8B008B'],
            ['value' => 'Dark Olive Green', 'ar' => 'أخضر زيتي داكن', 'color_code' => '#556B2F'],
            ['value' => 'Dark Orange', 'ar' => 'برتقالي داكن', 'color_code' => '#FF8C00'],
            ['value' => 'Dark Orchid', 'ar' => 'أوركيد داكن', 'color_code' => '#9932CC'],
            ['value' => 'Dark Red', 'ar' => 'أحمر داكن', 'color_code' => '#8B0000'],
            ['value' => 'Dark Salmon', 'ar' => 'سلموني داكن', 'color_code' => '#E9967A'],
            ['value' => 'Dark Sea Green', 'ar' => 'أخضر بحري داكن', 'color_code' => '#8FBC8F'],
            ['value' => 'Dark Slate Blue', 'ar' => 'أزرق صخري داكن', 'color_code' => '#483D8B'],
            ['value' => 'Dark Slate Gray', 'ar' => 'رمادي صخري داكن', 'color_code' => '#2F4F4F'],
            ['value' => 'Dark Turquoise', 'ar' => 'فيروزي داكن', 'color_code' => '#00CED1'],
            ['value' => 'Dark Violet', 'ar' => 'بنفسجي داكن', 'color_code' => '#9400D3'],
            ['value' => 'Deep Pink', 'ar' => 'زهري عميق', 'color_code' => '#FF1493'],
            ['value' => 'Deep Sky Blue', 'ar' => 'أزرق سماوي عميق', 'color_code' => '#00BFFF'],
            ['value' => 'Dim Gray', 'ar' => 'رمادي باهت', 'color_code' => '#696969'],
            ['value' => 'Dodger Blue', 'ar' => 'أزرق دوجر', 'color_code' => '#1E90FF'],
            ['value' => 'Firebrick', 'ar' => 'أحمر طوبي غامق', 'color_code' => '#B22222'],
            ['value' => 'Floral White', 'ar' => 'أبيض زهري', 'color_code' => '#FFFAF0'],
            ['value' => 'Forest Green', 'ar' => 'أخضر غابات', 'color_code' => '#228B22'],
            ['value' => 'Fuchsia', 'ar' => 'فوشيا', 'color_code' => '#FF00FF'],
            ['value' => 'Gainsboro', 'ar' => 'جنس بورو', 'color_code' => '#DCDCDC'],
            ['value' => 'Ghost White', 'ar' => 'أبيض شبح', 'color_code' => '#F8F8FF'],
            ['value' => 'Honeydew', 'ar' => 'عسل', 'color_code' => '#F0FFF0'],
            ['value' => 'Hot Pink', 'ar' => 'زهري ساخن', 'color_code' => '#FF69B4'],
            ['value' => 'Indian Red', 'ar' => 'أحمر هندي', 'color_code' => '#CD5C5C'],
            ['value' => 'Ivory', 'ar' => 'عاجي', 'color_code' => '#FFFFF0'],
            ['value' => 'Khaki', 'ar' => 'كاكي', 'color_code' => '#F0E68C'],
            ['value' => 'Lavender', 'ar' => 'لافندر', 'color_code' => '#E6E6FA'],
            ['value' => 'Lavender Blush', 'ar' => 'لافندر وردي', 'color_code' => '#FFF0F5'],
            ['value' => 'Lawn Green', 'ar' => 'أخضر عشبي', 'color_code' => '#7CFC00'],
            ['value' => 'Lemon Chiffon', 'ar' => 'ليموني فاتح', 'color_code' => '#FFFACD'],
            ['value' => 'Light Blue', 'ar' => 'أزرق فاتح', 'color_code' => '#ADD8E6'],
            ['value' => 'Light Coral', 'ar' => 'مرجاني فاتح', 'color_code' => '#F08080'],
            ['value' => 'Light Cyan', 'ar' => 'سماوي فاتح', 'color_code' => '#E0FFFF'],
            ['value' => 'Light Goldenrod Yellow', 'ar' => 'أصفر ذهبي فاتح', 'color_code' => '#FAFAD2'],
            ['value' => 'Light Gray', 'ar' => 'رمادي فاتح', 'color_code' => '#D3D3D3'],
            ['value' => 'Light Green', 'ar' => 'أخضر فاتح', 'color_code' => '#90EE90'],
            ['value' => 'Light Pink', 'ar' => 'زهري فاتح', 'color_code' => '#FFB6C1'],
            ['value' => 'Light Salmon', 'ar' => 'سلموني فاتح', 'color_code' => '#FFA07A'],
            ['value' => 'Light Sea Green', 'ar' => 'أخضر بحري فاتح', 'color_code' => '#20B2AA'],
            ['value' => 'Light Sky Blue', 'ar' => 'أزرق سماوي فاتح', 'color_code' => '#87CEFA'],
            ['value' => 'Light Slate Gray', 'ar' => 'رمادي صخري فاتح', 'color_code' => '#778899'],
            ['value' => 'Light Steel Blue', 'ar' => 'أزرق صلب فاتح', 'color_code' => '#B0C4DE'],
            ['value' => 'Light Yellow', 'ar' => 'أصفر فاتح', 'color_code' => '#FFFFE0'],
        ];

        foreach ($colors as $color) {
            // Check if main color value exists
            $exists = AttributeValue::where('attribute_id', $colorAttribute->id)
                ->whereEncrypted('value', $color['value'])
                ->exists();

            if ($exists) {
                $this->command->info("Skipped duplicate color: {$color['value']}");

                continue;
            }

            $attributeValue = AttributeValue::create([
                'attribute_id' => $colorAttribute->id,
                'value' => $color['value'],
                'color_code' => $color['color_code'],
            ]);

            // Check translation in the translations table using whereEncrypted
            $translationExists = $attributeValue->translations()
                ->where('locale', 'ar')
                ->whereEncrypted('value', $color['ar'])
                ->exists();

            if (! $translationExists) {
                $attributeValue->translations()->create([
                    'locale' => 'ar',
                    'value' => $color['ar'],
                ]);
            } else {
                $this->command->info("Skipped duplicate Arabic translation for: {$color['value']}");
            }
        }

        $this->command->info('Color attribute values seeded successfully!');
    }
}
