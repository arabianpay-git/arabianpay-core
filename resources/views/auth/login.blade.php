@extends('layouts.auth') 

@section('content')

@php 
    $ip = request()->ip(); 
    $attempt = App\Models\LoginAttempt::where('ip_address', $ip)->first(); 
    $isLocked = false; $lockedMessage = null; 
    
    if ($attempt) { 
        $now = Carbon\Carbon::now(); 
        $lockedUntil = $attempt->locked_until ? Carbon\Carbon::parse($attempt->locked_until) : null; 
        if ($attempt->attempts === 10) { 
            $isLocked = true; 
            $lockedMessage = '🚫 Access to this service is currently restricted due to repeated failed attempts.'; 
        } 
            elseif ($lockedUntil && $now->lt($lockedUntil)) { 
                $remaining = $now->diffInSeconds($lockedUntil); 
                $minutes = floor($remaining / 60); $seconds = $remaining % 60; $isLocked = true; 
                $lockedMessage = "⏳ Too many attempts. Please wait {$minutes} minute(s) and {$seconds} second(s) before trying again."; 
            } 
        } 
@endphp 

@if ($isLocked)
   
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">

            <div class="text-center mb-2.5">
                <h3 class="text-lg font-medium p-5 text-gray-900 leading-none mb-2.5">
                    {{ $lockedMessage }}
                </h3>
            </div>
        </div>
    </div>
@else
    <x-slot name="logo">
        <x-authentication-card-logo />
    </x-slot>

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">
            <form action="{{ route('login') }}" class="card-body flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
                @csrf

                <div class="text-center mb-2.5">
                    <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                        Welcome to {{ env('APP_NAME') }}
                    </h3>
                </div>

                {{-- <div class="text-center mb-2.5">
                    <h3 class="text-lg font-medium text-gray-900 leading-none mb-2.5">
                        Sign in
                    </h3>
                    <div class="flex items-center justify-center font-medium">
                        <span class="text-2sm text-gray-700 me-1.5">
                            Need an account?
                        </span>
                        <a class="text-2sm link" href="html/demo1/authentication/classic/sign-up.html">
                            Sign up
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="border-t border-gray-200 w-full"> </span>
                    <span class="text-2xs text-gray-500 font-medium uppercase">
                        Or
                    </span>
                    <span class="border-t border-gray-200 w-full"> </span>
                </div> --}}
                

                <x-validation-errors class="mb-4" />

                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Email
                    </label>
                    <input class="input" name="email" placeholder="email@email.com" type="email" value="{{ old('email') }}" required />
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="form-label font-normal text-gray-900">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                        <a class="text-2sm link shrink-0" href="{{ route('password.request') }}">
                            Forgot Password?
                        </a>
                        @endif
                    </div>
                    <div class="input" data-toggle-password="true">
                        <input name="password" placeholder="Enter Password" type="password" required />
                        <button class="btn btn-icon" data-toggle-password-trigger="true" type="button">
                            <i class="ki-filled ki-eye text-gray-500 toggle-password-active:hidden"> </i>
                            <i class="ki-filled ki-eye-slash text-gray-500 hidden toggle-password-active:block"> </i>
                        </button>
                    </div>
                </div>

                <label class="checkbox-group">
                    <input class="checkbox checkbox-sm" id="remember_me" name="remember" type="checkbox" value="1" />
                    <span class="checkbox-label">
                        Remember me
                    </span>
                </label>
                <button class="btn btn-primary flex justify-center grow">
                    Sign In
                </button>
            </form>
        </div>
    </div>
@endif
@endsection
