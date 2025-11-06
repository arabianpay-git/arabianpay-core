@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>
        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            <!-- Container -->
            @include('admin.accounts.includes.profile')
            <!-- End of Container -->
        </div>
        <!-- Container -->
        @include('admin.accounts.includes.header')

        <div class="container-fixed">
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
                                            type="text" value="{{ old('name', $supplierShop->name ?? '') }}" required />
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
                                            value="{{ old('phone_number', $supplierShop->phone_number ?? '') }}" required />
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
    </main>
@endsection
