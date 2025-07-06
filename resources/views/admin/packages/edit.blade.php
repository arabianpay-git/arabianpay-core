@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Package') }}</h3>
                        </div>

                        <!-- Language Tabs -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex gap-4" id="langTabs">
                                <button class="tab-btn active" data-tab="en">{{ translate('English') }}</button>
                                <button class="tab-btn" data-tab="ar">{{ translate('Arabic') }}</button>
                            </nav>
                        </div>

                        <!-- Update Form -->
                        <form action="{{ route('packages.update', $package->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="p-5">
                                <div class="tab-content" id="tab-en">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Name') }}</label>
                                            <input class="input @error('name.en') border-red-500 @enderror" name="name[en]"
                                                type="text" value="{{ old('name.en', $package->name ?? '') }}"
                                                required />
                                            @error('name.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="flex gap-4">
                                            <div class="w-full">
                                                <label class="form-label">{{ translate('Min Score') }}</label>
                                                <input class="input @error('min_score') border-red-500 @enderror"
                                                    name="min_score" type="number"
                                                    value="{{ old('min_score', $package->min_score) }}" required />
                                                @error('min_score')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="w-full">
                                                <label class="form-label">{{ translate('Max Score') }}</label>
                                                <input class="input @error('max_score') border-red-500 @enderror"
                                                    name="max_score" type="number"
                                                    value="{{ old('max_score', $package->max_score) }}" required />
                                                @error('max_score')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        @include('media.single', [
                                            'name' => 'logo',
                                            'label' => translate('Package Logo'),
                                            'required' => false,
                                            'value' => old('logo', $package->logo),
                                        ])
                                    </div>
                                </div>

                                <div class="tab-content hidden" id="tab-ar">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Name (Arabic)') }}</label>
                                            <input class="input @error('name.ar') border-red-500 @enderror" name="name[ar]"
                                                type="text"
                                                value="{{ old('name.ar', $package->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                            @error('name.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

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
