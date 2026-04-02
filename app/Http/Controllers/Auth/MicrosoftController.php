<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsToTwoFactorChallenge;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Log;

class MicrosoftController extends Controller
{
    use RedirectsToTwoFactorChallenge;

    // الخطوة 1: التوجيه إلى صفحة مايكروسوفت
    public function redirect()
    {
        // try{
        return Socialite::driver('microsoft')->redirect();
        // }catch(Exception $e){
        //     return json_encode('somthing wrong');
        // }

    }

    // الخطوة 2: استلام رد مايكروسوفت
    public function callback()
    {
        try {
            Log::info('Microsoft callback query', request()->query());

            $microsoftUser = Socialite::driver('microsoft')->user();
            $email = $microsoftUser->user['mail'] ?? $microsoftUser->user['userPrincipalName'] ?? null;

            // dd($email);

            // Check if user exist
            $user = User::whereEncrypted('email', $email)->first();
            // dd($user);
            if (! $user) {
                return redirect('/login')->with('error', 'User not exist');
            }

            if ($this->requiresTwoFactorChallenge($user)) {
                return $this->redirectToTwoFactorChallenge(request(), $user);
            }

            Auth::login($user);

            return redirect(route('dashboard')); // after login page
        } catch (Exception $e) {
            Log::info('Error with Microsoft '.$e->getMessage());

            return redirect('/login')->with('error', 'Login failed: '.$e->getMessage());
        }
    }
}
