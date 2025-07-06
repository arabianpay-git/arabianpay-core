@extends('layouts.base')
@section('content')
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <style>
            /* Add your custom styles */
            .choices__inner {
                min-height: 2.4rem !important;
                height: 2.4rem !important;
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
                border-radius: 0.375rem;
            }

            .choices__input {
                height: auto !important;
                margin: 0 !important;
            }

            .choices__list--multiple .choices__item {
                border-radius: 0.375rem;
                font-size: 0.875rem;
                padding: 0 7px;
            }

            .choices__list {
                position: relative !important;
                z-index: 9999 !important;
            }

            .choices {
                position: relative !important;
            }
        </style>
    @endpush

    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                {{ translate('Add New Coupon') }}
                            </h3>
                        </div>

                        <form action="{{ route('coupons.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">
                                <!-- Merchant Select -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Merchants') }}
                                        </label>
                                        <select class="input @error('user_id') border-red-500 @enderror" name="user_id"
                                            id="user_id">
                                            <option value="">{{ translate('Select Merchant') }}</option>
                                            @foreach ($merchants as $merchant)
                                                <option value="{{ $merchant->id }}"
                                                    {{ old('user_id') == $merchant->id ? 'selected' : '' }}>
                                                    {{ $merchant->business_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('user_id')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Coupon Code -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Code') }} <span class="text-danger">*</span>
                                        </label>
                                        <input class="input @error('code') border-red-500 @enderror" name="code"
                                            type="text" value="{{ old('code') }}" required />
                                    </div>
                                    @error('code')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Type Select -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label
                                            class="form-label flex items-center gap-1 max-w-56">{{ translate('Coupon Type') }}</label>
                                        <select class="input @error('type') border-red-500 @enderror" name="type"
                                            id="coupon_type" required>
                                            <option value="total_order"
                                                {{ old('type') == 'total_order' ? 'selected' : '' }}>
                                                {{ translate('Total Order') }}</option>
                                            <option value="product_base"
                                                {{ old('type') == 'product_base' ? 'selected' : '' }}>
                                                {{ translate('Product Base') }}</option>
                                        </select>
                                    </div>
                                    @error('type')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full" id="product_select_section" style="display: none;">
                                    <label class="form-label block mb-1">{{ translate('Select Products') }}</label>
                                    <select id="products" class="input w-full" name="products[]" multiple>
                                        <!-- Dynamic products will be loaded here -->
                                    </select>
                                </div>

                                <div class="w-full" id="total_order_section" style="display: none;">
                                    <div class="flex flex-col gap-4">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label
                                                class="form-label flex items-center gap-1 max-w-56">{{ translate('Minimum Order Value') }}</label>
                                            <input type="number" class="input" name="min_order_amount" step="0.01"
                                                value="{{ old('min_order_amount') }}">
                                        </div>

                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label
                                                class="form-label flex items-center gap-1 max-w-56">{{ translate('Maximum Discount') }}</label>
                                            <input type="number" class="input" name="max_discount_amount" step="0.01"
                                                value="{{ old('max_discount_amount') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-5">
                                    <input class="input" name="discount" type="number"
                                        placeholder="{{ translate('Discount') }}" value="{{ old('discount') }}"
                                        required />
                                    <select class="input" name="discount_type" required>
                                        <option value="percent" {{ old('discount_type') == 'percent' ? 'selected' : '' }}>
                                            {{ translate('Percent') }}</option>
                                        <option value="amount" {{ old('discount_type') == 'amount' ? 'selected' : '' }}>
                                            {{ translate('Amount') }}</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-5">
                                    <input class="input" name="start_date" type="date"
                                        placeholder="{{ translate('Discount Start Date') }}"
                                        value="{{ old('start_date') }}">
                                    <input class="input" name="end_date" type="date"
                                        placeholder="{{ translate('Discount End Date') }}" value="{{ old('end_date') }}">
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
        <!-- End of Container -->
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js"></script>

    <script>
        flatpickr("input[name='start_date']", {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
        });

        flatpickr("input[name='end_date']", {
            altInput: true,
            altFormat: "F j, Y",
            dateFormat: "Y-m-d",
        });

        let productChoices = null;

        function toggleCouponType() {
            const type = document.getElementById('coupon_type').value;
            const productSection = document.getElementById('product_select_section');
            const totalOrderSection = document.getElementById('total_order_section');

            if (type === 'product_base') {
                productSection.style.display = 'block';
                totalOrderSection.style.display = 'none';

                // Only initialize if not already done
                if (!productChoices) {
                    productChoices = new Choices('#products', {
                        searchEnabled: true,
                        itemSelectText: '',
                        shouldSort: false,
                        removeItemButton: true,
                        duplicateItemsAllowed: false,
                    });
                }
            } else {
                productSection.style.display = 'none';
                totalOrderSection.style.display = 'block';
            }
        }

        document.getElementById('coupon_type').addEventListener('change', toggleCouponType);
        window.addEventListener('DOMContentLoaded', toggleCouponType);

        // AJAX to load products based on user_id
        document.getElementById('user_id').addEventListener('change', function() {
            const userId = this.value;
            const productSelect = document.getElementById('products');

            if (userId) {
                productSelect.innerHTML = '<option>Loading products...</option>';

                fetch(`/admin/products/${userId}`) // This will call the `show` method in ProductController
                    .then(response => response.json())
                    .then(data => {
                        productSelect.innerHTML = '';
                        data.products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.name;
                            productSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error(error);
                        productSelect.innerHTML = '<option>Error loading products</option>';
                    });
            } else {
                productSelect.innerHTML = '';
            }
        });
    </script>
@endpush
