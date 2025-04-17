<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run()
    {
        $states = [
            [
                'en' => 'Riyadh',
                'ar' => 'الرياض',
            ],
            [
                'en' => 'Makkah',
                'ar' => 'مكة المكرمة',
            ],
            [
                'en' => 'Medina',
                'ar' => 'المدينة المنورة',
            ],
            [
                'en' => 'Eastern Province',
                'ar' => 'المنطقة الشرقية',
            ],
            [
                'en' => 'Asir',
                'ar' => 'عسير',
            ],
            [
                'en' => 'Tabuk',
                'ar' => 'تبوك',
            ],
        ];

        foreach ($states as $key => $state) {
            $s = State::create([
                'id' => $key + 1,
                'country_id' => 1,
                'name' => $state['en'],
            ]);

            $s->translations()->create([
                'locale' => 'ar',
                'name' => $state['ar'],
            ]);
        }
    }
}
