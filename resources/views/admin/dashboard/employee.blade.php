@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content"
        style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="container-fixed" style="text-align: center;">
            <div
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; padding: 20px;">
                <div class="text-center">
                    <h1 style="font-size: 2rem; font-weight: bold; margin-bottom: 1rem; color: #1f2937;">
                        {{ translate('Welcome Back!') }}
                    </h1>
                    <p style="font-size: 1.125rem; color: #4b5563;">
                        {{ translate('You are logged in as an employee. This is your home page.') }}
                    </p>
                </div>

                <div style="margin-top: 2rem;">
                    <img src="{{ asset('assets/media/illustrations/1.svg') }}" alt="{{ translate('Welcome') }}"
                        style="display: block; margin: 0 auto; height: 240px; width: 240px;">
                </div>
            </div>
        </div>
    </main>
@endsection
