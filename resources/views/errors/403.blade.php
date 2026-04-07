@extends('layouts.auth')

@section('content')
    <div class="w-full h-screen flex items-center justify-center bg-white dark:bg-gray-900">
        <div class="text-center">
            <div class="mb-9">
                <img alt="image" class="dark:hidden mx-auto max-h-[150px]"
                    src="{{ asset('assets/media/illustrations/12.svg') }}">
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white text-center mb-3">
                {{ translate('access_denied_title') }}
            </h3>
            <div class="text-sm text-center text-gray-700 dark:text-gray-300 mb-7">
                {{ translate('access_denied_body') }}
            </div>
            <a class="btn btn-primary inline-flex justify-center" href="{{ url('/') }}">
                {{ translate('Go to Home') }}
            </a>
        </div>
    </div>
@endsection
