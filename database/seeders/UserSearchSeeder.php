<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserSearch;
use App\Models\User;
use Faker\Factory as Faker;

class UserSearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        $userIds = User::pluck('id')->toArray();

        foreach (range(1, 15) as $index) {
            UserSearch::create([
                'user_id' => $faker->randomElement($userIds),
                'search_term' => $faker->word,
            ]);
        }
    }
}
