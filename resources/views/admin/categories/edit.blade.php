@extends('layouts.base')
@push('styles')
    <style>
        .choices__inner {
            min-height: 2.4rem !important;
            height: 2.4rem !important;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            border-radius: 0.375rem;
        }

        .choices__input {
            height: auto !important;

            margin: 0 !important;
        }

        .choices__list--multiple .choices__item {
            border-radius: 0.375rem;
            font-size: 0.875rem;
            padding: 0 7px;
        }

        .choices__list {
            position: relative !important;
            z-index: 9999 !important;
        }

        .choices {
            position: relative !important;
        }
    </style>
@endpush
@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Category') }}</h3>
                        </div>

                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex gap-4" id="langTabs">
                                <button class="tab-btn active" data-tab="en">{{ translate('English') }}</button>
                                <button class="tab-btn" data-tab="ar">{{ translate('Arabic') }}</button>
                            </nav>
                        </div>

                        <form action="{{ route('categories.update', $category->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="p-5">

                                <div class="tab-content" id="tab-en">
                                    <div class="grid gap-5">

                                        <div class="flex gap-4">
                                            {{-- Category Name --}}
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Category Name') }} <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="input @error('name') border-red-500 @enderror"
                                                        name="name" type="text"
                                                        value="{{ old('name', $category->name ?? '') }}" required />
                                                </div>
                                                @error('name')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            {{-- Units --}}
                                            <div class="w-full">
                                                <div class="flex flex-col gap-2.5">
                                                    <label class="form-label">{{ translate('Units') }}</label>
                                                    <input id="unit-input" type="text" name="unit"
                                                        class="input w-full @error('unit') border-red-500 @enderror"
                                                        placeholder="{{ translate('Type unit and press Enter') }}"
                                                        value="{{ old('unit', is_array($category->unit) ? implode(',', $category->unit) : $category->unit) }}" />
                                                </div>
                                                @error('unit')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>


                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Parent Category') }}</label>
                                            <select name="parent_id"
                                                class="input @error('parent_id') border-red-500 @enderror">
                                                <option value="">{{ translate('Select Parent Category') }}</option>
                                                @foreach ($categories as $categoryOption)
                                                    <option value="{{ $categoryOption->id }}"
                                                        {{ old('parent_id', $category->parent_id) == $categoryOption->id ? 'selected' : '' }}>
                                                        {{ $categoryOption->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('parent_id')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Featured') }}</label>
                                            <select class="input @error('featured') border-red-500 @enderror"
                                                name="featured">
                                                <option value="true"
                                                    {{ old('featured', $category->featured) == true ? 'selected' : '' }}>
                                                    {{ translate('Yes') }}</option>
                                                <option value="false"
                                                    {{ old('featured', $category->featured) == false ? 'selected' : '' }}>
                                                    {{ translate('No') }}</option>
                                            </select>
                                            @error('featured')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Order Level') }}</label>
                                            <input class="input @error('order_level') border-red-500 @enderror"
                                                name="order_level" type="number"
                                                value="{{ old('order_level', $category->order_level) }}" />
                                            @error('order_level')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        @include('media.single', [
                                            'name' => 'icon',
                                            'label' => translate('Category Icon'),
                                            'required' => false,
                                            'value' => old('icon', $category->icon),
                                        ])

                                        @include('media.single', [
                                            'name' => 'banner',
                                            'label' => translate('Category Banner'),
                                            'required' => false,
                                            'value' => old('banner', $category->banner),
                                        ])

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Title (English)') }}</label>
                                            <input class="input @error('meta_title.en') border-red-500 @enderror"
                                                name="meta_title[en]" type="text"
                                                value="{{ old('meta_title.en', $category->translations->where('locale', 'en')->first()->meta_title ?? '') }}" />
                                            @error('meta_title.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Description (English)') }}</label>
                                            <textarea class="textarea @error('meta_description.en') border-red-500 @enderror" name="meta_description[en]"
                                                rows="4">{{ old('meta_description.en', $category->translations->where('locale', 'en')->first()->meta_description ?? '') }}</textarea>
                                            @error('meta_description.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content hidden" id="tab-ar">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Category Name (Arabic)') }}</label>
                                            <input class="input @error('name.ar') border-red-500 @enderror" name="name[ar]"
                                                type="text"
                                                value="{{ old('name.ar', $category->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                            @error('name.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Title (Arabic)') }}</label>
                                            <input class="input @error('meta_title.ar') border-red-500 @enderror"
                                                name="meta_title[ar]" type="text"
                                                value="{{ old('meta_title.ar', $category->translations->where('locale', 'ar')->first()->meta_title ?? '') }}" />
                                            @error('meta_title.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Description (Arabic)') }}</label>
                                            <textarea class="textarea @error('meta_description.ar') border-red-500 @enderror" name="meta_description[ar]"
                                                rows="4">{{ old('meta_description.ar', $category->translations->where('locale', 'ar')->first()->meta_description ?? '') }}</textarea>
                                            @error('meta_description.ar')
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


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        const unitChoices = new Choices('#unit-input', {
            removeItemButton: true,
            duplicateItemsAllowed: false,
            delimiter: ',',
            editItems: true,
            paste: true,
            placeholder: true,
        });
    </script>
@endpush
