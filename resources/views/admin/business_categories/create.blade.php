@extends('layouts.base') @section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                {{ translate('Add New Business Category') }}
                            </h3>
                        </div>

                        <form action="{{ route('business-categories.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Business Type') }}
                                        </label>
                                        <select class="select" name="business_type_id">
                                            <option value="">{{ translate('Select One Option') }}</option>
                                            @foreach ($businessTypes as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('business_type_id')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Business Category') }} <span class="text-danger">*</span>
                                        </label>
                                        <input class="input @error('name') border-red-500 @enderror" name="name"
                                            type="text" value="{{ old('name') }}" required />
                                    </div>
                                    @error('name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Risk') }} <span class="text-danger">*</span>
                                        </label>
                                        <input class="input @error('risk') border-red-500 @enderror" name="risk"
                                            type="text" value="{{ old('risk') }}" required />
                                    </div>
                                    @error('risk')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                    <small class="text-gray">Hint: (1–5; 1 = very low risk, 5 = very high risk)</small>
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
                                                    {{ translate('Yes') }}
                                                </option>
                                                <option value="false" {{ old('featured') == 'false' ? 'selected' : '' }}>
                                                    {{ translate('No') }}
                                                </option>
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
                                    'label' => translate('Business Category Icon'),
                                    'required' => false,
                                    'value' => old('icon'),
                                ])

                                @include('media.single', [
                                    'name' => 'banner',
                                    'label' => translate('Business Category Banner'),
                                    'required' => false,
                                    'value' => old('banner'),
                                ])

                                <!-- Submit Button -->
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
        <!-- End of Container -->
    </main>
@endsection
