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
            z-index: 99 !important;
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
                        <div class="card-header">
                            <h3 class="card-title">{{ translate('Edit Supplier Role') }}</h3>
                        </div>
                        <form action="{{ route('supplier_roles.update', $supplierRole->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body grid gap-5">

                                <!-- Role Name Field -->
                                <div class="w-full">
                                    <label class="form-label">{{ translate('Role Name') }}</label>
                                    <input class="input @error('name') border-red-500 @enderror" name="name"
                                        type="text" value="{{ old('name', $supplierRole->name) }}" required />
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
                                            {{ in_array('dashboard', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Dashboard
                                        </option>
                                        <option value="branches"
                                            {{ in_array('branches', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Branches
                                        </option>
                                        <option value="products"
                                            {{ in_array('products', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Products
                                        </option>
                                        <option value="orders"
                                            {{ in_array('orders', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Orders
                                        </option>
                                        <option value="financial"
                                            {{ in_array('financial', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Financial
                                        </option>
                                        <option value="coupons"
                                            {{ in_array('coupons', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Coupons
                                        </option>
                                        <option value="support_tickets"
                                            {{ in_array('support_tickets', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Support & Tickets
                                        </option>
                                        <option value="media_uploads"
                                            {{ in_array('media_uploads', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Media Uploads
                                        </option>
                                        <option value="shop_settings"
                                            {{ in_array('shop_settings', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Shop Settings
                                        </option>
                                        <option value="pickup_points"
                                            {{ in_array('pickup_points', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Pickup Points
                                        </option>
                                        <option value="reports"
                                            {{ in_array('reports', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Reports
                                        </option>
                                        <option value="refunds"
                                            {{ in_array('refunds', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            Refunds
                                        </option>
                                        <option value="users"
                                            {{ in_array('users', old('permissions', $supplierRole->permissions ?? [])) ? 'selected' : '' }}>
                                            User Managment
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
