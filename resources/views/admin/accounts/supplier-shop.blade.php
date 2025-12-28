@extends('layouts.base')

@section('content')
    <main class="w-full mx-auto p-6 space-y-6 bg-slate-50">

        @include('admin.accounts.includes.supplier-profile-header')

        @include('admin.accounts.includes.supplier-nav')

        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 space-y-6">
                <div class="flex grow gap-5 lg:gap-7.5">
                    <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                        <div class="card pb-2.5">
                            <div class="card-header" id="basic_settings">
                                <h3 class="card-title">
                                    {{ translate('Shop Settings') }}
                                </h3>
                            </div>

                            <form action="{{ route('supplierShopSubmit') }}" method="POST">
                                @csrf
                                <div class="card-body grid gap-5">
                                    <input type="hidden" name="user_id" value="{{ $merchant->user_id }}">
                                    <!-- Shop Name -->
                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                {{ translate('Shop Name') }} <span class="text-danger">*</span>
                                            </label>
                                            <input class="input @error('name') border-red-500 @enderror" name="name"
                                                type="text" value="{{ old('name', $supplierShop->name ?? '') }}"
                                                required />
                                        </div>
                                        @error('name')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    @include('media.single', [
                                        'name' => 'logo',
                                        'label' => __('Shop Logo'),
                                        'required' => false,
                                        'value' => old('logo', $supplierShop->logo ?? ''),
                                    ])

                                    @include('media.multiple', [
                                        'name' => 'sliders',
                                        'label' => __('Sliders'),
                                        'required' => false,
                                        'value' => old('sliders', $supplierShop->sliders ?? []),
                                        'multiple_select' => true,
                                    ])

                                    @include('media.single', [
                                        'name' => 'banner',
                                        'label' => __('Shop Banner'),
                                        'required' => false,
                                        'value' => old('banner', $supplierShop->banner ?? ''),
                                    ])


                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                {{ translate('Phone Number') }} <span class="text-danger">*</span>
                                            </label>
                                            <input class="input @error('phone_number') border-red-500 @enderror"
                                                name="phone_number" type="text"
                                                value="{{ old('phone_number', $supplierShop->phone_number ?? '') }}"
                                                required />
                                        </div>
                                        @error('phone_number')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                {{ translate('Address') }} <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="textarea @error('address') border-red-500 @enderror" name="address" rows="3" required>{{ old('address', $supplierShop->address ?? '') }}</textarea>
                                        </div>
                                        @error('address')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

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
        </div>
    </main>
@endsection
