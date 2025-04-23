@extends('layouts.base') @section('content')

<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">
                            Add New Business Type
                        </h3>
                    </div>

                    <form action="{{ route('business-types.store') }}" method="POST">
                        @csrf
                        <div class="card-body grid gap-5">
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Business Type <span class="text-danger">*</span>
                                    </label>
                                    <input class="input @error('name') border-red-500 @enderror" name="name" type="text" value="{{ old('name') }}" required />
                                </div>
                                @error('name')
                                <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex gap-2.5">
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Featured
                                        </label>
                                        <select class="input @error('featured') border-red-500 @enderror" name="featured">
                                            <option value="">Select One Option</option>
                                            <option value="true" {{ old('featured') == 'true' ? 'selected' : '' }}>Yes</option>
                                            <option value="false" {{ old('featured') == 'false' ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    @error('featured')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                                
    
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Order Level
                                        </label>
                                        <input class="input @error('order_level') border-red-500 @enderror" name="order_level" type="number" value="{{ old('order_level') }}" />
                                    </div>
                                    @error('order_level')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Icon Picker -->

                            @include('media.single', [
                                'name' => 'icon',
                                'label' => 'Business Type Icon',
                                'required' => false,
                                'value' => old('icon')
                            ])

                            @include('media.single', [
                                'name' => 'banner',
                                'label' => 'Business Type Banner',
                                'required' => false,
                                'value' => old('banner')
                            ])

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">
                                    Save Changes
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

