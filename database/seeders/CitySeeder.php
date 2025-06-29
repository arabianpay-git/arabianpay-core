<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run()
    {
        $cities = [
            // Riyadh Region (state_id = 1)
            ['en' => 'Riyadh', 'ar' => 'الرياض', 'state_id' => 1],
            ['en' => 'Al Kharj', 'ar' => 'الخرج', 'state_id' => 1],
            ['en' => 'Al Majmaah', 'ar' => 'المجمعة', 'state_id' => 1],

            // Makkah Region (state_id = 2)
            ['en' => 'Jeddah', 'ar' => 'جدة', 'state_id' => 2],
            ['en' => 'Makkah', 'ar' => 'مكة', 'state_id' => 2],
            ['en' => 'Taif', 'ar' => 'الطائف', 'state_id' => 2],

            // Medina Region (state_id = 3)
            ['en' => 'Medina', 'ar' => 'المدينة المنورة', 'state_id' => 3],
            ['en' => 'Yanbu', 'ar' => 'ينبع', 'state_id' => 3],

            // Eastern Province (state_id = 4)
            ['en' => 'Dammam', 'ar' => 'الدمام', 'state_id' => 4],
            ['en' => 'Al Khobar', 'ar' => 'الخبر', 'state_id' => 4],
            ['en' => 'Dhahran', 'ar' => 'الظهران', 'state_id' => 4],

            // Qassim Region (state_id = 5)
            ['en' => 'Buraidah', 'ar' => 'بريدة', 'state_id' => 5],
            ['en' => 'Unaizah', 'ar' => 'عنيزة', 'state_id' => 5],

            // Asir Region (state_id = 6)
            ['en' => 'Abha', 'ar' => 'أبها', 'state_id' => 6],
            ['en' => 'Khamis Mushait', 'ar' => 'خميس مشيط', 'state_id' => 6],

            // Tabuk Region (state_id = 7)
            ['en' => 'Tabuk', 'ar' => 'تبوك', 'state_id' => 7],

            // Hail Region (state_id = 8)
            ['en' => 'Hail', 'ar' => 'حائل', 'state_id' => 8],

            // Northern Borders (state_id = 9)
            ['en' => 'Arar', 'ar' => 'عرعر', 'state_id' => 9],

            // Jazan Region (state_id = 10)
            ['en' => 'Jazan', 'ar' => 'جازان', 'state_id' => 10],
            ['en' => 'Sabya', 'ar' => 'صبيا', 'state_id' => 10],

            // Najran Region (state_id = 11)
            ['en' => 'Najran', 'ar' => 'نجران', 'state_id' => 11],

            // Al Bahah Region (state_id = 12)
            ['en' => 'Al Bahah', 'ar' => 'الباحة', 'state_id' => 12],

            // Al Jawf Region (state_id = 13)
            ['en' => 'Sakakah', 'ar' => 'سكاكا', 'state_id' => 13],
        ];

        foreach ($cities as $key => $city) {
            $c = City::create([
                'id' => $key + 1,
                'name' => $city['en'],
                'state_id' => $city['state_id'],
            ]);

            $c->translations()->create([
                'locale' => 'ar',
                'name' => $city['ar'],
            ]);
        }
    }
}
