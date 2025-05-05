@extends('layouts.base') @section('content')

<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">
                            Add New Package
                        </h3>
                    </div>

                    <form action="{{ route('packages.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body grid gap-5">
                    
                            <!-- Name Field -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Name <span class="text-danger">*</span>
                                    </label>
                                    <input class="input @error('name') border-red-500 @enderror" name="name" type="text" value="{{ old('name') }}" required />
                                </div>
                                @error('name')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                    
                            <div class="flex gap-4">
                                <!-- Min Score Field -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Min Score <span class="text-danger">*</span>
                                        </label>
                                        <input class="input @error('min_score') border-red-500 @enderror" name="min_score" type="number" value="{{ old('min_score') }}" required />
                                    </div>
                                    @error('min_score')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                        
                                <!-- Max Score Field -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Max Score <span class="text-danger">*</span>
                                        </label>
                                        <input class="input @error('max_score') border-red-500 @enderror" name="max_score" type="number" value="{{ old('max_score') }}" required />
                                    </div>
                                    @error('max_score')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            @include('media.single', [
                                'name' => 'logo',
                                'label' => 'Package Logo',
                                'required' => false,
                                'value' => old('logo')
                            ])
                    
                          
                            <!-- Submit Button -->
                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">
                                    Save Package
                                </button>
                            </div>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection

