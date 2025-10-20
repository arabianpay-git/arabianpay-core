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
                        <div class="flex flex-wrap">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input id="supplier-search-input" placeholder="Search users" type="text"
                                        autocomplete="off" />
                                </label>
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

                    <!-- Table + Pagination container for AJAX refresh -->
                    <div class="card-body">
                        <div id="suppliers-table-container">
                            @include('admin.accounts.partials.suppliers-table', [
                                'merchants' => $merchants,
                            ])
                            {{-- @include('admin.accounts.partials.commission-update-modal') --}}
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
            const tableContainer = document.getElementById('suppliers-table-container');
            const bulkBtn = document.getElementById('bulk-transfer-btn');

            let timeout = null;

            function initCheckboxes() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAllCheckbox = document.querySelector('input[data-datatable-check="true"]');

                function toggleBulkBtn() {
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    bulkBtn.classList.toggle('hidden', !anyChecked);
                }

                checkboxes.forEach(cb => cb.addEventListener('change', () => {
                    toggleBulkBtn();
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
                }));

                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', () => {
                        const checked = selectAllCheckbox.checked;
                        checkboxes.forEach(cb => cb.checked = checked);
                        toggleBulkBtn();
                    });
                }
            }

            function fetchSuppliers(search = '') {
                const url = new URL(window.location.href);
                url.searchParams.set('search', search);

                fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        tableContainer.innerHTML = html;
                        initCheckboxes();
                    })
                    .catch(err => console.error('Error fetching suppliers:', err));
            }

            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    fetchSuppliers(this.value.trim());
                }, 400);
            });

            // Initialize checkbox events on first load
            initCheckboxes();
        });
    </script>
@endpush
