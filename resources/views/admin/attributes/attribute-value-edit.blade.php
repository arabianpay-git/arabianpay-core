@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Attribute Value') }}</h3>
                        </div>

                        <!-- Language Tabs -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex gap-4" id="langTabs">
                                <button class="tab-btn active" data-tab="en">{{ translate('English') }}</button>
                                <button class="tab-btn" data-tab="ar">{{ translate('Arabic') }}</button>
                            </nav>
                        </div>

                        <!-- Update Form -->
                        <form action="{{ route('attribute-values.update', $attributeValue->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="p-5">
                                <div class="tab-content" id="tab-en">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Attribute Value') }}
                                                </label>
                                                <input class="input @error('value.en') border-red-500 @enderror"
                                                    name="value[en]" type="text"
                                                    value="{{ old('value.en', $attributeValue->value) }}" required />
                                            </div>
                                            @error('value.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content hidden" id="tab-ar">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Attribute Value') }}
                                                </label>
                                                <input class="input @error('value.ar') border-red-500 @enderror"
                                                    name="value[ar]" type="text"
                                                    value="{{ old('value.ar', $attributeValue->translations->where('locale', 'ar')->first()->value ?? '') }}" />
                                            </div>
                                            @error('value.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between pt-2.5">
                                    <a href="{{ route('attributes.editAttributeValue', $attributeValue->attribute_id) }}"
                                        class="btn btn-danger">
                                        {{ translate('Go Back') }}
                                    </a>
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
