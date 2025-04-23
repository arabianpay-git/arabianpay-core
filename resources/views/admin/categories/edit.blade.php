@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">Edit Category</h3>
                    </div>

                    <!-- Language Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex gap-4" id="langTabs">
                            <button class="tab-btn active" data-tab="en">English</button>
                            <button class="tab-btn" data-tab="ar">Arabic</button>
                        </nav>
                    </div>

                    <!-- Update Form -->
                    <form action="{{ route('categories.update', $category->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-5">
                            
                            <div class="tab-content" id="tab-en">
                                <div class="grid gap-5">
                                    
                                    <div class="w-full">
                                        <label class="form-label">Category Name</label>
                                        <input class="input @error('name.en') border-red-500 @enderror"
                                               name="name[en]"
                                               type="text"
                                               value="{{ old('name.en', $category->name ?? '') }}"
                                               required />
                                        @error('name.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label">Parent Category</label>
                                        <select name="parent_id" class="input @error('parent_id') border-red-500 @enderror">
                                            <option value="">Select Parent Category</option>
                                            @foreach ($categories as $categoryOption)
                                                <option value="{{ $categoryOption->id }}" {{ old('parent_id', $category->parent_id) == $categoryOption->id ? 'selected' : '' }}>
                                                    {{ $categoryOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('parent_id')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
        
                                    <div class="w-full">
                                        <label class="form-label">Featured</label>
                                        <select class="input @error('featured') border-red-500 @enderror" name="featured">
                                            <option value="true" {{ old('featured', $category->featured) == true ? 'selected' : '' }}>Yes</option>
                                            <option value="false" {{ old('featured', $category->featured) == false ? 'selected' : '' }}>No</option>
                                        </select>
                                        @error('featured')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
        
                                    <div class="w-full">
                                        <label class="form-label">Order Level</label>
                                        <input class="input @error('order_level') border-red-500 @enderror"
                                               name="order_level"
                                               type="number"
                                               value="{{ old('order_level', $category->order_level) }}" />
                                        @error('order_level')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
        
                                    @include('media.single', [
                                        'name' => 'icon',
                                        'label' => 'Category Icon',
                                        'required' => false,
                                        'value' => old('icon', $category->icon)
                                    ])
        
                                    @include('media.single', [
                                        'name' => 'banner',
                                        'label' => 'Category Banner',
                                        'required' => false,
                                        'value' => old('banner', $category->banner)
                                    ])
                                    
                                    <div class="w-full">
                                        <label class="form-label">Meta Title (English)</label>
                                        <input class="input @error('meta_title.en') border-red-500 @enderror"
                                               name="meta_title[en]"
                                               type="text"
                                               value="{{ old('meta_title.en', $category->translations->where('locale', 'en')->first()->meta_title ?? '') }}" />
                                        @error('meta_title.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label">Meta Description (English)</label>
                                        <textarea class="textarea @error('meta_description.en') border-red-500 @enderror"
                                                  name="meta_description[en]" rows="4">{{ old('meta_description.en', $category->translations->where('locale', 'en')->first()->meta_description ?? '') }}</textarea>
                                        @error('meta_description.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="tab-content hidden" id="tab-ar">
                                <div class="grid gap-5">
                                    <div class="w-full">
                                        <label class="form-label">Category Name (Arabic)</label>
                                        <input class="input @error('name.ar') border-red-500 @enderror"
                                               name="name[ar]"
                                               type="text"
                                               value="{{ old('name.ar', $category->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                        @error('name.ar')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label">Meta Title (Arabic)</label>
                                        <input class="input @error('meta_title.ar') border-red-500 @enderror"
                                               name="meta_title[ar]"
                                               type="text"
                                               value="{{ old('meta_title.ar', $category->translations->where('locale', 'ar')->first()->meta_title ?? '') }}" />
                                        @error('meta_title.ar')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label">Meta Description (Arabic)</label>
                                        <textarea class="textarea @error('meta_description.ar') border-red-500 @enderror"
                                                  name="meta_description[ar]" rows="4">{{ old('meta_description.ar', $category->translations->where('locale', 'ar')->first()->meta_description ?? '') }}</textarea>
                                        @error('meta_description.ar')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            

                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
