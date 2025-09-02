@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Brand') }}</h3>
                        </div>

                        <!-- Language Tabs -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex gap-4" id="langTabs">
                                <button class="tab-btn active" data-tab="en">{{ translate('English') }}</button>
                                <button class="tab-btn" data-tab="ar">{{ translate('Arabic') }}</button>
                            </nav>
                        </div>

                        <!-- Update Form -->
                        <form action="{{ route('brands.update', $brand->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="p-5">

                                <div class="tab-content" id="tab-en">
                                    <div class="grid gap-5">

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Brand Name') }}</label>
                                            <input class="input @error('name.en') border-red-500 @enderror" name="name[en]"
                                                type="text" value="{{ old('name.en', $brand->name ?? '') }}" required />
                                            @error('name.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Featured') }}</label>
                                            <select class="input @error('featured') border-red-500 @enderror"
                                                name="featured">
                                                <option value="true"
                                                    {{ old('featured', $brand->featured) == true ? 'selected' : '' }}>
                                                    {{ translate('Yes') }}</option>
                                                <option value="false"
                                                    {{ old('featured', $brand->featured) == false ? 'selected' : '' }}>
                                                    {{ translate('No') }}</option>
                                            </select>
                                            @error('featured')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Order Level') }}</label> <span
                                                class="text-danger">*</span>
                                            <input class="input @error('order_level') border-red-500 @enderror"
                                                name="order_level" type="number"
                                                value="{{ old('order_level', $brand->order_level) }}" />
                                            @error('order_level')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        @include('media.single', [
                                            'name' => 'logo',
                                            'label' => translate('Brand Logo'),
                                            'required' => true,
                                            'value' => old('icon', $brand->logo),
                                        ])

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Title (English)') }}</label>
                                            <input class="input @error('meta_title.en') border-red-500 @enderror"
                                                name="meta_title[en]" type="text"
                                                value="{{ old('meta_title.en', $brand->translations->where('locale', 'en')->first()->meta_title ?? '') }}" />
                                            @error('meta_title.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Description (English)') }}</label>
                                            <textarea class="textarea @error('meta_description.en') border-red-500 @enderror" name="meta_description[en]"
                                                rows="4">{{ old('meta_description.en', $brand->translations->where('locale', 'en')->first()->meta_description ?? '') }}</textarea>
                                            @error('meta_description.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content hidden" id="tab-ar">
                                    <div class="grid gap-5">

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Brand Name (Arabic)') }}</label>
                                            <input class="input @error('name.ar') border-red-500 @enderror" name="name[ar]"
                                                type="text"
                                                value="{{ old('name.ar', $brand->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                            @error('name.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Title (Arabic)') }}</label>
                                            <input class="input @error('meta_title.ar') border-red-500 @enderror"
                                                name="meta_title[ar]" type="text"
                                                value="{{ old('meta_title.ar', $brand->translations->where('locale', 'ar')->first()->meta_title ?? '') }}" />
                                            @error('meta_title.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="w-full">
                                            <label class="form-label">{{ translate('Meta Description (Arabic)') }}</label>
                                            <textarea class="textarea @error('meta_description.ar') border-red-500 @enderror" name="meta_description[ar]"
                                                rows="4">{{ old('meta_description.ar', $brand->translations->where('locale', 'ar')->first()->meta_description ?? '') }}</textarea>
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
