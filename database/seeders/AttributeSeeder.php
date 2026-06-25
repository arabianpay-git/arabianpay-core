<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run()
    {
        // Creating attributes with translations
        $attributes = [
            [
                'name' => 'Size',
                'ar' => 'الحجم',
            ],
            [
                'name' => 'Color',
                'ar' => 'اللون',
            ],
            [
                'name' => 'Material',
                'ar' => 'المادة',
            ],
        ];

        foreach ($attributes as $data) {
            // Create the attribute in the default language (English)
            $attribute = Attribute::create([
                'name' => $data['name'],
            ]);

            // Create translation for the attribute in Arabic
            $attribute->translations()->create([
                'locale' => 'ar',
                'name' => $data['ar'],
            ]);
        }
    }
}
