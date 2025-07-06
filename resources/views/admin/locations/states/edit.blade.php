@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit State') }}</h3>
                        </div>

                        <!-- Language Tabs -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex gap-4" id="langTabs">
                                <button class="tab-btn active" data-tab="en">{{ translate('English') }}</button>
                                <button class="tab-btn" data-tab="ar">{{ translate('Arabic') }}</button>
                            </nav>
                        </div>

                        <!-- Update Form -->
                        <form action="{{ route('states.update', $state->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="p-5">
                                <!-- English Tab -->
                                <div class="tab-content" id="tab-en">
                                    <div class="grid gap-5">
                                        <!-- Name (EN) -->
                                        <div class="w-full">
                                            <label class="form-label">{{ translate('State Name (English)') }}</label>
                                            <input class="input @error('name.en') border-red-500 @enderror" name="name[en]"
                                                type="text" value="{{ old('name.en', $state->name) }}" required />
                                            @error('name.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Arabic Tab -->
                                <div class="tab-content hidden" id="tab-ar">
                                    <div class="grid gap-5">
                                        <!-- Name (AR) -->
                                        <div class="w-full">
                                            <label class="form-label">{{ translate('State Name (Arabic)') }}</label>
                                            <input class="input @error('name.ar') border-red-500 @enderror" name="name[ar]"
                                                type="text"
                                                value="{{ old('name.ar', $state->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                            @error('name.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Country Select -->
                                <div class="w-full mt-5">
                                    <label class="form-label">{{ translate('Country') }}</label>
                                    <select name="country_id" class="input @error('country_id') border-red-500 @enderror"
                                        required>
                                        <option value="">{{ translate('Select Country') }}</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}"
                                                {{ old('country_id', $state->country_id) == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('country_id')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">{{ translate('Save Changes') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
