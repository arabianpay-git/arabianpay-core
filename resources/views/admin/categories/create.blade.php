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

        .select2-container .select2-selection--single {
            height: 40px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 8px !important;
            right: 4px !important;
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
                                        <div class="flex flex-col gap-2.5 w-full">
                                            <label for="parent_id" class="form-label font-medium text-gray-700">
                                                {{ translate('Parent') }}
                                            </label>
                                            <select id="parent_id"
                                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('parent_id') border-red-500 @enderror"
                                                name="parent_id">
                                                <option value="">{{ translate('Select Parent Category') }}</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ old('parent_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('parent_id')
                                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                </div>

                                <div class="w-full" id="unit-field-wrapper">
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
                                                {{ translate('Order Level') }} <span class="text-danger">*</span>
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
    <!-- Select2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#parent_id').select2({
                placeholder: '{{ translate('Select Parent Category') }}',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
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

        function toggleUnitField() {
            const parentSelect = document.querySelector('[name="parent_id"]');
            const unitFieldWrapper = document.getElementById('unit-field-wrapper'); // FIXED
            if (parentSelect.value) {
                unitFieldWrapper.style.display = 'none';
            } else {
                unitFieldWrapper.style.display = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const parentSelect = document.querySelector('[name="parent_id"]');
            toggleUnitField();
            parentSelect.addEventListener('change', toggleUnitField);
        });
    </script>
@endpush
