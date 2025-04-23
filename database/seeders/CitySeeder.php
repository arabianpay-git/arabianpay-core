<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run()
    {
        $cities = [
            ['en' => 'Riyadh',  'ar' => 'الرياض',     'state_id' => 1],
            ['en' => 'Jeddah',  'ar' => 'جدة',         'state_id' => 2],
            ['en' => 'Medina',  'ar' => 'المدينة',     'state_id' => 3],
            ['en' => 'Dammam',  'ar' => 'الدمام',      'state_id' => 4],
            ['en' => 'Abha',    'ar' => 'أبها',         'state_id' => 5],
            ['en' => 'Tabuk',   'ar' => 'تبوك',         'state_id' => 6],
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
