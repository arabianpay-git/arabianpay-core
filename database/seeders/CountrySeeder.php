<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => 'SA',
                'en' => 'Saudi Arabia',
                'ar' => 'المملكة العربية السعودية',
            ],
            [
                'code' => 'AE',
                'en' => 'United Arab Emirates',
                'ar' => 'الإمارات العربية المتحدة',
            ],
        ];

        foreach ($countries as $data) {
            $country = Country::create([
                'name' => $data['en'],
                'code' => $data['code'],
            ]);

            $country->translations()->create([
                'locale' => 'ar',
                'name' => $data['ar'],
            ]);
        }
    }
}
