@extends('layouts.auth')

@section('content')
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">
            <form action="{{ route('dev.login.store') }}" class="card-body flex flex-col gap-5 p-10" method="POST">
                @csrf

                <div class="text-center mb-8">
                    <a href="{{ route('dashboard') }}" class="inline-flex justify-center">
                        <img src="{{ asset('assets/media/images/logo.png') }}" alt="arabianpay-logo"
                            class="h-20 object-contain" />
                    </a>

                    <h3
                        class="mt-6 text-2xl font-semibold text-gray-900 flex items-center justify-center gap-2 select-none">
                        🚧 Dev Login 🚧
                    </h3>
                    <p class="text-sm text-gray-600 mt-2">
                        For Local Development Only
                    </p>
                </div>

                {{-- Warning Banner --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-yellow-800">Development Only</h4>
                            <p class="text-xs text-yellow-700 mt-1">
                                This page is only registered when <code class="text-yellow-800">APP_ENV=local</code>.
                                Uses the same password check as normal login; two-factor is not enforced here.
                            </p>
                        </div>
                    </div>
                </div>

                <x-validation-errors class="mb-4" />

                {{-- Email Input --}}
                <div class="flex flex-col gap-1">
                    <label class="form-label text-gray-900" for="email">
                        Email Address
                    </label>
                    <input id="email" name="email" type="email" placeholder="Enter your email"
                        class="input" value="{{ old('email') }}" required autofocus autocomplete="username" />
                </div>

                {{-- Password --}}
                <div class="flex flex-col gap-1">
                    <label class="form-label text-gray-900" for="password">
                        Password
                    </label>
                    <input id="password" name="password" type="password" placeholder="Enter your password"
                        class="input" required autocomplete="current-password" />
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-primary w-full">
                    Sign in (dev)
                </button>

                {{-- Back to Normal Login --}}
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        ← Back to normal login
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
