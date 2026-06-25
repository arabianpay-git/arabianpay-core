<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\Http\Livewire\UpdatePasswordForm;
use Livewire\Livewire;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The password validation rule includes `uncompromised()`, which calls
        // Have I Been Pwned. Stub it so tests do not depend on the internet.
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response(''),
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->call('updatePassword');

        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'WrongPassword1!',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_passwords_must_match(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdatePasswordForm::class)
            ->set('state', [
                'current_password' => 'password',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'WrongPassword1!',
            ])
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
