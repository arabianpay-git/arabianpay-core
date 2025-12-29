@extends('layouts.base')

@section('content')
    @push('styles')
        <script src="https://cdn.tailwindcss.com"></script>
    @endpush

    <main class="grow content pt-5 bg-white" id="content" role="content">
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

                                <!-- Custom Onboarding Step Dropdown -->
                                <div class="relative" id="onboarding-dropdown">
                                    <!-- Hidden input to store the value -->
                                    <input type="hidden" id="filter-onboarding-step" name="onboarding_step"
                                        value="{{ request('onboarding_step', '') }}">

                                    <!-- Dropdown toggle button -->
                                    <button type="button" id="onboarding-dropdown-toggle"
                                        class="select select-sm flex items-center justify-between w-56 text-left"
                                        style="width: 14rem;">
                                        <span id="onboarding-selected-text">
                                            @php
                                                $onboardingSteps = [
                                                    'basic-info' => '1. Basic Info',
                                                    'business-revenue' => '2. Business Revenue',
                                                    'business-verification' => '3. Business Verification',
                                                    'personal-details' => '4. Personal Details',
                                                    'bank-details' => '5. Bank Details',
                                                    'additional-details' => '6. Additional Details',
                                                ];
                                                $selectedStep = request('onboarding_step', '');
                                                echo $selectedStep && isset($onboardingSteps[$selectedStep])
                                                    ? $onboardingSteps[$selectedStep]
                                                    : translate('All Onboarding Steps');
                                            @endphp
                                        </span>
                                        <i class="ki-solid ki-down ml-2"></i>
                                    </button>

                                    <!-- Dropdown menu -->
                                    <div id="onboarding-dropdown-menu"
                                        class="absolute z-50 hidden mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg">
                                        <div class="p-3">
                                            <div class="dropdown-option" data-value="">
                                                <div class="font-medium text-gray-900">
                                                    {{ translate('All Onboarding Steps') }}</div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ translate('Show all suppliers regardless of onboarding progress') }}
                                                </div>
                                            </div>

                                            <div class="border-t my-2"></div>

                                            @foreach ([
            'basic-info' => [
                'label' => translate('1. Basic Info'),
                'description' => translate('Users who registered but haven\'t provided business name'),
            ],
            'business-revenue' => [
                'label' => translate('2. Business Revenue'),
                'description' => translate('Users with business name but haven\'t provided revenue details'),
            ],
            'business-verification' => [
                'label' => translate('3. Business Verification'),
                'description' => translate('Users with business name & revenue but haven\'t completed business verification'),
            ],
            'personal-details' => [
                'label' => translate('4. Personal Details'),
                'description' => translate('Merchants with CR number but haven\'t completed Nafath verification'),
            ],
            'bank-details' => [
                'label' => translate('5. Bank Details'),
                'description' => translate('Merchants with approved Nafath but haven\'t added bank details'),
            ],
            'additional-details' => [
                'label' => translate('6. Additional Details'),
                'description' => translate('Merchants with bank details but missing required documents'),
            ],
        ] as $key => $data)
                                                <div class="dropdown-option" data-value="{{ $key }}">
                                                    <div class="font-medium text-gray-900">{{ $data['label'] }}</div>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        {{ $data['description'] }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

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
            const onboardingInput = document.getElementById('filter-onboarding-step');
            const tableContainer = document.getElementById('suppliers-table-container');
            const bulkBtn = document.getElementById('bulk-transfer-btn');

            // Custom dropdown elements
            const dropdownToggle = document.getElementById('onboarding-dropdown-toggle');
            const dropdownMenu = document.getElementById('onboarding-dropdown-menu');
            const selectedText = document.getElementById('onboarding-selected-text');
            const dropdownOptions = document.querySelectorAll('.dropdown-option');

            let timeout = null;
            let currentFetchAbortController = null;

            // Initialize custom dropdown
            function initCustomDropdown() {
                // Toggle dropdown menu
                dropdownToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isHidden = dropdownMenu.classList.contains('hidden');

                    // Close other dropdowns if any
                    document.querySelectorAll('.absolute.bg-white.border.rounded-lg.shadow-lg').forEach(
                        menu => {
                            if (menu !== dropdownMenu) {
                                menu.classList.add('hidden');
                            }
                        });

                    // Toggle current dropdown
                    dropdownMenu.classList.toggle('hidden');

                    // Position dropdown
                    if (!isHidden) {
                        const rect = dropdownToggle.getBoundingClientRect();
                        dropdownMenu.style.left = '0';
                        dropdownMenu.style.top = rect.height + 4 + 'px';
                        dropdownMenu.style.width = rect.width + 'px';
                    }

                    // Rotate arrow icon
                    const arrowIcon = this.querySelector('i');
                    if (arrowIcon) {
                        arrowIcon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                    }
                });

                // Handle option selection
                dropdownOptions.forEach(option => {
                    // Click to select
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const value = this.getAttribute('data-value');
                        const label = this.querySelector('.font-medium').textContent;

                        // Update hidden input
                        onboardingInput.value = value;

                        // Update selected text
                        selectedText.textContent = value ? label : 'All Onboarding Steps';

                        // Update selected state
                        dropdownOptions.forEach(opt => opt.classList.remove('selected'));
                        this.classList.add('selected');

                        // Close dropdown
                        dropdownMenu.classList.add('hidden');
                        dropdownToggle.querySelector('i').style.transform = 'rotate(0deg)';

                        // Trigger filter
                        triggerFilter(true);
                    });
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.add('hidden');
                        dropdownToggle.querySelector('i').style.transform = 'rotate(0deg)';
                    }
                });

                // Close dropdown on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !dropdownMenu.classList.contains('hidden')) {
                        dropdownMenu.classList.add('hidden');
                        dropdownToggle.querySelector('i').style.transform = 'rotate(0deg)';
                    }
                });

                // Initialize selected option
                const currentValue = onboardingInput.value;
                if (currentValue) {
                    const selectedOption = Array.from(dropdownOptions).find(opt =>
                        opt.getAttribute('data-value') === currentValue
                    );
                    if (selectedOption) {
                        selectedOption.classList.add('selected');
                    }
                } else {
                    // Select "All" option
                    const allOption = Array.from(dropdownOptions).find(opt =>
                        opt.getAttribute('data-value') === ''
                    );
                    if (allOption) {
                        allOption.classList.add('selected');
                    }
                }
            }

            /**
             * Build final URL with proper parameter handling
             */
            function buildFinalUrl(resetPage = false) {
                const url = new URL(window.location.href);

                // Helper function to add parameter if it has a value
                const addParam = (key, value) => {
                    if (value && value.toString().trim() !== '') {
                        url.searchParams.set(key, value.toString());
                    } else {
                        url.searchParams.delete(key);
                    }
                };

                // Add all parameters
                addParam('search', searchInput.value);
                addParam('status', statusFilter.value);
                addParam('employee', employeeFilter.value);
                addParam('onboarding_step', onboardingInput.value);

                // Handle page parameter
                if (resetPage) {
                    url.searchParams.set('page', '1');
                }

                return url;
            }

            function updateBrowserUrl(url) {
                window.history.replaceState({}, '', url.toString());
            }

            function fetchSuppliers(resetPage = false) {
                // Abort previous request if still pending
                if (currentFetchAbortController) {
                    currentFetchAbortController.abort();
                }

                // Create new AbortController for this request
                currentFetchAbortController = new AbortController();
                const signal = currentFetchAbortController.signal;

                const finalUrl = buildFinalUrl(resetPage);

                // Update URL in browser without page reload
                updateBrowserUrl(finalUrl);

                // Add loading indicator
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
                    })
                    .catch(err => {
                        if (err.name !== 'AbortError') {
                            console.error('Error fetching suppliers:', err);
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

            // Trigger filter with debouncing
            function triggerFilter(resetPage = false) {
                clearTimeout(timeout);
                timeout = setTimeout(() => fetchSuppliers(resetPage), 300);
            }

            // Handle pagination clicks
            tableContainer.addEventListener('click', function(e) {
                const paginationLink = e.target.closest('.pagination a');
                if (paginationLink) {
                    e.preventDefault();

                    // Update URL with page parameter
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

                // Initialize bulk button state
                toggleBtn();
            }

            // Event Listeners for other filters
            searchInput.addEventListener('input', () => triggerFilter(true));
            statusFilter.addEventListener('change', () => triggerFilter(true));
            employeeFilter.addEventListener('change', () => triggerFilter(true));

            // Handle browser back/forward navigation
            window.addEventListener('popstate', function() {
                // Update filter values from URL
                const urlParams = new URLSearchParams(window.location.search);
                searchInput.value = urlParams.get('search') || '';
                statusFilter.value = urlParams.get('status') || '';
                employeeFilter.value = urlParams.get('employee') || '';
                const onboardingStep = urlParams.get('onboarding_step') || '';

                // Update custom dropdown
                onboardingInput.value = onboardingStep;

                // Update selected text
                const onboardingSteps = {
                    'basic-info': '1. Basic Info',
                    'business-revenue': '2. Business Revenue',
                    'business-verification': '3. Business Verification',
                    'personal-details': '4. Personal Details',
                    'bank-details': '5. Bank Details',
                    'additional-details': '6. Additional Details',
                };
                selectedText.textContent = onboardingStep && onboardingSteps[onboardingStep] ?
                    onboardingSteps[onboardingStep] :
                    'All Onboarding Steps';

                // Update selected option in dropdown
                dropdownOptions.forEach(opt => opt.classList.remove('selected'));
                const selectedOption = Array.from(dropdownOptions).find(opt =>
                    opt.getAttribute('data-value') === onboardingStep
                );
                if (selectedOption) {
                    selectedOption.classList.add('selected');
                } else {
                    // Select "All" option
                    const allOption = Array.from(dropdownOptions).find(opt =>
                        opt.getAttribute('data-value') === ''
                    );
                    if (allOption) allOption.classList.add('selected');
                }

                // Fetch with current URL
                fetchSuppliers(false);
            });

            // Clear filters button
            document.getElementById('clear-filters-btn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = "{{ route('suppliers') }}";
            });

            // Initialize
            initCustomDropdown();
            initCheckboxes();
        });
    </script>
@endpush
