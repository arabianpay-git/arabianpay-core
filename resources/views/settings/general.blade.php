@extends('layouts.base')

@section('content')
    <main class="grow content pt-5">
        <div class="container-fixed">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-6">
                <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-primary">
                    <i class="ki-duotone ki-setting-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </a>
                <i class="ki-duotone ki-right text-gray-400 text-xs"></i>
                <span class="text-sm font-medium text-gray-700">General Settings</span>
            </div>

            {{-- Page Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">General Settings</h1>
                <p class="text-gray-600">Configure your site's basic information and general preferences.</p>
            </div>

            {{-- Settings Form --}}
            <form action="{{ route('settings.update', 'general') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {{-- Left Column --}}
                    <div class="lg:col-span-2 space-y-6">
                        {{-- Site Information Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Site Information</h3>
                                <p class="card-subtitle">Basic information about your website</p>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <div class="form-group">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" class="form-control" name="site_name"
                                            value="{{ Setting::getByKey('site_name', config('app.name')) }}">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Site Title</label>
                                        <input type="text" class="form-control" name="site_title"
                                            value="{{ Setting::getByKey('site_title') }}">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Site Description</label>
                                        <textarea class="form-control" name="site_description" rows="3">{{ Setting::getByKey('site_description') }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Contact Email</label>
                                        <input type="email" class="form-control" name="contact_email"
                                            value="{{ Setting::getByKey('contact_email') }}">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Contact Phone</label>
                                        <input type="text" class="form-control" name="contact_phone"
                                            value="{{ Setting::getByKey('contact_phone') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Localization Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Localization</h3>
                                <p class="card-subtitle">Language and regional settings</p>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <div class="form-group">
                                        <label class="form-label">Default Language</label>
                                        <select class="form-select" name="default_language">
                                            <option value="en"
                                                {{ Setting::getByKey('default_language', 'en') == 'en' ? 'selected' : '' }}>
                                                English</option>
                                            <option value="ar"
                                                {{ Setting::getByKey('default_language', 'en') == 'ar' ? 'selected' : '' }}>
                                                Arabic</option>
                                            <option value="fr"
                                                {{ Setting::getByKey('default_language', 'en') == 'fr' ? 'selected' : '' }}>
                                                French</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Timezone</label>
                                        <select class="form-select" name="timezone">
                                            @foreach (timezone_identifiers_list() as $timezone)
                                                <option value="{{ $timezone }}"
                                                    {{ Setting::getByKey('timezone', config('app.timezone')) == $timezone ? 'selected' : '' }}>
                                                    {{ $timezone }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Date Format</label>
                                        <select class="form-select" name="date_format">
                                            <option value="d/m/Y"
                                                {{ Setting::getByKey('date_format', 'd/m/Y') == 'd/m/Y' ? 'selected' : '' }}>
                                                DD/MM/YYYY</option>
                                            <option value="m/d/Y"
                                                {{ Setting::getByKey('date_format', 'd/m/Y') == 'm/d/Y' ? 'selected' : '' }}>
                                                MM/DD/YYYY</option>
                                            <option value="Y-m-d"
                                                {{ Setting::getByKey('date_format', 'd/m/Y') == 'Y-m-d' ? 'selected' : '' }}>
                                                YYYY-MM-DD</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Time Format</label>
                                        <select class="form-select" name="time_format">
                                            <option value="12"
                                                {{ Setting::getByKey('time_format', '12') == '12' ? 'selected' : '' }}>12
                                                Hour</option>
                                            <option value="24"
                                                {{ Setting::getByKey('time_format', '12') == '24' ? 'selected' : '' }}>24
                                                Hour</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column --}}
                    <div class="space-y-6">
                        {{-- Logo & Favicon Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Branding</h3>
                                <p class="card-subtitle">Logos and favicon</p>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <div class="form-group">
                                        <label class="form-label">Logo</label>
                                        <div class="mt-2">
                                            <img id="logo-preview"
                                                src="{{ Setting::getByKey('logo') ? asset(Setting::getByKey('logo')) : asset('assets/media/images/default-logo.svg') }}"
                                                class="h-10 mb-2">
                                            <input type="file" class="form-control" name="logo" id="logo-upload"
                                                accept="image/*">
                                            <input type="hidden" name="logo_url"
                                                value="{{ Setting::getByKey('logo') }}">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Favicon</label>
                                        <div class="mt-2">
                                            <img id="favicon-preview"
                                                src="{{ Setting::getByKey('favicon') ? asset(Setting::getByKey('favicon')) : asset('favicon.ico') }}"
                                                class="h-8 w-8 mb-2">
                                            <input type="file" class="form-control" name="favicon"
                                                id="favicon-upload" accept="image/x-icon,image/png">
                                            <input type="hidden" name="favicon_url"
                                                value="{{ Setting::getByKey('favicon') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions Card --}}
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <button type="submit" class="btn btn-primary w-full">
                                        <i class="ki-duotone ki-check-circle me-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        Save Changes
                                    </button>

                                    <button type="button" class="btn btn-outline-secondary w-full"
                                        onclick="resetForm()">
                                        <i class="ki-duotone ki-reset me-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        Reset to Default
                                    </button>

                                    <a href="{{ route('settings.index') }}" class="btn btn-light w-full">
                                        <i class="ki-duotone ki-arrow-left me-2">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        Back to Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    @push('scripts')
        <script>
            // Handle logo upload preview
            document.getElementById('logo-upload').addEventListener('change', function(e) {
                if (e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('logo-preview').src = e.target.result;
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            // Handle favicon upload preview
            document.getElementById('favicon-upload').addEventListener('change', function(e) {
                if (e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('favicon-preview').src = e.target.result;
                    }
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            function resetForm() {
                if (confirm('Are you sure you want to reset all settings to default?')) {
                    // Implement reset logic here
                    window.location.reload();
                }
            }
        </script>
    @endpush
@endsection
