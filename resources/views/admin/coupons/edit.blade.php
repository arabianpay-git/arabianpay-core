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
                            Edit Coupon
                        </h3>
                    </div>

                    <form action="{{ route('coupons.update', $coupon->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body grid gap-5">
                            <!-- Merchant Select -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Merchants
                                    </label>
                                    <select class="input @error('user_id') border-red-500 @enderror" name="user_id" id="user_id">
                                        <option value="">Select Merchant</option>
                                        @foreach($merchants as $merchant)
                                            <option value="{{ $merchant->id }}" {{ old('user_id', $coupon->user_id) == $merchant->id ? 'selected' : '' }}>
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
                                        Code <span class="text-danger">*</span>
                                    </label>
                                    <input class="input @error('code') border-red-500 @enderror" name="code" type="text" value="{{ old('code', $coupon->code) }}" required />
                                </div>
                                @error('code')
                                <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="w-full">
                                <label class="form-label">Coupon Type</label>
                                <select class="input @error('type') border-red-500 @enderror" name="type" id="coupon_type" required>
                                    <option value="total_order" {{ old('type', $coupon->type) == 'total_order' ? 'selected' : '' }}>Total Order</option>
                                    <option value="product_base" {{ old('type', $coupon->type) == 'product_base' ? 'selected' : '' }}>Product Base</option>
                                </select>
                                @error('type')<span class="text-danger text-sm">{{ $message }}</span>@enderror
                            </div>
                    
                            <div class="w-full" id="product_select_section" style="display: {{ $coupon->type === 'product_base' ? 'block' : 'none' }}">
                                <label class="form-label block mb-1">Select Products</label>
                                <select id="products" class="input w-full" name="products[]" multiple>
                                    @foreach($allProducts as $product)
                                        <option value="{{ $product->id }}" 
                                            {{ in_array($product->id, old('products', $products)) ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('products')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>                            
                    
                            <div class="w-full" id="total_order_section" style="display: {{ $coupon->type === 'total_order' ? 'block' : 'none' }}">
                                <div class="flex flex-col gap-4">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">Minimum Order Value</label>
                                        <input type="number" class="input" name="min_order_amount" step="0.01" value="{{ old('min_order_amount', $coupon->details['min_order_amount'] ?? '') }}">
                                    </div>
                            
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">Maximum Discount</label>
                                        <input type="number" class="input" name="max_discount_amount" step="0.01" value="{{ old('max_discount_amount', $coupon->details['max_discount_amount'] ?? '') }}">
                                    </div>
                                </div>
                            </div>                            
                    
                            <div class="grid grid-cols-2 gap-5">
                                <input class="input" name="discount" type="number" placeholder="Discount" value="{{ old('discount', $coupon->discount) }}" required />
                                <select class="input" name="discount_type" required>
                                    <option value="percent" {{ old('discount_type', $coupon->discount_type) == 'percent' ? 'selected' : '' }}>Percent</option>
                                    <option value="amount" {{ old('discount_type', $coupon->discount_type) == 'amount' ? 'selected' : '' }}>Amount</option>
                                </select>
                            </div>
                    
                            <div class="grid grid-cols-2 gap-5">
                                <input class="input" name="start_date" type="text" placeholder="Discount Start Date" value="{{ old('start_date', $coupon->start_date) }}">
                                <input class="input" name="end_date" type="text" placeholder="Discount End Date" value="{{ old('end_date', $coupon->end_date) }}">
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
    <!-- End of Container -->
</main>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    flatpickr("input[name='start_date']");
    flatpickr("input[name='end_date']");

    let productChoices = null;

    function toggleCouponType() {
        const type = document.getElementById('coupon_type').value;
        const productSection = document.getElementById('product_select_section');
        const totalOrderSection = document.getElementById('total_order_section');

        if (type === 'product_base') {
            productSection.style.display = 'block';
            totalOrderSection.style.display = 'none';

            // Clear Total Order Fields
            document.querySelector("input[name='min_order_amount']").value = '';
            document.querySelector("input[name='max_discount_amount']").value = '';

            if (!productChoices) {
                productChoices = new Choices('#products', {
                    searchEnabled: true,
                    itemSelectText: '',
                    shouldSort: false,
                    removeItemButton: true,
                });
            }
        } else {
            productSection.style.display = 'none';
            totalOrderSection.style.display = 'block';
        }
    }

    document.getElementById('coupon_type').addEventListener('change', toggleCouponType);
    window.addEventListener('DOMContentLoaded', toggleCouponType);

    document.getElementById('user_id').addEventListener('change', function () {
        const userId = this.value;
        const productSelect = document.getElementById('products');
        productSelect.innerHTML = '<option>Loading...</option>';

        if (userId) {
            fetch(`/admin/products/${userId}`)
                .then(res => res.json())
                .then(data => {
                    productSelect.innerHTML = '';
                    data.products.forEach(product => {
                        const option = document.createElement('option');
                        option.value = product.id;
                        option.textContent = product.name;
                        productSelect.appendChild(option);
                    });

                    if (!productChoices) {
                        productChoices = new Choices('#products', {
                            searchEnabled: true,
                            itemSelectText: '',
                            shouldSort: false,
                            removeItemButton: true,
                        });
                    }
                })
                .catch(err => {
                    productSelect.innerHTML = '<option>Error loading products</option>';
                });
        }
    });
</script>
@endpush
