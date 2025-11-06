@extends('layouts.auth')

@php
    // Get status code and default messages
    $code = isset($code) ? $code : $exception->getStatusCode() ?? 500;

    $messages = [
        403 => __('Forbidden'),
        404 => __('Page Not Found'),
        405 => __('Method Not Allowed'),
        408 => __('Request Timeout'),
        419 => __('Page Expired'),
        429 => __('Too Many Requests'),
        500 => __('Internal Server Error'),
        503 => __('Service Unavailable'),
        504 => __('Gateway Timeout'),
        505 => __('HTTP Version Not Supported'),
    ];

    $descriptions = [
        403 => __('You do not have permission to access this page.'),
        404 => __(
            'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.',
        ),
        405 => __('The method is not allowed for the requested URL.'),
        408 => __('The server timed out waiting for the request.'),
        419 => __('The page has expired due to inactivity.'),
        429 => __('Too many requests have been made in a short period. Please try again later.'),
        500 => __(
            'The server encountered an internal error or misconfiguration and was unable to complete your request.',
        ),
        503 => __('The server is currently unable to handle the request due to maintenance or overload.'),
        504 => __('The server was acting as a gateway or proxy and did not receive a timely response.'),
        505 => __('The server does not support the HTTP protocol version used in the request.'),
    ];

    $message = $messages[$code] ?? __('Error');
    $description =
        $descriptions[$code] ?? __('An unexpected error occurred. Please try again later or contact support.');

    $imageLight = "assets/media/illustrations/{$code}.svg";
    $imageDark = "assets/media/illustrations/{$code}-dark.svg";
@endphp

@section('content')
    <div class="w-full h-screen flex items-center justify-center bg-white dark:bg-gray-900">
        <div class="text-center max-w-md px-4">
            <div class="mb-9">
                <img alt="error image" class="dark:hidden mx-auto max-h-[150px]" src="{{ asset($imageLight) }}"
                    onerror="this.style.display='none'">
                <img alt="error image" class="light:hidden mx-auto max-h-[150px]" src="{{ asset($imageDark) }}"
                    onerror="this.style.display='none'">
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-3">{{ $code }}</h1>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">{{ $message }}</h3>
            <div class="text-sm text-gray-700 dark:text-gray-300 mb-7">
                {{ $description }}
            </div>
            <a class="btn btn-primary inline-flex justify-center" href="{{ url('/') }}">
                {{ translate('Go to Home') }}
            </a>
        </div>
    </div>
@endsection
