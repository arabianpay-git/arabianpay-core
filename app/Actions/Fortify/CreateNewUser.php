<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'business_name' => ['required', 'string', 'max:255', 'unique:users'],
            'iqama' => ['nullable', 'string', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', 'max:20'],
            'country_id' => ['required', 'exists:countries,id'],
            'state_id' => ['required', 'exists:states,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'email' => $input['email'],
                'business_name' => $input['business_name'],
                'iqama' => $input['iqama'] ?? null,
                'phone_number' => $input['phone_number'],
                'user_type' => 'merchant', // default
                'status' => 'hold', // default
                'country_id' => $input['country_id'],
                'state_id' => $input['state_id'],
                'city_id' => $input['city_id'],
                'password' => Hash::make($input['password']),
            ]);
        });
    }

    /**
     * Create a personal team for the user.
     */
    // protected function createTeam(User $user): void
    // {
    //     $user->ownedTeams()->save(Team::forceCreate([
    //         'user_id' => $user->id,
    //         'name' => explode(' ', $user->name, 2)[0] . "'s Team",
    //         'personal_team' => true,
    //     ]));
    // }
}
