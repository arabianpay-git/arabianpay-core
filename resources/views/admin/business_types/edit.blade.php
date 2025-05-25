@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">Edit Business Type</h3>
                    </div>

                    <!-- Language Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex gap-4" id="langTabs">
                            <button class="tab-btn active" data-tab="en">English</button>
                            <button class="tab-btn" data-tab="ar">Arabic</button>
                        </nav>
                    </div>

                    @if ($errors->any())
                <div class="grid gap-5 lg:gap-7.5 mb-5">
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

                    <!-- Update Form -->
                    <form action="{{ route('business-types.update', $businessType->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-5">
                            
                            <div class="tab-content" id="tab-en">
                                <div class="grid gap-5">
                                    
                                    <div class="w-full">
                                        <label class="form-label">Business Type</label>
                                        <input class="input @error('name.en') border-red-500 @enderror"
                                               name="name[en]"
                                               type="text"
                                               value="{{ old('name.en', $businessType->name ?? '') }}"
                                               required />
                                        @error('name.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <label class="form-label">Risk Level <span class="text-danger">*</span></label>
                                        <select class="input @error('risk_level') border-red-500 @enderror" name="risk_level" required>
                                            <option value="low" {{ old('risk_level', $businessType->risk_level) == 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ old('risk_level', $businessType->risk_level) == 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ old('risk_level', $businessType->risk_level) == 'high' ? 'selected' : '' }}>High</option>
                                        </select>
                                        @error('risk_level')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
        
                                    <div class="w-full">
                                        <label class="form-label">Featured</label>
                                        <select class="input @error('featured') border-red-500 @enderror" name="featured">
                                            <option value="true" {{ old('featured', $businessType->featured) == true ? 'selected' : '' }}>Yes</option>
                                            <option value="false" {{ old('featured', $businessType->featured) == false ? 'selected' : '' }}>No</option>
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
                                               value="{{ old('order_level', $businessType->order_level) }}" />
                                        @error('order_level')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
        
                                    @include('media.single', [
                                        'name' => 'icon',
                                        'label' => 'Business Type Icon',
                                        'required' => false,
                                        'value' => old('icon', $businessType->icon)
                                    ])
        
                                    @include('media.single', [
                                        'name' => 'banner',
                                        'label' => 'Business Type Banner',
                                        'required' => false,
                                        'value' => old('banner', $businessType->banner)
                                    ])
                                </div>
                            </div>

                            <div class="tab-content hidden" id="tab-ar">
                                <div class="grid gap-5">
                                    <div class="w-full">
                                        <label class="form-label">Business Type (Arabic)</label>
                                        <input class="input @error('name.ar') border-red-500 @enderror"
                                               name="name[ar]"
                                               type="text"
                                               value="{{ old('name.ar', $businessType->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                        @error('name.ar')
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
