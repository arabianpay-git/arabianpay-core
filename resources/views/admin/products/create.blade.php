@extends('layouts.base') @section('content')
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <style>
            .ck.ck-toolbar {
                flex-wrap: wrap !important;
                max-width: 100% !important;
                box-sizing: border-box;
                overflow-x: auto;
            }

            .ck.ck-toolbar .ck-toolbar__items {
                flex-wrap: wrap !important;
                max-width: 100%;
            }

            .ck-editor__editable_inline {
                min-height: 180px !important;
                max-width: 100% !important;
                overflow-x: auto;
                box-sizing: border-box;
            }

            .ck-toolbar {
                scrollbar-width: thin;
                scrollbar-color: #c1c1c1 transparent;
            }

            .ck-toolbar::-webkit-scrollbar {
                height: 6px;
            }

            .ck-toolbar::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 10px;
            }

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

    <main class="grow content pt-5" id="content" role="content">

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Add New Product') }}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="container-fixed">
                @if ($errors->any())
                    <div class="grid gap-5 lg:gap-7.5 mb-5">
                        <div class="card rounded-xl">
                            <div
                                class="flex items-center flex-wrap sm:flex-wrap justify-between grow gap-2 p-5 rtl:[background-position:-30%_41%] [background-position:121%_41%] bg-no-repeat bg-[length:660px_310px] upgrade-bg">
                                <div class="flex items-center gap-4">
                                    <div class="relative size-[50px] shrink-0">
                                        <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none"
                                            height="48" viewBox="0 0 44 48" width="44"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506
                                                                                                                18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937
                                                                                                                39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                fill=""></path>
                                            <path
                                                d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506
                                                                                                                18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937
                                                                                                                39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                stroke=""></path>
                                        </svg>
                                        <div
                                            class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                            <i class="ki-filled ki-information-4 text-xl text-brand"> </i>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <a class="text-base font-medium text-gray-900 hover:text-primary-active"
                                                href="#">
                                                {{ translate('Please fix the following errors:') }}
                                            </a>
                                        </div>
                                        <div class="text-2sm text-gray-800">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li class="text-danger">{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                @endif
                <!-- begin: grid -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5">
                    <div class="col-span-2">
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Select Supplier') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Merchants') }}
                                                </label>
                                                <select class="input @error('user_id') border-red-500 @enderror"
                                                    name="user_id" required>
                                                    <option value="">{{ translate('Select Merchant') }}</option>
                                                    @foreach ($merchants as $merchant)
                                                        <option value="{{ $merchant->id }}"
                                                            {{ old('user_id') == $merchant->id ? 'selected' : '' }}>
                                                            {{ $merchant->business_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('user_id')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Product Information') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Product Name') }} <span class="text-danger">*</span>
                                                </label>
                                                <input class="input @error('name') border-red-500 @enderror" name="name"
                                                    type="text" value="{{ old('name') }}" required />
                                            </div>
                                            @error('name')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex flex-col gap-1.5">
                                                <label class="form-label">
                                                    {{ translate('Category') }}
                                                </label>
                                                <select id="parent_id"
                                                    class="select w-full @error('parent_id') border-red-500 @enderror"
                                                    name="parent_id" required>
                                                    <option value="">{{ translate('Select Category') }}</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}"
                                                            {{ old('parent_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('parent_id')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex flex-col gap-1.5">
                                                <label class="form-label">
                                                    {{ translate('Brand') }}
                                                </label>
                                                <select id="brand_id"
                                                    class="select w-full @error('brand_id') border-red-500 @enderror"
                                                    name="brand_id">
                                                    <option value="">{{ translate('Select Brand') }}</option>
                                                    @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}"
                                                            {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                                            {{ $brand->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('brand_id')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Unit') }}
                                                </label>
                                                <select id="unit-select" name="unit"
                                                    class="input @error('unit') border-red-500 @enderror">
                                                    <option value="">{{ translate('Select Unit') }}</option>
                                                    {{-- Options will be dynamically loaded via JS --}}
                                                </select>
                                            </div>
                                            @error('unit')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Weight') }} <small>({{ translate('In KG') }})</small>
                                                </label>
                                                <input class="input @error('weight') border-red-500 @enderror"
                                                    name="weight" type="number" value="{{ old('weight', 0.0) }}"
                                                    step="0.01" placeholder="0.00" />
                                            </div>
                                            @error('weight')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Minimum Purchase Quantity') }}
                                                </label>
                                                <input class="input @error('min_qty') border-red-500 @enderror"
                                                    name="min_qty" type="number" value="{{ old('min_qty', 1) }}"
                                                    placeholder="1" />
                                            </div>
                                            @error('min_qty')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex flex-col gap-1.5">
                                                <label class="form-label">{{ translate('Tags') }}</label>
                                                <input id="tags"
                                                    class="input w-full @error('tags') border-red-500 @enderror"
                                                    name="tags[]" type="text"
                                                    placeholder="{{ translate('Type and hit enter to add a tag') }}"
                                                    multiple
                                                    value="{{ old('tags') ? implode(',', (array) old('tags')) : '' }}" />
                                            </div>
                                            <span
                                                class="text-xs">{{ translate('This is used for search. Input those words by which customer can find this product.') }}</span>
                                            @error('tags')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <label class="form-label max-w-56">
                                                {{ translate('Refundable') }}
                                            </label>
                                            <div class="grow">
                                                <label class="switch">
                                                    <span class="switch-label text-sm">
                                                        {{ translate('Is Product Refundable') }}
                                                    </span>
                                                    <input name="refundable" type="checkbox" value="1"
                                                        {{ old('refundable') ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Product Images') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        @include('media.single', [
                                            'name' => 'thumbnail',
                                            'label' => translate('Product Thumbnail'),
                                            'required' => false,
                                            'value' => old('thumbnail'),
                                            'info' => translate(
                                                'These images are visible in product details page gallery. Use 600x600 sizes images.'),
                                        ])
                                        @include('media.multiple', [
                                            'name' => 'photos',
                                            'label' => translate('Gallery Images'),
                                            'required' => false,
                                            'value' => old('photos', []),
                                            'info' => translate(
                                                'This image is visible in all product box. Use 300x300 sizes image. Keep some blank space around main object of your image as we had to crop some edge in different devices to make it responsive.'),
                                            'multiple_select' => true,
                                        ])
                                    </div>
                                </div>
                            </div>

                            {{-- Variation --}}
                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Product Variation') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex flex-col gap-2.5">
                                                <label for="attribute_id" class="form-label text-sm font-semibold mb-2">
                                                    {{ translate('Attributes') }}
                                                </label>
                                                <select id="attribute_id"
                                                    class="input @error('attribute_id') border-red-500 @enderror w-full"
                                                    name="attribute_id[]" multiple>
                                                    <option value="">{{ translate('Select Attributes') }}</option>
                                                    @foreach ($attributes as $attribute)
                                                        <option value="{{ $attribute->id }}"
                                                            {{ collect(old('attribute_id'))->contains($attribute->id) ? 'selected' : '' }}>
                                                            {{ $attribute->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('attribute_id')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Product Prices & Stock') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="flex gap-2">
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Unit Price') }} <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="input @error('unit_price') border-red-500 @enderror"
                                                        name="unit_price" type="number"
                                                        value="{{ old('unit_price', 0) }}" required />
                                                </div>
                                                @error('unit_price')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Cost Per item') }} <span
                                                            class="text-danger">*</span>
                                                    </label>
                                                    <input class="input @error('purchase_price') border-red-500 @enderror"
                                                        name="purchase_price" type="number"
                                                        value="{{ old('purchase_price', 0) }}" required />
                                                </div>
                                                <span
                                                    class="text-xs">{{ translate("Customers won't see this price.") }}</span>
                                                @error('purchase_price')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Discount Start Date') }}
                                                    </label>
                                                    <input
                                                        class="input @error('discount_start_date') border-red-500 @enderror"
                                                        name="discount_start_date"
                                                        placeholder="{{ translate('Discount Start Date') }}"
                                                        type="date" value="{{ old('discount_start_date') }}"
                                                        required />
                                                </div>
                                                @error('discount_start_date')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Discount End Date') }}
                                                    </label>
                                                    <input
                                                        class="input @error('discount_end_date') border-red-500 @enderror"
                                                        name="discount_end_date"
                                                        placeholder="{{ translate('Discount End Date') }}" type="date"
                                                        value="{{ old('discount_end_date') }}" required />
                                                </div>
                                                @error('discount_end_date')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Discount') }}
                                                    </label>
                                                    <input class="input @error('discount') border-red-500 @enderror"
                                                        name="discount" type="number" value="{{ old('discount') }}"
                                                        required />
                                                </div>
                                                @error('discount')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        {{ translate('Discount Type') }}
                                                    </label>
                                                    <select class="input @error('discount_type') border-red-500 @enderror"
                                                        name="discount_type">
                                                        <option value="">{{ translate('Select One Option') }}
                                                        </option>
                                                        <option value="amount"
                                                            {{ old('discount_type') == 'amount' ? 'selected' : '' }}>
                                                            {{ translate('Flat') }}
                                                        </option>
                                                        <option value="percent"
                                                            {{ old('discount_type') == 'percent' ? 'selected' : '' }}>
                                                            {{ translate('Percent') }}
                                                        </option>
                                                    </select>
                                                </div>
                                                @error('discount_type')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Quantity') }}
                                                </label>
                                                <input class="input @error('current_stock') border-red-500 @enderror"
                                                    name="current_stock" type="number"
                                                    value="{{ old('current_stock') }}" required />
                                            </div>
                                            @error('current_stock')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('SKU') }}
                                                </label>
                                                <input class="input @error('sku') border-red-500 @enderror"
                                                    name="sku" type="text" value="{{ old('sku') }}" />
                                            </div>
                                            @error('sku')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- description --}}
                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Product Description') }}
                                    </h3>
                                </div>

                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex flex-col gap-2.5">
                                                <label class="form-label">
                                                    {{ translate('Short Description') }}
                                                </label>
                                                <textarea id="short_description" name="short_description"
                                                    class="input @error('short_description') border-red-500 @enderror">{{ old('short_description') }}</textarea>
                                            </div>
                                            @error('short_description')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex flex-col gap-2.5">
                                                <label class="form-label">
                                                    {{ translate('Description') }}
                                                </label>
                                                <textarea id="description" name="description" class="input @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                                            </div>
                                            @error('description')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- meta tags --}}
                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('SEO Meta Tags') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Meta Title') }}
                                                </label>
                                                <input class="input @error('meta_title') border-red-500 @enderror"
                                                    name="meta_title" type="text" value="{{ old('meta_title') }}" />
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
                                                <textarea class="textarea @error('meta_description') border-red-500 @enderror" name="meta_description"
                                                    rows="4">{{ old('meta_description') }}</textarea>
                                            </div>
                                            @error('meta_description')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        @include('media.single', [
                                            'name' => 'meta_img',
                                            'label' => translate('Meta Image'),
                                            'required' => false,
                                            'value' => old('meta_img'),
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1">
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Shipping Configuration') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        @php
                                            $oldShippingType = old('shipping_type');
                                        @endphp

                                        <!-- Free Shipping Checkbox -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        {{ translate('Free Shipping') }}
                                                    </span>
                                                    <input id="free_shipping" name="shipping_type" type="checkbox"
                                                        value="free"
                                                        {{ $oldShippingType === 'free' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Flat Rate Checkbox -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        {{ translate('Flat Rate') }}
                                                    </span>
                                                    <input id="flat_rate" name="shipping_type" type="checkbox"
                                                        value="flat_rate"
                                                        {{ $oldShippingType === 'flat_rate' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Flat Rate Input Field (toggle visibility) -->
                                        <div id="flat_rate_input"
                                            class="flex items-center gap-2.5 {{ $oldShippingType === 'flat_rate' ? '' : 'hidden' }}">
                                            <label for="flat_rate_amount"
                                                class="text-sm">{{ translate('Flat Rate Amount') }}</label>
                                            <input id="flat_rate_amount" type="number"
                                                class="input @error('shipping_cost') border-red-500 @enderror"
                                                name="shipping_cost" placeholder="{{ translate('Enter amount') }}"
                                                value="{{ old('shipping_cost') }}" />
                                        </div>

                                        <!-- Is Product Quantity Multiply Checkbox -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        {{ translate('Is Product Quantity Multiply') }}
                                                    </span>
                                                    <input id="quantity_multiply" name="shipping_type" type="checkbox"
                                                        value="product_wise"
                                                        {{ $oldShippingType === 'product_wise' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Low Stock Quantity Warning') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Quantity') }}
                                                </label>
                                                <input class="input @error('low_stock_quantity') border-red-500 @enderror"
                                                    name="low_stock_quantity" type="number"
                                                    value="{{ old('low_stock_quantity', 1) }}" />
                                            </div>
                                            @error('low_stock_quantity')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Stock Visibility State') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">

                                        <!-- Show Stock Quantity -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span
                                                        class="switch-label text-sm">{{ translate('Show Stock Quantity') }}</span>
                                                    <input id="stock_qty" name="stock_visibility_state" type="checkbox"
                                                        value="quantity"
                                                        {{ old('stock_visibility_state') === 'quantity' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Show Stock With Text Only -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span
                                                        class="switch-label text-sm">{{ translate('Show Stock With Text Only') }}</span>
                                                    <input id="stock_text" name="stock_visibility_state" type="checkbox"
                                                        value="text"
                                                        {{ old('stock_visibility_state') === 'text' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Hide Stock -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span
                                                        class="switch-label text-sm">{{ translate('Hide Stock') }}</span>
                                                    <input id="stock_hide" name="stock_visibility_state" type="checkbox"
                                                        value="hide"
                                                        {{ old('stock_visibility_state') === 'hide' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Featured') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        {{ translate('Status') }}
                                                    </span>
                                                    <!-- Hidden input to send false if checkbox is unchecked -->
                                                    <input type="hidden" name="featured" value="0" />
                                                    <input id="featured" name="featured" type="checkbox" value="1"
                                                        {{ old('featured', $yourModel->featured ?? false) ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Estimate Shipping Time') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Shipping Days') }}
                                                </label>
                                                <input class="input @error('est_shipping_days') border-red-500 @enderror"
                                                    name="est_shipping_days" type="number"
                                                    value="{{ old('est_shipping_days') }}" />
                                            </div>
                                            @error('est_shipping_days')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        {{ translate('Vat & TAX') }}
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('VAT') }}
                                                </label>
                                                <input class="input @error('tax') border-red-500 @enderror"
                                                    name="tax" type="number" value="{{ old('tax') }}" />
                                            </div>
                                            @error('tax')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('VAT Type') }}
                                                </label>
                                                <select class="input @error('tax_type') border-red-500 @enderror"
                                                    name="tax_type">
                                                    <option value="">{{ translate('Select One Option') }}</option>
                                                    <option value="amount"
                                                        {{ old('tax_type') == 'amount' ? 'selected' : '' }}>
                                                        {{ translate('Flat') }}</option>
                                                    <option value="percent"
                                                        {{ old('tax_type') == 'percent' ? 'selected' : '' }}>
                                                        {{ translate('Percent') }}</option>
                                                </select>
                                            </div>
                                            @error('tax_type')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end pt-2.5">
                    <button type="submit" name="action" value="unpublish" class="btn btn-danger"
                        style="margin-right: 7px">
                        {{ translate('Save & Unpublish') }}
                    </button>

                    <button type="submit" name="action" value="publish" class="btn btn-primary">
                        {{ translate('Save & Publish') }}
                    </button>
                </div>

                <!-- end: grid -->
            </div>
        </form>
        <!-- End of Container -->
    </main>

@endsection

@push('scripts')
    <script src="{{ asset('assets/js/ckeditor.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("input[name='discount_start_date']", {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
        });

        flatpickr("input[name='discount_end_date']", {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
        });
    </script>
    <script>
        ClassicEditor
            .create(document.querySelector('#short_description'), {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
                    'bulletedList', 'numberedList', 'todoList', '|',
                    'alignment', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                    'highlight', '|',
                    'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                    'undo', 'redo'
                ]
            })
            .catch(error => {
                console.error(error);
            });

        ClassicEditor
            .create(document.querySelector('#description'), {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript', '|',
                    'bulletedList', 'numberedList', 'todoList', '|',
                    'alignment', '|',
                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
                    'highlight', '|',
                    'imageUpload', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                    'undo', 'redo'
                ]
            })
            .catch(error => {
                console.error(error);
            });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="stock_visibility_state"]');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        checkboxes.forEach(cb => {
                            if (cb !== this) cb.checked = false;
                        });
                    }
                });
            });
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const freeShippingCheckbox = document.querySelector('#free_shipping');
            const flatRateCheckbox = document.querySelector('#flat_rate');
            const quantityMultiplyCheckbox = document.querySelector('#quantity_multiply');
            const flatRateInput = document.querySelector('#flat_rate_input');

            // Function to uncheck all checkboxes except the one being passed as 'except'
            function uncheckOtherCheckboxes(except) {
                if (except !== 'free_shipping') freeShippingCheckbox.checked = false;
                if (except !== 'flat_rate') flatRateCheckbox.checked = false;
                if (except !== 'quantity_multiply') quantityMultiplyCheckbox.checked = false;

                // Only hide the flat rate input if flat rate checkbox is unchecked
                if (except !== 'flat_rate') {
                    flatRateInput.classList.add('hidden');
                }
            }

            // Event listener for free shipping checkbox
            freeShippingCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    uncheckOtherCheckboxes('free_shipping');
                }
            });

            // Event listener for flat rate checkbox
            flatRateCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    uncheckOtherCheckboxes('flat_rate');
                    flatRateInput.classList.remove('hidden');
                } else {
                    flatRateInput.classList.add('hidden');
                }
            });

            // Event listener for quantity multiply checkbox
            quantityMultiplyCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    uncheckOtherCheckboxes('quantity_multiply');
                    flatRateInput.classList.remove('hidden');
                } else {
                    flatRateInput.classList.add('hidden');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#category_id', {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });

            new Choices('#brand_id', {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false
            });

            new Choices('#tags', {
                removeItemButton: true,
                duplicateItemsAllowed: false,
                delimiter: ',',
            });

            new Choices('#attribute_id', {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                removeItemButton: true,
                duplicateItemsAllowed: false,
            });
        });
    </script>
@endpush


@push('scripts')
    <script>
        document.getElementById('category_id').addEventListener('change', function() {
            const categoryId = this.value;
            const unitSelect = document.getElementById('unit-select');

            unitSelect.innerHTML = `<option value="">${@json(translate('Loading...'))}</option>`;

            if (!categoryId) {
                unitSelect.innerHTML = `<option value="">${@json(translate('Select Unit'))}</option>`;
                return;
            }

            fetch(`{{ url('admin/get-category-units') }}/${categoryId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch');
                    return response.json();
                })
                .then(data => {
                    unitSelect.innerHTML = `<option value="">${@json(translate('Select Unit'))}</option>`;
                    data.units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit;
                        option.textContent = unit;
                        unitSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching units:', error);
                    unitSelect.innerHTML = `<option value="">${@json(translate('Failed to load'))}</option>`;
                });
        });
    </script>
@endpush
