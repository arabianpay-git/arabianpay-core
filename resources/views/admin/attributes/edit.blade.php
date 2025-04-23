@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">Edit Attribute</h3>
                    </div>

                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex gap-4" id="langTabs">
                            <button class="tab-btn active" data-tab="en">English</button>
                            <button class="tab-btn" data-tab="ar">Arabic</button>
                        </nav>
                    </div>

                    <!-- Update Form -->
                    <form action="{{ route('attributes.update', $attribute->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-5">
                            
                            <div class="tab-content" id="tab-en">
                                <div class="grid gap-5">
                                    
                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                Attribute
                                            </label>
                                            <input class="input @error('name.en') border-red-500 @enderror"
                                                    name="name[en]"
                                                    type="text"
                                                    value="{{ old('name.en', $attribute->name) }}" required />
                                        </div>
                                        @error('name[en]')
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
                                                Attribute
                                            </label>
                                            <input class="input @error('name.ar') border-red-500 @enderror"
                                                name="name[ar]"
                                                type="text"
                                                value="{{ old('name.ar', $attribute->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                        </div>
                                        @error('name[en]')
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
