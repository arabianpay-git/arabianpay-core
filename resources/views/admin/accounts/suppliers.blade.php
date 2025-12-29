@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Header Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Suppliers') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- Search + Controls Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Suppliers') }}
                        </h3>

                        <div class="flex flex-wrap items-center gap-3">

                            <div class="flex gap-2">
                                <div class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input id="supplier-search-input" placeholder="Search users" type="text"
                                            value="{{ request('search') }}" autocomplete="off" />
                                    </label>
                                </div>

                                <select id="filter-status" class="select select-sm" style="width: 10rem;">
                                    <option value="">{{ translate('All Statuses') }}</option>
                                    @foreach (['under_review', 'contract_sent', 'active', 'pending', 'approved', 'suspended', 'blacklisted'] as $st)
                                        <option value="{{ $st }}"
                                            {{ request('status') == $st ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                                        </option>
                                    @endforeach
                                </select>

                                <select id="filter-employee" class="select select-sm" style="width: 10rem;">
                                    <option value="">{{ translate('All Employees') }}</option>
                                    @foreach (\App\Models\User::where('user_type', 'employee')->get() as $emp)
                                        <option value="{{ $emp->id }}"
                                            {{ request('employee') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->first_name }} {{ $emp->last_name }}
                                        </option>
                                    @endforeach
                                </select>

                                <select id="filter-onboarding-step" class="select select-sm" style="width: 14rem;">
                                    <option value="">{{ translate('All Onboarding Steps') }}</option>
                                    @foreach ([
            'basic-info' => '1. Basic Info',
            'business-revenue' => '2. Business Revenue',
            'business-verification' => '3. Business Verification',
            'personal-details' => '4. Personal Details',
            'bank-details' => '5. Bank Details',
            'additional-details' => '6. Additional Details',
        ] as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ request('onboarding_step') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <a href="{{ route('suppliers') }}" class="btn btn-sm btn-light" id="clear-filters-btn"
                                    type="button">
                                    <i class="ki-filled ki-arrows-circle"></i>
                                    {{ translate('Clear') }}
                                </a>
                            </div>


                            <div class="flex justify-end">
                                <button id="bulk-transfer-btn" class="btn btn-sm btn-primary hidden"
                                    data-modal-toggle="#transfer_request_bulk">
                                    <i class="ki-filled ki-disconnect"></i> {{ translate('Bulk Transfer') }}
                                </button>
                            </div>

                            @include('admin.components.transfer-request-bulk', [
                                'employees' => getEmployees(),
                                'model_type' => 'App\Models\Merchant',
                            ])
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="suppliers-table-container">
                            @include('admin.accounts.partials.suppliers-table', [
                                'merchants' => $merchants,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('admin.components.transfer-detail')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('supplier-search-input');
            const statusFilter = document.getElementById('filter-status');
            const employeeFilter = document.getElementById('filter-employee');
            const onboardingFilter = document.getElementById('filter-onboarding-step');
            const tableContainer = document.getElementById('suppliers-table-container');
            const bulkBtn = document.getElementById('bulk-transfer-btn');
            let timeout = null;

            function updateUrl() {
                const url = new URL(window.location.href);

                // Helper to set or delete params if empty
                const setParam = (key, value) => {
                    if (value && value.trim() !== '') {
                        url.searchParams.set(key, value);
                    } else {
                        url.searchParams.delete(key);
                    }
                };

                setParam('search', searchInput.value);
                setParam('status', statusFilter.value);
                setParam('employee', employeeFilter.value);
                setParam('onboarding_step', onboardingFilter.value);

                window.history.pushState({}, '', url);
                return url.toString();
            }

            function fetchSuppliers() {
                const finalUrl = updateUrl();

                fetch(finalUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                        initCheckboxes();
                    })
                    .catch(err => console.error('Error fetching suppliers:', err));
            }

            function initCheckboxes() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAll = document.querySelector('input[data-datatable-check="true"]');

                function toggleBtn() {
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    if (bulkBtn) bulkBtn.classList.toggle('hidden', !anyChecked);
                }

                checkboxes.forEach(cb =>
                    cb.addEventListener('change', () => {
                        toggleBtn();
                        if (selectAll) {
                            selectAll.checked = Array.from(checkboxes).every(c => c.checked);
                        }
                    })
                );

                if (selectAll) {
                    selectAll.addEventListener('change', () => {
                        const checked = selectAll.checked;
                        checkboxes.forEach(cb => cb.checked = checked);
                        toggleBtn();
                    });
                }
            }

            // Listeners
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSuppliers, 400);
            });

            [statusFilter, employeeFilter, onboardingFilter].forEach(el => {
                if (el) el.addEventListener('change', fetchSuppliers);
            });

            // Initial checkbox run
            initCheckboxes();
        });
    </script>
@endpush
