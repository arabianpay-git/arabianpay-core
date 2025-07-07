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
                            <h3 class="card-title">
                                {{ translate('Add New Category') }}
                            </h3>
                        </div>

                        <form action="{{ route('categories.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">
                                <div class="flex gap-4">
                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                {{ translate('Category Name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input class="input @error('name') border-red-500 @enderror" name="name"
                                                type="text" value="{{ old('name') }}" required />
                                        </div>
                                        @error('name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <div class="flex flex-col gap-2.5">
                                            <label class="form-label">
                                                {{ translate('Units') }}
                                            </label>
                                            <input id="unit-input" type="text" name="unit[]"
                                                class="input w-full @error('unit') border-red-500 @enderror"
                                                placeholder="{{ translate('Type unit and press Enter') }}" multiple />
                                        </div>
                                        @error('unit')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>

                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1">
                                            {{ translate('Parent') }}
                                        </label>
                                        <select class="input @error('category_id') border-red-500 @enderror"
                                            name="category_id">
                                            <option value="">{{ translate('Select Parent Category') }}</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('category_id')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex gap-2.5">
                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                {{ translate('Featured') }}
                                            </label>
                                            <select class="input @error('featured') border-red-500 @enderror"
                                                name="featured">
                                                <option value="">{{ translate('Select One Option') }}</option>
                                                <option value="true" {{ old('featured') == 'true' ? 'selected' : '' }}>
                                                    {{ translate('Yes') }}</option>
                                                <option value="false" {{ old('featured') == 'false' ? 'selected' : '' }}>
                                                    {{ translate('No') }}</option>
                                            </select>
                                        </div>
                                        @error('featured')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                {{ translate('Order Level') }}
                                            </label>
                                            <input class="input @error('order_level') border-red-500 @enderror"
                                                name="order_level" type="number" value="{{ old('order_level') }}" />
                                        </div>
                                        @error('order_level')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Icon Picker -->
                                @include('media.single', [
                                    'name' => 'icon',
                                    'label' => translate('Category Icon'),
                                    'required' => false,
                                    'value' => old('icon'),
                                ])

                                @include('media.single', [
                                    'name' => 'banner',
                                    'label' => translate('Category Banner'),
                                    'required' => false,
                                    'value' => old('banner'),
                                ])

                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Meta Title') }}
                                        </label>
                                        <input class="input @error('meta_title') border-red-500 @enderror" name="meta_title"
                                            type="text" value="{{ old('meta_title') }}" />
                                    </div>
                                    @error('meta_title')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Meta Description') }}
                                        </label>
                                        <textarea class="textarea @error('meta_description') border-red-500 @enderror" name="meta_description" rows="4">{{ old('meta_description') }}</textarea>
                                    </div>
                                    @error('meta_description')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">
                                        {{ translate('Save Changes') }}
                                    </button>
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
