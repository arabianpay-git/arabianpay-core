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

                                <a href="{{ route('suppliers') }}" class="btn btn-sm btn-light" id="clear-filters-btn"
                                    type="button">
                                    <i class="ki-filled ki-arrows-circle"> </i>
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
            const tableContainer = document.getElementById('suppliers-table-container');
            const bulkBtn = document.getElementById('bulk-transfer-btn');
            let timeout = null;

            function updateUrl() {
                const url = new URL(window.location.href);
                url.searchParams.set('search', searchInput.value.trim());
                url.searchParams.set('status', statusFilter.value);
                url.searchParams.set('employee', employeeFilter.value);

                // 🔥 UPDATE URL WITHOUT RELOAD
                window.history.replaceState({}, '', url);
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
                    });
            }

            function initCheckboxes() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAll = document.querySelector('input[data-datatable-check="true"]');

                function toggleBtn() {
                    const anyChecked = [...checkboxes].some(cb => cb.checked);
                    bulkBtn.classList.toggle('hidden', !anyChecked);
                }

                checkboxes.forEach(cb =>
                    cb.addEventListener('change', () => {
                        toggleBtn();
                        if (selectAll) {
                            selectAll.checked = [...checkboxes].every(c => c.checked);
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

            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSuppliers, 400);
            });

            statusFilter.addEventListener('change', fetchSuppliers);
            employeeFilter.addEventListener('change', fetchSuppliers);

            initCheckboxes();
        });
    </script>
@endpush
