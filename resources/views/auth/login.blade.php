@extends('layouts.auth')

@section('content')
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">
            <form action="{{ route('login') }}" class="card-body flex flex-col gap-5 p-10" id="sign_in_form" method="POST">
                @csrf

                <div class="text-center mb-8">
                    <a href="{{ route('dashboard') }}" class="inline-flex justify-center">
                        <img src="{{ asset('assets/media/images/logo.png') }}" alt="arabianpay-logo"
                            class="h-20 object-contain" />
                    </a>

                    <h3
                        class="mt-6 text-2xl font-semibold text-gray-900 flex items-center justify-center gap-2 select-none">
                        {{ translate('Welcome Back') }} <span class="text-3xl leading-none">👋</span>
                    </h3>
                </div>

                <x-validation-errors class="mb-4" />

                <div class="flex flex-col gap-1">
                    <label class="form-label font-normal text-gray-900">
                        Email
                    </label>
                    <input class="input" name="email" placeholder="email@email.com" type="email"
                        value="{{ old('email') }}" required />
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="form-label font-normal text-gray-900">
                            Password
                        </label>
                        {{-- @if (Route::has('password.request'))
                                <a class="text-2sm link shrink-0" href="{{ route('password.request') }}">
                                    Forgot Password?
                                </a>
                            @endif --}}
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
@endsection
