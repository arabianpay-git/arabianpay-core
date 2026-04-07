@extends('layouts.base')

@section('content')
    @push('styles')
        <script src="https://cdn.tailwindcss.com"></script>
    @endpush

    <main class="grow content pt-5 bg-white" id="content" role="content">
        <!-- Header Container -->
        <div class="container-fixed">
            <div class="flex items-center justify-between gap-5 pb-7.5">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Suppliers') }}
                </h1>

                <a href="{{ route('merchants.trashed') }}" class="btn btn-sm btn-outline btn-warning">
                    <i class="ki-filled ki-trash"></i>
                    {{ translate('View Trash') }}
                    @php
                        $trashCount = \App\Models\Merchant::onlyTrashed()->count();
                    @endphp
                    @if ($trashCount > 0)
                        <span class="ml-1 badge badge-sm badge-danger">{{ $trashCount }}</span>
                    @endif
                </a>
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

                                <!-- Custom Onboarding Step Dropdown -->
                                {{-- @include('admin.accounts.components.on-boarding-filter') --}}

                                <a href="{{ route('suppliers') }}" class="btn btn-sm btn-light" id="clear-filters-btn"
                                    type="button">
                                    <i class="ki-filled ki-arrows-circle"></i>
                                    {{ translate('Clear') }}
                                </a>

                                <a href="{{ route('suppliers.export', request()->query()) }}" id="suppliers-export-link"
                                    class="btn btn-sm btn-success" data-export-url="{{ route('suppliers.export') }}">
                                    <i class="ki-filled ki-file-down"></i>
                                    {{ translate('Export Excel') }}
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

@push('styles')
    <style>
        /* Custom dropdown styles */
        #onboarding-dropdown-toggle {
            min-height: 32px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: left;
        }

        #onboarding-dropdown-toggle:hover {
            border-color: #9ca3af;
        }

        #onboarding-dropdown-toggle:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        #onboarding-dropdown-toggle i {
            transition: transform 0.2s;
        }

        #onboarding-dropdown-menu {
            max-height: 400px;
            overflow-y: auto;
        }

        .dropdown-option {
            padding: 0.75rem;
            margin-bottom: 0.25rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .dropdown-option:hover {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }

        .dropdown-option.selected {
            background-color: #eff6ff;
            border-color: #3b82f6;
        }

        .dropdown-option:last-child {
            margin-bottom: 0;
        }

        .dropdown-option .font-medium {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .dropdown-option .text-xs {
            line-height: 1.25;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('supplier-search-input');
            const statusFilter = document.getElementById('filter-status');
            const employeeFilter = document.getElementById('filter-employee');
            const tableContainer = document.getElementById('suppliers-table-container');
            const bulkBtn = document.getElementById('bulk-transfer-btn');

            let timeout = null;
            let currentFetchAbortController = null;

            /**
             * Build final URL with proper parameter handling
             */
            function buildFinalUrl(resetPage = false) {
                const url = new URL(window.location.href);

                const addParam = (key, value) => {
                    if (value && value.toString().trim() !== '') {
                        url.searchParams.set(key, value.toString());
                    } else {
                        url.searchParams.delete(key);
                    }
                };

                addParam('search', searchInput.value);
                addParam('status', statusFilter.value);
                addParam('employee', employeeFilter.value);

                if (resetPage) {
                    url.searchParams.set('page', '1');
                }

                return url;
            }

            function syncExportLink() {
                const exportLink = document.getElementById('suppliers-export-link');
                if (!exportLink) {
                    return;
                }
                const base = exportLink.getAttribute('data-export-url');
                if (!base) {
                    return;
                }
                const finalUrl = buildFinalUrl(false);
                const u = new URL(base, window.location.origin);
                ['search', 'status', 'employee'].forEach((key) => {
                    const v = finalUrl.searchParams.get(key);
                    if (v) {
                        u.searchParams.set(key, v);
                    } else {
                        u.searchParams.delete(key);
                    }
                });
                u.searchParams.delete('page');
                exportLink.href = u.toString();
            }

            function updateBrowserUrl(url) {
                window.history.replaceState({}, '', url.toString());
            }

            function fetchSuppliers(resetPage = false) {
                if (currentFetchAbortController) {
                    currentFetchAbortController.abort();
                }

                currentFetchAbortController = new AbortController();
                const signal = currentFetchAbortController.signal;

                const finalUrl = buildFinalUrl(resetPage);
                updateBrowserUrl(finalUrl);

                tableContainer.classList.add('opacity-50', 'pointer-events-none');

                fetch(finalUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || ''
                        },
                        signal: signal
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.text();
                    })
                    .then(html => {
                        tableContainer.innerHTML = html;
                        tableContainer.classList.remove('opacity-50', 'pointer-events-none');
                        initCheckboxes();
                        syncExportLink();
                    })
                    .catch(err => {
                        if (err.name !== 'AbortError') {
                            console.error(err);
                            tableContainer.classList.remove('opacity-50', 'pointer-events-none');
                            tableContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="ki-filled ki-information text-2xl mb-2"></i>
                        <p>Error loading suppliers. Please try again.</p>
                        <button onclick="fetchSuppliers(false)" class="btn btn-sm btn-primary mt-2">
                            Retry
                        </button>
                    </div>
                `;
                        }
                    });
            }

            function triggerFilter(resetPage = false) {
                clearTimeout(timeout);
                timeout = setTimeout(() => fetchSuppliers(resetPage), 300);
            }

            tableContainer.addEventListener('click', function(e) {
                const paginationLink = e.target.closest('.pagination a');
                if (paginationLink) {
                    e.preventDefault();
                    const url = new URL(paginationLink.href);
                    window.history.pushState({}, '', url.toString());
                    fetchSuppliers(false);
                }
            });

            function initCheckboxes() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAll = document.querySelector('input[data-datatable-check="true"]');

                function toggleBtn() {
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    if (bulkBtn) {
                        bulkBtn.classList.toggle('hidden', !anyChecked);
                    }
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

                toggleBtn();
            }

            searchInput.addEventListener('input', () => triggerFilter(true));
            statusFilter.addEventListener('change', () => triggerFilter(true));
            employeeFilter.addEventListener('change', () => triggerFilter(true));

            window.addEventListener('popstate', function() {
                const urlParams = new URLSearchParams(window.location.search);
                searchInput.value = urlParams.get('search') || '';
                statusFilter.value = urlParams.get('status') || '';
                employeeFilter.value = urlParams.get('employee') || '';
                fetchSuppliers(false);
            });

            document.getElementById('clear-filters-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = "{{ route('suppliers') }}";
            });

            initCheckboxes();
            syncExportLink();
        });
    </script>
@endpush
