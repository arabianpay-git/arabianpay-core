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
                   Edit Product
                </h1>
            </div>
            
        </div>
        <!-- Language Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-4" id="langTabs">
                <button class="tab-btn active" data-tab="en">English</button>
                <button class="tab-btn" data-tab="ar">Arabic</button>
            </nav>
        </div>
    </div>
    <!-- End of Container -->
    <!-- Container -->
    <form action="{{ route('products.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="tab-content" id="tab-en">

            <div class="container-fixed">
                @if ($errors->any())
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="card rounded-xl">
                            <div class="flex items-center flex-wrap sm:flex-wrap justify-between grow gap-2 p-5 rtl:[background-position:-30%_41%] [background-position:121%_41%] bg-no-repeat bg-[length:660px_310px] upgrade-bg">
                                <div class="flex items-center gap-4">
                                    <div class="relative size-[50px] shrink-0">
                                        <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none" height="48" viewBox="0 0 44 48" width="44" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506 
                                                    18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937 
                                                    39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                fill=""
                                            ></path>
                                            <path
                                                d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506 
                                                    18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937 
                                                    39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                stroke=""
                                            ></path>
                                        </svg>
                                        <div class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                            <i class="ki-filled ki-information-4 text-xl text-brand"> </i>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <a class="text-base font-medium text-gray-900 hover:text-primary-active" href="#">
                                                Please fix the following errors:
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
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-7.5 mt-5">
                    <div class="col-span-2">
                        <div class="grid gap-5 lg:gap-7.5">
                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Select Supplier
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Merchants
                                                </label>
                                                <select class="input @error('user_id') border-red-500 @enderror" name="user_id" required>
                                                    <option value="">Select Merchant</option>
                                                    @foreach($merchants as $merchant)
                                                        <option value="{{ $merchant->id }}" {{ old('user_id', $product->user_id) == $merchant->id ? 'selected' : '' }}>
                                                            {{ $merchant->business_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('user_id')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                                
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
                                        Product Information
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Product Name <span class="text-danger">*</span>
                                                </label>
                                                <input class="input @error('name.en') border-red-500 @enderror" name="name[en]" type="text" value="{{ old('name.en', $product->name) }}" required />
                                            </div>
                                            @error('name.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    
                                        <div class="w-full">
                                            <div class="flex flex-col gap-1.5">
                                                <label class="form-label">
                                                    Category
                                                </label>
                                                <select id="category_id" class="select w-full @error('category_id') border-red-500 @enderror" name="category_id" required>
                                                    <option value="">Select Category</option>
                                                    @foreach ($categories as $category) 
                                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}> 
                                                            {{ $category->name }} 
                                                        </option>   
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('category_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        
                                        <div class="w-full">
                                            <div class="flex flex-col gap-1.5">
                                                <label class="form-label">
                                                    Brand
                                                </label>
                                                <select id="brand_id" class="select w-full @error('brand_id') border-red-500 @enderror" name="brand_id">
                                                    <option value="">Select Brand</option>
                                                    @foreach ($brands as $brand) 
                                                        <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}> 
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
                                                    Unit
                                                </label>
                                                <input class="input @error('unit.en') border-red-500 @enderror" name="unit[en]" type="text" value="{{ old('unit.en', $product->unit) }}" placeholder="Unit (eg. KG, PC etc)" />
                                            </div>
                                            @error('unit.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Weight <small>(In KG)</small>
                                                </label>
                                                <input class="input @error('weight') border-red-500 @enderror" name="weight" type="number" value="{{ old('weight', $product->weight) }}" step="0.01" placeholder="0.00" />
                                            </div>
                                            @error('weight')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Minimum Purchase Quantity
                                                </label>
                                                <input class="input @error('min_qty') border-red-500 @enderror" name="min_qty" type="number" value="{{ old('min_qty', $product->min_qty) }}" placeholder="1" />
                                            </div>
                                            @error('min_qty')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    
                                        <div class="w-full">
                                            <div class="flex flex-col gap-1.5">
                                                <label for="tags_en" class="form-label">Tags (EN)</label>
                                                <input 
                                                    id="tags"
                                                    class="input w-full @error('tags.en') border-red-500 @enderror"
                                                    name="tags[en][]"
                                                    type="text"
                                                    placeholder="Type and hit enter to add a tag"
                                                    {{-- 🛠️ Always implode the array from old() or model --}}
                                                    value="{{ implode(',', old('tags.en', json_decode($product->tags ?? '[]', true))) }}"
                                                />
                                            </div>
                                            <span class="text-xs">This is used for search. Input words by which customers can find this product.</span>
                                            @error('tags.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                       
                                        
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <label class="form-label max-w-56">
                                                Refundable
                                            </label>
                                            <div class="grow">
                                                <label class="switch">
                                                    <span class="switch-label text-sm">
                                                        Is Product Refundable
                                                    </span>
                                                    <input name="refundable" type="checkbox" value="1" {{ old('refundable', $product->refundable) ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>    
                                    </div>                                
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Product Images
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        @include('media.single', [
                                            'name' => 'thumbnail',
                                            'label' => 'Product Thumbnail',
                                            'required' => false,
                                            'value' => old('thumbnail', $product->thumbnail ?? null),
                                            'info' => 'These images are visible in product details page gallery. Use 600x600 sizes images.'
                                        ])

                                        @include('media.multiple', [
                                            'name' => 'photos',
                                            'label' => 'Gallery Images',
                                            'required' => false,
                                            'value' => old('photos', json_decode($product->photos ?? '[]', true) ?? []),
                                            'info' => 'This image is visible in all product box. Use 300x300 sizes image. Keep some blank space around main object of your image as we had to crop some edge in different devices to make it responsive.',
                                            'multiple_select' => true,
                                        ])
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">Product Variations</h3>
                                </div>
                                @php
                                    $selectedAttributes = old('attribute_id', $product->attributes->pluck('id')->toArray());
                                @endphp
                            
                                <div class="card-body grid gap-5">
                                <!-- Attribute Select -->
                                <div>
                                    <label for="attribute_id" class="form-label text-sm font-semibold mb-2">
                                        Select Attributes
                                    </label>
                                    <select id="attribute_id" name="attribute_id[]" class="input w-full" multiple>
                                    @foreach($attributes as $attribute)
                                        <option
                                        value="{{ $attribute->id }}"
                                        data-name="{{ $attribute->name }}"
                                        {{ in_array($attribute->id, $selectedAttributes) ? 'selected' : '' }}>
                                        {{ $attribute->name }}
                                        </option>
                                    @endforeach
                                    </select>
                                </div>
                                
                                <!-- Dynamic Attribute Value Selects -->
                                <div id="attribute-values-wrapper" class="grid gap-4 hidden"></div>
                                
                                <!-- Variation Rows -->
                                <div id="variation-fields" class="grid gap-5"></div>
                                
                                <!-- Template -->
                                <template id="variation-template">
                                    <div class="variation-row grid grid-cols-1 md:grid-cols-4 gap-5 border rounded-xl p-4 bg-gray-50">
                                        <input type="hidden" name="__NAME__[attribute_id]" value="__ID__">
                                        <input type="hidden" name="__NAME__[attribute_value_id]" value="__ATTRIBUTE_VALUE_ID__">
                                        <input type="hidden" name="__NAME__[value]" value="__VALUE__">
                                    
                                        <div>
                                            <label class="form-label text-sm font-semibold mb-1">Selected</label>
                                            <input type="text" class="input w-full" value="__ATTRIBUTE_VALUE__" readonly>
                                        </div>
                                        <div>
                                            <label class="form-label text-sm font-semibold mb-1">Price</label>
                                            <input type="number" step="0.01" name="__NAME__[price]" class="input w-full" placeholder="Enter price">
                                        </div>
                                        <div>
                                            <label class="form-label text-sm font-semibold mb-1">SKU</label>
                                            <input type="text" name="__NAME__[sku]" class="input w-full" placeholder="Enter SKU">
                                        </div>
                                        @include('media.single', [
                                            'name'     => '__NAME__[variant_image]',
                                            'label'    => 'Variant Image',
                                            'required' => false,
                                            'value'    => '',
                                        ])
                                    </div>
                                </template>
                                
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Product Prices & Stock
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="flex gap-2">
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        Unit Price <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="input @error('unit_price') border-red-500 @enderror" name="unit_price" type="number" value="{{ old('unit_price', $product->unit_price ?? 0) }}" required />
                                                </div>
                                                @error('unit_price')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                    
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        Cost Per item <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="input @error('purchase_price') border-red-500 @enderror" name="purchase_price" type="number" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required />
                                                </div>
                                                <span class="text-xs">Customers won't see this price.</span>
                                                @error('purchase_price')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    
                                        <div class="flex gap-2">
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        Discount Start Date
                                                    </label>
                                                    <input class="input @error('discount_start_date') border-red-500 @enderror" name="discount_start_date" placeholder="Discount Start Date" type="date" 
                                                        value="{{ old('discount_start_date', $product->discount_start_date ? \Carbon\Carbon::parse($product->discount_start_date)->format('Y-m-d') : '') }}" />
                                                </div>
                                                @error('discount_start_date')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        Discount End Date
                                                    </label>
                                                    <input class="input @error('discount_end_date') border-red-500 @enderror" name="discount_end_date" placeholder="Discount End Date" type="date" 
                                                        value="{{ old('discount_end_date', $product->discount_end_date ? \Carbon\Carbon::parse($product->discount_end_date)->format('Y-m-d') : '') }}" />
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
                                                        Discount 
                                                    </label>
                                                    <input class="input @error('discount') border-red-500 @enderror" name="discount" type="number" value="{{ old('discount', $product->discount) }}" />
                                                </div>
                                                @error('discount')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                    
                                            <div class="w-full">
                                                <div class="flex items-baseline flex-wrap gap-2.5">
                                                    <label class="form-label flex items-center gap-1 max-w-56">
                                                        Discount Type
                                                    </label>
                                                    <select class="input @error('discount_type') border-red-500 @enderror" name="discount_type">
                                                        <option value="">Select One Option</option>
                                                        <option value="amount" {{ old('discount_type', $product->discount_type) == 'amount' ? 'selected' : '' }}>Flat</option>
                                                        <option value="percent" {{ old('discount_type', $product->discount_type) == 'percent' ? 'selected' : '' }}>Percent</option>
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
                                                    Quantity
                                                </label>
                                                <input class="input @error('current_stock') border-red-500 @enderror" name="current_stock" type="number" value="{{ old('current_stock', $product->current_stock) }}" required />
                                            </div>
                                            @error('current_stock')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    SKU
                                                </label>
                                                <input class="input @error('sku') border-red-500 @enderror" name="sku" type="text" value="{{ old('sku', $product->sku) }}" />
                                            </div>
                                            @error('sku')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>                                
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Product Description
                                    </h3>
                                </div>

                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex flex-col gap-2.5">
                                                <label class="form-label">
                                                    Short Description (English)
                                                </label>
                                                <textarea id="short_description" name="short_description[en]"
                                                    class="input @error('short_description.en') border-red-500 @enderror"
                                                >{{ old('short_description.en', $product->short_description ?? '') }}</textarea>
                                            </div>
                                            @error('short_description.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        
                                        <div class="w-full">
                                            <div class="flex flex-col gap-2.5">
                                                <label class="form-label">
                                                    Description (English)
                                                </label>
                                                <textarea id="description" name="description[en]"
                                                    class="input @error('description.en') border-red-500 @enderror"
                                                >{{ old('description.en', $product->description ?? '') }}</textarea>
                                            </div>
                                            @error('description.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        SEO Meta Tags
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Meta Title (English)
                                                </label>
                                                <input class="input @error('meta_title.en') border-red-500 @enderror"
                                                       name="meta_title[en]"
                                                       type="text"
                                                       value="{{ old('meta_title.en', $product->meta_title ?? '') }}" />
                                            </div>
                                            @error('meta_title.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Meta Description (English)
                                                </label>
                                                <textarea class="textarea @error('meta_description.en') border-red-500 @enderror"
                                                          name="meta_description[en]"
                                                          rows="4">{{ old('meta_description.en', $product->meta_description ?? '') }}</textarea>
                                            </div>
                                            @error('meta_description.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        
                                    
                                        @include('media.single', [
                                            'name' => 'meta_img',
                                            'label' => 'Meta Image',
                                            'required' => false,
                                            'value' => old('meta_img', $product->meta_img ?? ''),
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
                                        Product Status
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-center gap-2.5">
                                                <div class="grow">
                                                    <label class="text-sm block mb-1">Published</label>
                                                    <select class="input w-full @error('published') border-red-500 @enderror" name="published">
                                                        <option value="">Select One Option</option>
                                                        <option value="published" {{ old('published', $product->published ?? '') == 'published' ? 'selected' : '' }}>Published</option>
                                                        <option value="pending" {{ old('published', $product->published ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center flex-wrap gap-2.5 mt-4">
                                            <div class="grow">
                                                <label class="text-sm block mb-1">Approval</label>
                                                <select class="input w-full @error('approved') border-red-500 @enderror" name="approved">
                                                    <option value="">Select One Option</option>
                                                    <option value="approved" {{ old('approved', $product->approved ?? '') == 'approved' ? 'selected' : '' }}>Approve</option>
                                                    <option value="pending" {{ old('approved', $product->approved ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="rejected" {{ old('approved', $product->approved ?? '') == 'rejected' ? 'selected' : '' }}>Reject</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Shipping Configuration
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <!-- Free Shipping Checkbox -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        Free Shipping
                                                    </span>
                                                    <input id="free_shipping" name="shipping_type" type="checkbox" value="free" 
                                                        {{ $product->shipping_type == 'free' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    
                                        <!-- Flat Rate Checkbox -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        Flat Rate
                                                    </span>
                                                    <input id="flat_rate" name="shipping_type" type="checkbox" value="flat_rate" 
                                                        {{ $product->shipping_type == 'flat_rate' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    
                                        <!-- Flat Rate Input Field (toggle visibility) -->
                                        <div id="flat_rate_input" class="flex items-center gap-2.5 {{ $product->shipping_cost ?? '' }}">
                                            <label for="flat_rate_amount" class="text-sm">Flat Rate Amount</label>
                                            <input id="flat_rate_amount" type="number" class="input @error('shipping_cost') border-red-500 @enderror" 
                                                name="shipping_cost" placeholder="Enter amount" value="{{ old('shipping_cost', $product->shipping_cost) }}" />
                                        </div>
                                    
                                        <!-- Is Product Quantity Multiply Checkbox -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        Is Product Quantity Multiply
                                                    </span>
                                                    <input id="quantity_multiply" name="shipping_type" type="checkbox" value="product_wise" 
                                                        {{ $product->shipping_type == 'product_wise' ? 'checked' : '' }} />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Low Stock Quantity Warning
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Quantity
                                                </label>
                                                <input class="input @error('low_stock_quantity') border-red-500 @enderror" 
                                                    name="low_stock_quantity" 
                                                    type="number" 
                                                    value="{{ old('low_stock_quantity', $product->low_stock_quantity ?? 1) }}" />
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
                                        Stock Visibility State
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <!-- Show Stock Quantity -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">Show Stock Quantity</span>
                                                    <input 
                                                        id="stock_qty" 
                                                        name="stock_visibility_state" 
                                                        type="checkbox" 
                                                        value="quantity" 
                                                        {{ old('stock_visibility_state', $product->stock_visibility_state) === 'quantity' ? 'checked' : '' }}
                                                    />
                                                </label>
                                            </div>
                                        </div>
                                    
                                        <!-- Show Stock With Text Only -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">Show Stock With Text Only</span>
                                                    <input 
                                                        id="stock_text" 
                                                        name="stock_visibility_state" 
                                                        type="checkbox" 
                                                        value="text" 
                                                        {{ old('stock_visibility_state', $product->stock_visibility_state) === 'text' ? 'checked' : '' }}
                                                    />
                                                </label>
                                            </div>
                                        </div>
                                    
                                        <!-- Hide Stock -->
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">Hide Stock</span>
                                                    <input 
                                                        id="stock_hide" 
                                                        name="stock_visibility_state" 
                                                        type="checkbox" 
                                                        value="hide" 
                                                        {{ old('stock_visibility_state', $product->stock_visibility_state) === 'hide' ? 'checked' : '' }}
                                                    />
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Featured
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <div class="grow">
                                                <label class="switch justify-between">
                                                    <span class="switch-label text-sm">
                                                        Status
                                                    </span>
                                                    <!-- Hidden input to send false if checkbox is unchecked -->
                                                    <input type="hidden" name="featured" value="0" />
                                                    <input 
                                                        id="featured" 
                                                        name="featured" 
                                                        type="checkbox" 
                                                        value="1" 
                                                        {{ old('featured', $product->featured ?? false) ? 'checked' : '' }}
                                                    />
                                                </label>
                                            </div>                                        
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card min-w-full">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        Estimate Shipping Time
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Shipping Days
                                                </label>
                                                <input 
                                                    class="input @error('est_shipping_days') border-red-500 @enderror" 
                                                    name="est_shipping_days" 
                                                    type="number" 
                                                    value="{{ old('est_shipping_days', $product->est_shipping_days ?? '') }}" 
                                                />
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
                                        Vat & TAX
                                    </h3>
                                </div>
                                <div class="card-table scrollable-x-auto pb-3">
                                    <div class="card-body grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    VAT
                                                </label>
                                                <input class="input @error('tax') border-red-500 @enderror" name="tax" type="number" value="{{ old('tax', $product->tax ?? '') }}" />
                                            </div>
                                            @error('tax')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    VAT Type
                                                </label>
                                                <select class="input @error('tax_type') border-red-500 @enderror" name="tax_type">
                                                    <option value="">Select One Option</option>
                                                    <option value="amount" {{ old('tax_type', $product->tax_type ?? '') == 'amount' ? 'selected' : '' }}>Flat</option>
                                                    <option value="percent" {{ old('tax_type', $product->tax_type ?? '') == 'percent' ? 'selected' : '' }}>Percent</option>
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
                <!-- end: grid -->
            </div>
        </div>

        <div class="tab-content hidden" id="tab-ar">
            <div class="container-fixed">

                <div class="col-span-2">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="card min-w-full">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Product Information
                                </h3>
                            </div>
                            <div class="card-table scrollable-x-auto pb-3">
                                <div class="card-body grid gap-5">
                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                Product Name (AR) <span class="text-danger">*</span>
                                            </label>
                                            <input class="input @error('name.ar') border-red-500 @enderror" name="name[ar]" type="text" value="{{ old('name.ar', $product->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                        </div>
                                        @error('name.ar')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                Unit (AR)
                                            </label>
                                            <input class="input @error('unit.ar') border-red-500 @enderror" name="unit[ar]" type="text" value="{{ old('unit.ar', $product->translations->where('locale', 'ar')->first()->unit ?? '') }}" placeholder="Unit (e.g. كغ، قطعة)" />
                                        </div>
                                        @error('unit.ar')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
                                    <div class="w-full mt-4">
                                        <div class="flex flex-col gap-1.5">
                                            <label for="tags_ar" class="form-label">Tags (AR)</label>
                                            <input 
                                                id="tags_ar"
                                                class="input w-full @error('tags.ar') border-red-500 @enderror"
                                                name="tags[ar][]"
                                                type="text"
                                                placeholder="Type and hit enter to add a tag"
                                                {{-- 🛠️ Ditto for Arabic: decode JSON to array, then implode --}}
                                                value="{{ implode(',', old(
                                                    'tags.ar',
                                                    json_decode(optional($product->translations->where('locale','ar')->first())->tags ?? '[]', true)
                                                )) }}"
                                            />
                                        </div>
                                        <span class="text-xs">This is used for search. Input words by which customers can find this product.</span>
                                        @error('tags.ar')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
                                </div>                                
                            </div>
                            
                        </div>
                    </div>
                </div>

                <div class="col-span-2">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="card min-w-full">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Product Description
                                </h3>
                            </div>

                            <div class="card-table scrollable-x-auto pb-3">
                                <div class="card-body grid gap-5">
                                    <div class="w-full">
                                        <div class="flex flex-col gap-2.5">
                                            <label class="form-label">
                                                Short Description (AR)
                                            </label>
                                            <textarea id="short_description_ar" name="short_description[ar]"
                                                    class="input @error('short_description.ar') border-red-500 @enderror">
                                                {{ old('short_description.ar', $product->translations->where('locale', 'ar')->first()->short_description ?? '') }}
                                            </textarea>
                                        </div>
                                        @error('short_description.ar')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                
                                    <div class="w-full">
                                        <div class="flex flex-col gap-2.5">
                                            <label class="form-label">
                                                Description (AR)
                                            </label>
                                            <textarea id="description_ar" name="description[ar]"
                                                    class="input @error('description.ar') border-red-500 @enderror">
                                                {{ old('description.ar', $product->translations->where('locale', 'ar')->first()->description ?? '') }}
                                            </textarea>
                                        </div>
                                        @error('description.ar')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                                        
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-2">
                    <div class="grid gap-5 lg:gap-7.5">
                        <div class="card min-w-full">
                            <div class="card-header">
                                <h3 class="card-title">
                                    SEO Meta Tags
                                </h3>
                            </div>
                            <div class="card-table scrollable-x-auto pb-3">
                                <div class="card-body grid gap-5">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <label class="form-label">Meta Title (Arabic)</label>
                                            <input class="input @error('meta_title.ar') border-red-500 @enderror"
                                                   name="meta_title[ar]"
                                                   type="text"
                                                   value="{{ old('meta_title.ar', $product->translations->where('locale', 'ar')->first()->meta_title ?? '') }}" />
                                            @error('meta_title.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
    
                                        <div class="w-full">
                                            <label class="form-label">Meta Description (Arabic)</label>
                                            <textarea class="textarea @error('meta_description.ar') border-red-500 @enderror"
                                                      name="meta_description[ar]" rows="4">{{ old('meta_description.ar', $product->translations->where('locale', 'ar')->first()->meta_description ?? '') }}</textarea>
                                            @error('meta_description.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    
                    </div>
                </div> 
            </div>
        </div>

        <div class="flex justify-end pt-2.5">
            <button type="submit" class="btn btn-primary">
                Save Changes
            </button>
        </div>
    </form>
    <!-- End of Container -->
</main>
@php
    $existingVariations = collect($product->variants)->flatMap(function ($variant) {
        return collect($variant['attributes'])->map(function ($attr) use ($variant) {
            return [
                'id' => $variant['id'] ?? null,
                'attribute' => $attr['attribute'] ?? null,
                'attribute_id' => $attr['attribute_id'] ?? null,
                'attribute_value_id' => $attr['attribute_value_id'] ?? null,
                'value' => $attr['value'] ?? '',
                'price' => $variant['price'] ?? '',
                'sku' => $variant['sku'] ?? '',
                'image' => $variant['variant_image'] ?? '',
            ];
        });
    });
@endphp

@endsection

@push('scripts')
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
    const attributeValues = @json($attributeValues);
    const attributeSelect = document.getElementById('attribute_id');
    const valueWrapper = document.getElementById('attribute-values-wrapper');
    const variationFields = document.getElementById('variation-fields');
    const tpl = document.getElementById('variation-template').innerHTML;
    const existingVariations = @json($existingVariations);
    
    // Group existing variations by variant ID
    function groupByVariant(variations) {
        const map = new Map();
    
        variations.forEach(v => {
            if (!map.has(v.id)) {
                map.set(v.id, {
                    id: v.id,
                    price: v.price,
                    sku: v.sku,
                    image: v.image,
                    attributes: [],
                });
            }
            map.get(v.id).attributes.push({
                attribute_id: v.attribute_id,
                value: v.value,
                label: v.label ?? `${v.attribute}:${v.value}`,
            });
        });
    
        return Array.from(map.values());
    }
    
    // Build value selectors from selected attributes
    function buildValueSelectors(attrs) {
        valueWrapper.innerHTML = '';
        variationFields.innerHTML = '';
    
        if (!attrs.length) {
            valueWrapper.classList.add('hidden');
            return;
        }
    
        valueWrapper.classList.remove('hidden');
    
        attrs.forEach(id => {

            
            
            const label = document.querySelector(`#attribute_id option[value="${id}"]`)?.dataset.name || `Attribute ${id}`;
            const vals = attributeValues[id] || [];
            
            const div = document.createElement('div');
            div.className = 'mb-2';
    
            const select = document.createElement('select');
            select.className = 'input w-full mb-2 attribute_value_id choices__input';
            select.multiple = true;
            select.dataset.id = id;
            select.dataset.label = label;
    
            const dash = document.createElement('option');
            dash.value = '';
            dash.text = `-- Select ${label} --`;
            select.appendChild(dash);
    
            vals.forEach(v => {
                const option = document.createElement('option');
                option.value = v;
                option.text = v;
    
                const isSelected = existingVariations.some(variation => variation.value == v);
                if (isSelected) {
                    option.selected = true;
                }
    
                select.appendChild(option);
            });
    
            select.onchange = renderVariations;
            div.appendChild(select);
            valueWrapper.appendChild(div);
    
            new Choices(select, {
                searchEnabled: true,
                itemSelectText: '',
                shouldSort: false,
                removeItemButton: true,
                duplicateItemsAllowed: false,
            });
        });
    
        renderExistingVariations();
    }
    
    // Render variations based on user selection
    function renderVariations() {
        const selects = Array.from(valueWrapper.querySelectorAll('select'));
        
        const grids = selects.map(sel =>
            Array.from(sel.selectedOptions)
                .filter(o => o.value)
                .map(o => ({
                    attrId: sel.dataset.id,
                    // attrValId: sel.dataset.valId,
                    label: sel.dataset.label,
                    value: o.value
                }))                
        );
    
        if (grids.some(arr => arr.length === 0)) {
            variationFields.innerHTML = '';
            return;
        }
    
        const combos = grids.reduce((acc, arr) =>
            acc.flatMap(prev =>
                arr.map(item => [...prev, item])
            ), [[]]
        );
    
        variationFields.innerHTML = '';

        combos.forEach(chosen => {
            const attrVal = chosen.map(c => `${c.label}:${c.value}`).join(' / ');
            const ids = chosen.map(c => c.attrId).join(',');
            const vals = chosen.map(c => c.value).join(' / ');
            const nameKey = `variants[${Date.now()}_${Math.random().toString(36).substring(2, 7)}]`;

            
            let attrValueIds = chosen.map(c => c.value).join(',');

            let row = tpl
                .replace(/__NAME__/g, nameKey)
                .replace(/__ID__/g, ids)
                .replace(/__ATTRIBUTE_VALUE__/g, attrVal)
                .replace(/__VALUE__/g, vals)
                .replace(/__ATTRIBUTE_VALUE_ID__/g, attrValueIds);


            variationFields.insertAdjacentHTML('beforeend', row);
        });


    }
    
    // Render backend variations
    function renderExistingVariations() {
        const grouped = groupByVariant(existingVariations);
    
        grouped.forEach(variation => {
            const nameKey = `variants[${variation.id}]`;
            
            console.log();
            
            const attrVal = variation.attributes.map(a => `${a.label}`).join(' / ');
            const attrIds = variation.attributes.map(a => a.attribute_id).join(',');
            const attrValues = variation.attributes.map(a => a.value).join(' / ');

            
            let row = tpl
                .replace(/__NAME__/g, nameKey)
                .replace(/__ID__/g, attrIds)
                .replace(/__VALUE__/g, attrValues)
                .replace(/__ATTRIBUTE_VALUE__/g, attrVal);
    
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = row;
    
            const priceInput = tempDiv.querySelector(`[name="${nameKey}[price]"]`);
            const skuInput = tempDiv.querySelector(`[name="${nameKey}[sku]"]`);
            if (priceInput) priceInput.value = variation.price ?? '';
            if (skuInput) skuInput.value = variation.sku ?? '';
    
            // For media picker: trigger the value + custom "change" event if needed
            const imageInput = tempDiv.querySelector(`[name="${nameKey}[variant_image]"]`);
            if (imageInput && variation.image) {
                imageInput.value = variation.image;
                imageInput.dispatchEvent(new Event('change')); // Required for media.single UI
            }
    
            variationFields.appendChild(tempDiv.firstElementChild);
        });
    }
    
    // Init on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        const preSelected = Array.from(attributeSelect.selectedOptions).map(o => o.value);
        if (preSelected.length) buildValueSelectors(preSelected);
    });
    
    attributeSelect.onchange = () => {
        const selectedAttrs = Array.from(attributeSelect.selectedOptions).map(o => o.value);
        buildValueSelectors(selectedAttrs);
    };
</script>
    











<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    ClassicEditor
        .create(document.querySelector('#short_description'))
        .catch(error => {
            console.error(error);
        });
    ClassicEditor
        .create(document.querySelector('#description'))
        .catch(error => {
            console.error(error);
        });

        ClassicEditor
        .create(document.querySelector('#short_description_ar'))
        .catch(error => {
            console.error(error);
        });
    ClassicEditor
        .create(document.querySelector('#description_ar'))
        .catch(error => {
            console.error(error);
        });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('input[name="stock_visibility_state"]');

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
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
    document.addEventListener('DOMContentLoaded', function () {
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
        freeShippingCheckbox.addEventListener('change', function () {
            if (this.checked) {
                uncheckOtherCheckboxes('free_shipping');
            }
        });

        // Event listener for flat rate checkbox
        flatRateCheckbox.addEventListener('change', function () {
            if (this.checked) {
                uncheckOtherCheckboxes('flat_rate');
                flatRateInput.classList.remove('hidden');
            } else {
                flatRateInput.classList.add('hidden');
            }
        });

        // Event listener for quantity multiply checkbox
        quantityMultiplyCheckbox.addEventListener('change', function () {
            if (this.checked) {
                uncheckOtherCheckboxes('quantity_multiply');
            }
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
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

    new Choices('#tags_ar', {
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
