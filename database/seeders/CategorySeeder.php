<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed the categories in English
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'order_level' => 1,
            'banner' => 'path/to/banner.jpg',
            'icon' => 'path/to/icon.jpg',
            'featured' => true,
            'meta_title' => 'Electronics Category',
            'meta_description' => 'Electronics category description',
        ]);

        $clothing = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
            'order_level' => 2,
            'banner' => 'path/to/banner.jpg',
            'icon' => 'path/to/icon.jpg',
            'featured' => false,
            'meta_title' => 'Clothing Category',
            'meta_description' => 'Clothing category description',
        ]);

        // Now create translations for the categories in Arabic
        $electronics->translations()->create([
            'locale' => 'ar',
            'name' => 'إلكترونيات',
            'meta_title' => 'فئة الإلكترونيات',
            'meta_description' => 'وصف فئة الإلكترونيات',
        ]);

        $clothing->translations()->create([
            'locale' => 'ar',
            'name' => 'ملابس',
            'meta_title' => 'فئة الملابس',
            'meta_description' => 'وصف فئة الملابس',
        ]);
    }
}
