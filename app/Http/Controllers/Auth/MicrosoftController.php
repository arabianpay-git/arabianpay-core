<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Log;

class MicrosoftController extends Controller
{
    // الخطوة 1: التوجيه إلى صفحة مايكروسوفت
    public function redirect()
    {
        //try{
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

            //dd($email);

            // Check if user exist
            $user = User::whereEncrypted('email', $email)->first();
            //dd($user);
            if (!$user) {
                return redirect('/login')->with('error', 'User not exist');
            }

            Auth::login($user);
            return redirect(route('dashboard')); // after login page
        } catch (Exception $e) {
            Log::info("Error with Microsoft ". $e->getMessage());
            return redirect('/login')->with('error', 'Login failed: ' . $e->getMessage());
        }
    }
}
