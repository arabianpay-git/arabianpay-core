@extends('layouts.base')

@push('styles')
    <style>
        .choices__inner {
            /* min-height: 2.4rem !important; */
            /* height: 2.4rem !important; */
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            border-radius: 0.375rem;
        }

        .choices__input {
            height: auto !important;
            margin: 0 !important;
        }

        .choices__list--multiple .choices__item {
            border-radius: 0.375rem !important;
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

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">{{ translate('Add New Supplier Role') }}</h3>
                        </div>
                        <form action="{{ route('supplier_roles.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">

                                <!-- Role Name Field -->
                                <div class="w-full">
                                    <label class="form-label">{{ translate('Role Name') }}</label>
                                    <input class="input @error('name') border-red-500 @enderror" name="name"
                                        type="text" value="{{ old('name') }}" required />
                                    @error('name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Permissions Field -->
                                <div class="w-full">
                                    <label class="form-label">{{ translate('Permissions') }}</label>
                                    <select name="permissions[]" id="permissions" multiple
                                        class="input @error('permissions') border-red-500 @enderror">
                                        <option value="dashboard"
                                            {{ collect(old('permissions'))->contains('dashboard') ? 'selected' : '' }}>
                                            Dashboard
                                        </option>
                                        <option value="branches"
                                            {{ collect(old('permissions'))->contains('branches') ? 'selected' : '' }}>
                                            Branches
                                        </option>
                                        <option value="products"
                                            {{ collect(old('permissions'))->contains('products') ? 'selected' : '' }}>
                                            Products
                                        </option>
                                        <option value="orders"
                                            {{ collect(old('permissions'))->contains('orders') ? 'selected' : '' }}>
                                            Orders
                                        </option>
                                        <option value="financial"
                                            {{ collect(old('permissions'))->contains('financial') ? 'selected' : '' }}>
                                            Financial
                                        </option>
                                        <option value="coupons"
                                            {{ collect(old('permissions'))->contains('coupons') ? 'selected' : '' }}>
                                            Coupons
                                        </option>
                                        <option value="support_tickets"
                                            {{ collect(old('permissions'))->contains('support_tickets') ? 'selected' : '' }}>
                                            Support & Tickets
                                        </option>
                                        <option value="media_uploads"
                                            {{ collect(old('permissions'))->contains('media_uploads') ? 'selected' : '' }}>
                                            Media Uploads
                                        </option>
                                        <option value="shop_settings"
                                            {{ collect(old('permissions'))->contains('shop_settings') ? 'selected' : '' }}>
                                            Shop Settings
                                        </option>
                                        <option value="pickup_points"
                                            {{ collect(old('permissions'))->contains('pickup_points') ? 'selected' : '' }}>
                                            Pickup Points
                                        </option>
                                        <option value="reports"
                                            {{ collect(old('permissions'))->contains('reports') ? 'selected' : '' }}>
                                            Reports
                                        </option>
                                        <option value="refunds"
                                            {{ collect(old('permissions'))->contains('refunds') ? 'selected' : '' }}>
                                            Refunds
                                        </option>
                                        <option value="users"
                                            {{ collect(old('permissions'))->contains('users') ? 'selected' : '' }}>
                                            User Mangment
                                        </option>
                                    </select>
                                    @error('permissions')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">{{ translate('Save Changes') }}</button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new Choices('#permissions', {
                removeItemButton: true,
                searchEnabled: true,
                placeholder: true,
                placeholderValue: "{{ translate('Select Permissions') }}"
            });
        });
    </script>
@endpush
