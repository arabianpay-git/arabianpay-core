@extends('layouts.base')

@section('content')
    <main id="content" role="content"
        style="display: flex; align-items: center; justify-content: center; padding: 40px 20px;">
        <div class="container-fixed" style="text-align: center; display: contents;">
            <div
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; padding: 40px 30px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 480px;">

                <div class="text-center">
                    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">
                        {{ translate('Welcome Back!') }}
                    </h1>
                    <p style="font-size: 1.125rem; color: #4b5563; line-height: 1.6;">
                        {{ translate('Glad to see you again! Here is your employee dashboard.') }}
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
