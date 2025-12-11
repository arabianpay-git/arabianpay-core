@extends('layouts.base')

@push('styles')
    <style>
        .select2-container .select2-selection--single {
            height: 37px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--single .select2-selection__clear {
            line-height: 37px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 5px !important;
        }
    </style>
@endpush
@section('content')
    @php
        $general = settings('general');
    @endphp

    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">

            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ translate('General Settings') }}</h1>
                <p class="text-gray-600">
                    {{ translate('Configure your site basic information, branding, and localization preferences.') }}
                </p>
            </div>

            <form action="{{ route('settings.general.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="flex grow gap-5 lg:gap-7.5 mt-5">

                    <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5 lg:w-2/3">

                        <div class="card pb-2.5">
                            <div class="card-header" id="site_information">
                                <h3 class="card-title">{{ translate('Site Information') }}</h3>
                            </div>

                            <div class="card-body grid gap-5">

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Site Name') }}
                                    </label>
                                    <input class="input @error('site_name') border-red-500 @enderror" name="site_name"
                                        type="text" value="{{ old('site_name', $general['site_name'] ?? '') }}"
                                        required />
                                    @error('site_name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Site Title') }}
                                    </label>
                                    <input class="input @error('site_title') border-red-500 @enderror" name="site_title"
                                        type="text" value="{{ old('site_title', $general['site_title'] ?? '') }}" />
                                    @error('site_title')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Site Description') }}
                                    </label>
                                    <textarea class="textarea @error('site_description') border-red-500 @enderror" name="site_description" rows="3">{{ old('site_description', $general['site_description'] ?? '') }}</textarea>
                                    @error('site_description')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <div class="card pb-2.5">
                            <div class="card-header" id="contact_information">
                                <h3 class="card-title">{{ translate('Contact Information') }}</h3>
                            </div>

                            <div class="card-body grid gap-5">

                                <div class="grid gap-5 md:grid-cols-2">
                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('Contact Email') }}
                                        </label>
                                        <input class="input @error('contact_email') border-red-500 @enderror"
                                            name="contact_email" type="email"
                                            value="{{ old('contact_email', $general['contact_email'] ?? '') }}" />
                                        @error('contact_email')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('Contact Phone') }}
                                        </label>
                                        <input class="input @error('contact_phone') border-red-500 @enderror"
                                            name="contact_phone" type="text"
                                            value="{{ old('contact_phone', $general['contact_phone'] ?? '') }}" />
                                        @error('contact_phone')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">
                                        {{ translate('Address Line') }}
                                    </label>
                                    <textarea class="textarea @error('address_line') border-red-500 @enderror" name="address_line" rows="2">{{ old('address_line', $general['address_line'] ?? '') }}</textarea>
                                    @error('address_line')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <div class="card pb-2.5">
                            <div class="card-header" id="localization_settings">
                                <h3 class="card-title">{{ translate('Localization') }}</h3>
                            </div>

                            <div class="card-body grid gap-5 md:grid-cols-2 lg:grid-cols-4">

                                <div class="w-full">
                                    <label
                                        class="form-label flex items-center gap-1">{{ translate('Default Language') }}</label>
                                    <select class="select" name="default_language">
                                        <option value="en"
                                            {{ ($general['default_language'] ?? '') == 'en' ? 'selected' : '' }}>
                                            {{ translate('English') }}
                                        </option>
                                        <option value="ar"
                                            {{ ($general['default_language'] ?? '') == 'ar' ? 'selected' : '' }}>
                                            {{ translate('Arabic') }}
                                        </option>
                                    </select>
                                </div>

                                <div class="w-full">
                                    <label class="form-label flex items-center gap-1">{{ translate('Timezone') }}</label>
                                    <select class="select select2 w-full" name="timezone">
                                        @foreach (timezone_identifiers_list() as $timezone)
                                            <option value="{{ $timezone }}"
                                                {{ ($general['timezone'] ?? config('app.timezone')) == $timezone ? 'selected' : '' }}>
                                                {{ $timezone }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="w-full">
                                    <label
                                        class="form-label flex items-center gap-1">{{ translate('Date Format') }}</label>
                                    <select class="select" name="date_format">
                                        <option value="d M Y"
                                            {{ ($general['date_format'] ?? '') == 'd M Y' ? 'selected' : '' }}>
                                            DD/MM/YYYY
                                        </option>
                                        <option value="M d Y"
                                            {{ ($general['date_format'] ?? '') == 'M d Y' ? 'selected' : '' }}>
                                            MM/DD/YYYY
                                        </option>
                                        <option value="Y-m-d"
                                            {{ ($general['date_format'] ?? '') == 'Y-m-d' ? 'selected' : '' }}>
                                            YYYY-MM-DD
                                        </option>
                                    </select>
                                </div>

                                <div class="w-full">
                                    <label
                                        class="form-label flex items-center gap-1">{{ translate('Time Format') }}</label>
                                    <select class="select" name="time_format">
                                        <option value="12"
                                            {{ ($general['time_format'] ?? '') == '12' ? 'selected' : '' }}>
                                            12 {{ translate('Hour') }}
                                        </option>
                                        <option value="24"
                                            {{ ($general['time_format'] ?? '') == '24' ? 'selected' : '' }}>
                                            24 {{ translate('Hour') }}
                                        </option>
                                    </select>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="lg:w-1/3 flex flex-col items-stretch gap-5 lg:gap-7.5">
                        <div class="card pb-2.5">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Branding') }}</h3>
                            </div>

                            <div class="card-body grid gap-5">

                                <div class="w-full">
                                    @include('media.single', [
                                        'name' => 'logo',
                                        'label' => translate('Upload Logo'),
                                        'value' => $general['logo'] ?? null,
                                    ])
                                </div>

                                <div class="w-full">
                                    @include('media.single', [
                                        'name' => 'favicon',
                                        'label' => translate('Upload Favicon'),
                                        'value' => $general['favicon'] ?? null,
                                    ])
                                </div>

                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ translate('Actions') }}</h3>
                            </div>

                            <div class="card-body">
                                <div class="flex gap-3">

                                    <a href="{{ route('settings.index') }}" type="button"
                                        class="btn btn-outline btn-secondary w-full d-flex align-items-center justify-content-center">
                                        <i class="ki-filled ki-arrow-left me-2"></i>
                                        {{ translate('Cancel') }}
                                    </a>

                                    <button type="submit"
                                        class="btn btn-primary w-full d-flex align-items-center justify-content-center">
                                        <i class="ki-filled ki-check-circle me-2"></i>
                                        {{ translate('Save Changes') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection


@push('scripts')
    <!-- Select2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: "{{ translate('Select a timezone') }}",
                allowClear: true
            });
        });
    </script>
@endpush
