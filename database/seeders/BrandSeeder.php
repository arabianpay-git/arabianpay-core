<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed the brands in English
        $nike = Brand::create([
            'name' => 'Nike',
            'slug' => 'nike',
            'logo' => 'path/to/nike_logo.jpg',
            'order_level' => 1,
            'featured' => true,
            'meta_title' => 'Nike Brand',
            'meta_description' => 'Nike brand description',
        ]);

        $adidas = Brand::create([
            'name' => 'Adidas',
            'slug' => 'adidas',
            'logo' => 'path/to/adidas_logo.jpg',
            'order_level' => 2,
            'featured' => false,
            'meta_title' => 'Adidas Brand',
            'meta_description' => 'Adidas brand description',
        ]);

        // Now create translations for the brands in Arabic
        $nike->translations()->create([
            'locale' => 'ar',
            'name' => 'نايك',
            'meta_title' => 'علامة نايك التجارية',
            'meta_description' => 'وصف علامة نايك التجارية',
        ]);

        $adidas->translations()->create([
            'locale' => 'ar',
            'name' => 'أديداس',
            'meta_title' => 'علامة أديداس التجارية',
            'meta_description' => 'وصف علامة أديداس التجارية',
        ]);
    }
}
