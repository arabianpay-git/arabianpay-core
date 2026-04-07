@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="mb-6">
                <a href="{{ route('settings.index') }}" class="btn btn-sm btn-light mb-4">
                    <i class="ki-filled ki-arrow-left"></i> {{ translate('Back to settings') }}
                </a>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ translate($title) }}</h1>
                <p class="text-gray-600">
                    {{ translate('This section is not configured yet. Use General and Email settings for live options.') }}
                </p>
            </div>
            <div class="card">
                <div class="card-body text-gray-500 text-sm">
                    {{ translate('If you need this screen implemented, specify the fields and storage key in your backlog.') }}
                </div>
            </div>
        </div>
    </main>
@endsection
