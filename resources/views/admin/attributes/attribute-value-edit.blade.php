@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Edit Attribute Value') }}</h3>
                        </div>

                        <!-- Language Tabs -->
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex gap-4" id="langTabs">
                                <button class="tab-btn active" data-tab="en">{{ translate('English') }}</button>
                                <button class="tab-btn" data-tab="ar">{{ translate('Arabic') }}</button>
                            </nav>
                        </div>

                        <!-- Update Form -->
                        <form action="{{ route('attribute-values.update', $attributeValue->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="p-5">
                                @php
                                    $isColor = strtolower($attributeValue->attribute->name) === 'color';
                                    $valueEn = old('value.en', $attributeValue->value);
                                    $valueAr = old(
                                        'value.ar',
                                        $attributeValue->translations->where('locale', 'ar')->first()->value ?? '',
                                    );
                                    $colorCode = old('color_code', $attributeValue->color_code ?? '#1976D2');
                                @endphp

                                <div class="tab-content" id="tab-en">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Attribute Value') }}
                                                </label>
                                                <input class="input @error('value.en') border-red-500 @enderror"
                                                    name="value[en]" type="text" value="{{ $valueEn }}" required />
                                            </div>
                                            @error('value.en')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        @if ($isColor)
                                            <div class="w-full">
                                                <label
                                                    class="form-label flex items-center gap-1 mb-2">{{ translate('Pick Color') }}</label>
                                                <div class="flex items-center gap-2 relative">
                                                    <!-- Color swatch -->
                                                    <div id="color-swatch" class="w-10 h-10 border rounded cursor-pointer"
                                                        style="background-color: {{ $colorCode }};"></div>

                                                    <!-- Hidden color picker -->
                                                    <input type="color" id="color-picker" value="{{ $colorCode }}"
                                                        class="absolute top-0 left-0 w-10 h-10 opacity-0 cursor-pointer appearance-none border-none p-0 m-0" />

                                                    <!-- Hex input -->
                                                    <input type="text" id="color-hex" name="color_code"
                                                        value="{{ $colorCode }}" placeholder="#1976D2"
                                                        class="input w-28 h-10 text-sm px-2 rounded border" />
                                                </div>
                                                @error('color_code')
                                                    <span class="text-danger text-sm">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="tab-content hidden" id="tab-ar">
                                    <div class="grid gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    {{ translate('Attribute Value') }}
                                                </label>
                                                <input class="input @error('value.ar') border-red-500 @enderror"
                                                    name="value[ar]" type="text" value="{{ $valueAr }}" />
                                            </div>
                                            @error('value.ar')
                                                <span class="text-danger text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between pt-2.5">
                                    <a href="{{ route('attributes.editAttributeValue', $attributeValue->attribute_id) }}"
                                        class="btn btn-danger">{{ translate('Go Back') }}</a>
                                    <button class="btn btn-primary">{{ translate('Save Changes') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if ($isColor)
        <script>
            const swatch = document.getElementById('color-swatch');
            const picker = document.getElementById('color-picker');
            const hexInput = document.getElementById('color-hex');

            swatch.addEventListener('click', () => picker.click());

            picker.addEventListener('input', () => {
                swatch.style.backgroundColor = picker.value;
                hexInput.value = picker.value;
            });

            hexInput.addEventListener('input', () => {
                const val = hexInput.value;
                if (/^#([0-9A-Fa-f]{6})$/.test(val)) {
                    picker.value = val;
                    swatch.style.backgroundColor = val;
                }
            });
        </script>
    @endif
@endsection
