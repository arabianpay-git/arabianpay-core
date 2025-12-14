<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="card-title font-semibold text-gray-800 flex items-center gap-2">
            {{ translate('Customer Entities') }}
        </h3>
        <div class="flex items-center gap-3">
            <!-- Date Range Picker -->
            <div class="flex items-center gap-2 bg-white rounded-lg border border-gray-300 p-2">
                <div class="relative">
                    <input type="text" id="entitiesStartDate" placeholder="{{ translate('Start Date') }}"
                        class="flatpickr-input bg-transparent border-0 text-gray-800 text-sm w-32 focus:outline-none focus:ring-0"
                        readonly>
                </div>
                <span class="text-gray-400">→</span>
                <div class="relative">
                    <input type="text" id="entitiesEndDate" placeholder="{{ translate('End Date') }}"
                        class="flatpickr-input bg-transparent border-0 text-gray-800 text-sm w-32 focus:outline-none focus:ring-0"
                        readonly>
                </div>
            </div>

            <!-- Fetch Button -->
            <button id="fetchEntitiesBtn"
                class="btn btn-primary bg-purple-600 hover:bg-purple-700 border-purple-600 px-4 py-2 text-white font-medium rounded-lg transition duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ki-outline ki-search fs-3"></i>
                {{ translate('Fetch Entities') }}
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="entitiesContainer" class="p-4">
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-100 mb-4">
                    <i class="ki-duotone ki-document text-2xl text-purple-600">
                        <i class="path1"></i>
                        <i class="path2"></i>
                        <i class="path3"></i>
                    </i>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 mb-2">
                    {{ translate('Ready to Fetch') }}
                </h4>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    {{ translate('Choose a start and end date, then click "Fetch Entities" to retrieve entity data from Lean Open Banking.') }}
                </p>
            </div>
        </div>
    </div>
</div>

{{-- prettier-ignore-start --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('fetchEntitiesBtn');
        const container = document.getElementById('entitiesContainer');
        const startDateInput = document.getElementById('entitiesStartDate');
        const endDateInput = document.getElementById('entitiesEndDate');
        const customerId = {{ $customer->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const Swal = window.Swal;

        // Initialize Flatpickr
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        const startDatePicker = flatpickr(startDateInput, {
            dateFormat: "Y-m-d",
            defaultDate: thirtyDaysAgo,
            maxDate: today,
            onChange: function(selectedDates, dateStr) {
                if (dateStr) {
                    endDatePicker.set('minDate', dateStr);
                }
            }
        });

        const endDatePicker = flatpickr(endDateInput, {
            dateFormat: "Y-m-d",
            defaultDate: today,
            minDate: thirtyDaysAgo,
            maxDate: today,
            onChange: function(selectedDates, dateStr) {
                if (dateStr) {
                    startDatePicker.set('maxDate', dateStr);
                }
            }
        });

        // Helper Functions
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function safe(text) {
            return text === null || text === undefined || text === '' ? '-' : escapeHtml(text);
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                if (isNaN(date)) return dateString;
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (e) {
                return dateString;
            }
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                if (isNaN(date)) return dateString;
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }

        function renderStatusBadge(status) {
            const statusMap = {
                'ACTIVE': {
                    class: 'bg-green-100 text-green-800 border-green-200',
                    icon: 'ki-duotone ki-check fs-3',
                    text: 'Active'
                },
                'EXPIRED': {
                    class: 'bg-red-100 text-red-800 border-red-200',
                    icon: 'ki-duotone ki-cross fs-3',
                    text: 'Expired'
                },
                'PENDING': {
                    class: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    icon: 'ki-duotone ki-clock fs-3',
                    text: 'Pending'
                },
                'default': {
                    class: 'bg-gray-100 text-gray-800 border-gray-200',
                    icon: 'ki-duotone ki-question fs-3',
                    text: 'Unknown'
                }
            };

            const statusInfo = statusMap[status] || statusMap['default'];
            return `<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusInfo.class} border gap-2">
                <i class="${statusInfo.icon}"></i>${statusInfo.text}
            </span>`;
        }

        function processAccountsData(accounts) {
            if (!Array.isArray(accounts) || accounts.length === 0) {
                return {
                    nationalId: null,
                    groupedAccounts: {},
                    accountsByType: {}
                };
            }

            // Group accounts by their type (IBAN, MASKED_PAN, etc.)
            const groupedAccounts = {};
            let nationalId = null;
            
            accounts.forEach(account => {
                const scheme = account.scheme_name || account.scheme || 'UNKNOWN';
                const identification = account.identification || 'N/A';
                const name = account.name || null;
                
                if (scheme === 'NATIONAL_ID') {
                    nationalId = identification;
                } else {
                    if (!groupedAccounts[scheme]) {
                        groupedAccounts[scheme] = [];
                    }
                    groupedAccounts[scheme].push({
                        identification: identification,
                        name: name,
                        scheme: scheme
                    });
                }
            });

            return {
                nationalId: nationalId,
                groupedAccounts: groupedAccounts,
                totalAccounts: accounts.filter(acc => {
                    const scheme = acc.scheme_name || acc.scheme || '';
                    return scheme !== 'NATIONAL_ID';
                }).length
            };
        }

        function renderAccountGroup(scheme, accounts) {
            const schemeNames = {
                'IBAN': 'IBAN',
                'MASKED_PAN': 'Card',
                'ACCOUNT_NUMBER': 'Account',
                'BIC': 'BIC',
                'SORT_CODE': 'Sort Code',
                'UNKNOWN': 'Account'
            };

            const schemeDisplay = schemeNames[scheme] || scheme;
            const isIBAN = scheme === 'IBAN';
            const isCard = scheme === 'MASKED_PAN';
            const colSpanClass = isIBAN ? 'lg:col-span-2' : (isCard ? 'lg:col-span-1' : 'lg:col-span-3');
            
            return `
                <div class="${colSpanClass}">
                    <div class="bg-white rounded-lg border border-gray-200 p-4 h-full">
                        <div class="flex items-center justify-between mb-3">
                            <h6 class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                ${isCard ? 
                                    '<i class="ki-duotone ki-credit-cart fs-3 text-blue-500"></i>' : 
                                    '<i class="ki-duotone ki-wallet fs-3 text-blue-500"></i>'
                                }
                                ${schemeDisplay} (${accounts.length})
                            </h6>
                        </div>
                        <div class="space-y-2">
                            ${accounts.map(account => `
                                <div class="flex items-start justify-between bg-gray-50 rounded p-2 border border-gray-100">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 text-sm font-mono break-all">
                                            ${escapeHtml(account.identification)}
                                        </div>
                                        ${account.name ? `
                                            <div class="text-xs text-gray-500 mt-1">
                                                ${escapeHtml(account.name)}
                                            </div>
                                        ` : ''}
                                    </div>
                                    <div class="ml-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            ${escapeHtml(schemeDisplay)}
                                        </span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }

        function renderAccountsSection(accountsData) {
            if (!accountsData.nationalId && Object.keys(accountsData.groupedAccounts).length === 0) {
                return '<div class="text-sm text-gray-400">No accounts</div>';
            }

            let html = '';
            
            // Show National ID at the top
            if (accountsData.nationalId) {
                html += `
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h5 class="text-sm font-medium text-gray-700">Customer Identifier</h5>
                                <p class="text-xs text-gray-500">Unique identifier for this customer</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 border border-purple-200 gap-2">
                                    <i class="ki-duotone ki-profile-circle fs-3"></i>National ID
                                </span>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-purple-50 to-white rounded-xl border border-purple-200 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-2xl font-bold text-gray-900 font-mono">
                                        ${escapeHtml(accountsData.nationalId)}
                                    </div>
                                    <div class="text-sm text-gray-500 mt-1">Unique customer identifier</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Type</div>
                                    <div class="text-sm font-medium text-gray-900">National ID</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Show grouped accounts
            const accountTypes = Object.keys(accountsData.groupedAccounts);
            if (accountTypes.length > 0) {
                html += `
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h5 class="text-sm font-medium text-gray-700">Linked Accounts</h5>
                                <p class="text-xs text-gray-500">Total: ${accountsData.totalAccounts} accounts</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200 gap-2">
                                    <i class="ki-duotone ki-link fs-3"></i>${accountTypes.length} type(s)
                                </span>
                            </div>
                        </div>
                        
                        <!-- Account Groups Grid - 3 columns layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                `;

                // Sort account types: IBAN first, then MASKED_PAN, then others
                const sortedTypes = accountTypes.sort((a, b) => {
                    const order = ['IBAN', 'MASKED_PAN', 'ACCOUNT_NUMBER', 'BIC', 'SORT_CODE'];
                    const indexA = order.indexOf(a);
                    const indexB = order.indexOf(b);
                    if (indexA !== -1 && indexB !== -1) return indexA - indexB;
                    if (indexA !== -1) return -1;
                    if (indexB !== -1) return 1;
                    return a.localeCompare(b);
                });

                sortedTypes.forEach(scheme => {
                    html += renderAccountGroup(scheme, accountsData.groupedAccounts[scheme]);
                });

                html += `
                        </div>
                    </div>
                `;
            }

            return html;
        }

        function renderConsentCard(consent, bankName = null) {
            const status = consent.consent_status || consent.status || 'UNKNOWN';
            const accounts = consent.accounts || [];
            const accountsData = processAccountsData(accounts);
            
            return `
                <div class="bg-white rounded-xl border border-gray-200 hover:border-purple-300 transition-all duration-200 hover:shadow-md">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    ${bankName ? `
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                                <i class="ki-duotone ki-bank text-white text-sm"></i>
                                            </div>
                                            <span class="font-medium text-gray-700">${escapeHtml(bankName)}</span>
                                        </div>
                                    ` : ''}
                                    ${renderStatusBadge(status)}
                                </div>
                                <div class="text-sm text-gray-600 font-mono bg-gray-50 px-3 py-1.5 rounded-lg inline-block">
                                    ${safe(consent.bank_consent_id)}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Consent ID</div>
                                <div class="text-sm font-medium text-gray-900">#${consent.id || consent.bank_consent_id || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="space-y-1">
                                <div class="text-xs text-gray-500">Created</div>
                                <div class="flex items-center text-sm text-gray-900 gap-2">
                                    <i class="ki-outline ki-calendar-tick fs-3 text-gray-400"></i>
                                    ${formatDateTime(consent.creation_date_time)}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-gray-500">Expires</div>
                                <div class="flex items-center text-sm text-gray-900 gap-2">
                                    <i class="ki-outline ki-calendar-cross fs-3 text-gray-400"></i>
                                    ${formatDateTime(consent.expiration_date_time)}
                                </div>
                            </div>
                        </div>
                        
                        ${accountsData.totalAccounts > 0 || accountsData.nationalId ? `
                            <div class="border-t border-gray-100 pt-4">
                                ${renderAccountsSection(accountsData)}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function renderCustomerCard(customerIdentifier, customerData) {
            const banks = customerData.banks || [];
            const totalConsents = customerData.totalConsents || 0;
            const totalAccounts = customerData.totalAccounts || 0;
            const activeConsents = customerData.activeConsents || 0;
            
            // Use the actual identifier name based on what it is
            const identifierType = customerIdentifier.includes('-') ? 'Customer ID' : 'National ID';
            
            return `
                <div class="bg-gradient-to-r from-purple-50 to-white border border-purple-100 rounded-xl shadow-sm mb-6 overflow-hidden">
                    <!-- Customer Header -->
                    <div class="p-5 border-b border-purple-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-md">
                                    <i class="ki-duotone ki-user text-white text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg">${identifierType}: ${escapeHtml(customerIdentifier)}</h4>
                                    <p class="text-sm text-gray-600 mt-1">Customer Consents Overview</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-4">
                                    <div class="text-center">
                                        <div class="text-xl font-bold text-gray-900">${totalConsents}</div>
                                        <div class="text-xs text-gray-500">Consents</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-xl font-bold text-green-600">${activeConsents}</div>
                                        <div class="text-xs text-gray-500">Active</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-xl font-bold text-blue-600">${totalAccounts}</div>
                                        <div class="text-xs text-gray-500">Accounts</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Linked Banks</div>
                                <div class="text-lg font-semibold text-gray-900">${banks.length}</div>
                            </div>
                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Total Accounts</div>
                                <div class="text-lg font-semibold text-blue-600">${totalAccounts}</div>
                            </div>
                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">Bank Types</div>
                                <div class="text-lg font-semibold text-indigo-600">
                                    ${[...new Set(banks.map(b => b.bank_type).filter(Boolean))].length}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank Sections -->
                    <div class="p-5">
                        ${banks.map(bank => {
                            const bankName = bank.bank_name || bank.bank_identifier || 'Unknown Bank';
                            const consents = bank.consents || [];
                            const bankConsentsCount = consents.length;
                            const bankAccountsCount = consents.reduce((total, consent) => {
                                const accounts = consent.accounts || [];
                                return total + accounts.filter(acc => {
                                    const scheme = acc.scheme_name || acc.scheme || '';
                                    return scheme !== 'NATIONAL_ID';
                                }).length;
                            }, 0);
                            
                            return `
                                <div class="mb-6 last:mb-0">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                                <i class="ki-duotone ki-bank text-white"></i>
                                            </div>
                                            <div>
                                                <h5 class="font-bold text-gray-900">${escapeHtml(bankName)}</h5>
                                                <div class="flex items-center gap-3 text-sm text-gray-600">
                                                    <span class="flex items-center gap-1">
                                                        <i class="ki-duotone ki-document"></i>
                                                        ${bankConsentsCount} consent${bankConsentsCount !== 1 ? 's' : ''}
                                                    </span>
                                                    <span class="flex items-center gap-1">
                                                        <i class="ki-duotone ki-wallet"></i>
                                                        ${bankAccountsCount} account${bankAccountsCount !== 1 ? 's' : ''}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-medium px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                                ${escapeHtml(bank.bank_type || 'N/A')}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-4">
                                        ${consents.map(consent => renderConsentCard(consent, bankName)).join('')}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        }

        // Fetch Entities Function
        async function fetchEntities() {
            const startDate = startDatePicker.selectedDates[0];
            const endDate = endDatePicker.selectedDates[0];

            if (!startDate || !endDate) {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ translate('Date Range Required') }}',
                    text: '{{ translate('Please select both start and end dates.') }}',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Disable button and show loading state
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = `
                <i class="ki-duotone ki-abstract-12 fs-3 animate-spin"></i>
                {{ translate('Fetching...') }}
            `;

            // Format dates as YYYY-MM-DD
            const startDateStr = startDate.toISOString().split('T')[0];
            const endDateStr = endDate.toISOString().split('T')[0];

            // Show loading
            container.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 mb-4">
                        <i class="ki-duotone ki-abstract-12 text-2xl text-white animate-spin"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 text-lg mb-2">Fetching Consent Data</h4>
                    <p class="text-sm text-gray-600 max-w-md mx-auto">
                        Retrieving customer consent information from Lean Open Banking...
                    </p>
                    <div class="mt-4 text-sm text-gray-500">
                        <div class="inline-flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full">
                            <i class="ki-outline ki-calendar-8"></i>
                            <span>${startDateStr} → ${endDateStr}</span>
                        </div>
                    </div>
                </div>`;

            try {
                // Build URL with query parameters
                const url = `/admin/lean/${customerId}/entities?start_date=${encodeURIComponent(startDateStr)}&end_date=${encodeURIComponent(endDateStr)}`;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const entities = Array.isArray(data.data) ? data.data : (data.data ? [data.data] : []);

                    if (entities.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-12">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                    <i class="ki-duotone ki-inbox text-2xl text-gray-400"></i>
                                </div>
                                <h4 class="font-bold text-gray-900 text-lg mb-2">No Consents Found</h4>
                                <p class="text-sm text-gray-600 max-w-md mx-auto">
                                    No consent data was found for the selected date range.
                                </p>
                            </div>`;
                        return;
                    }

                    // Group entities by customer identifier (customer_id)
                    const customersMap = {};
                    
                    entities.forEach(entity => {
                        const customerIdentifier = entity.customer_id || 'Unknown';
                        
                        if (!customersMap[customerIdentifier]) {
                            customersMap[customerIdentifier] = {
                                banks: {},
                                totalConsents: 0,
                                totalAccounts: 0,
                                activeConsents: 0
                            };
                        }
                        
                        const bankIdentifier = entity.bank_identifier || 'Unknown';
                        const bankName = entity.bank_name || bankIdentifier;
                        const bankType = entity.bank_type || 'N/A';
                        
                        if (!customersMap[customerIdentifier].banks[bankIdentifier]) {
                            customersMap[customerIdentifier].banks[bankIdentifier] = {
                                bank_name: bankName,
                                bank_identifier: bankIdentifier,
                                bank_type: bankType,
                                consents: []
                            };
                        }
                        
                        const consents = entity.consents || [];
                        customersMap[customerIdentifier].banks[bankIdentifier].consents.push(...consents);
                        
                        // Update totals
                        customersMap[customerIdentifier].totalConsents += consents.length;
                        
                        consents.forEach(consent => {
                            const accounts = consent.accounts || [];
                            // Count only non-NATIONAL_ID accounts
                            const nonNationalIdAccounts = accounts.filter(acc => {
                                const scheme = acc.scheme_name || acc.scheme || '';
                                return scheme !== 'NATIONAL_ID';
                            });
                            customersMap[customerIdentifier].totalAccounts += nonNationalIdAccounts.length;
                            
                            if (consent.consent_status === 'ACTIVE' || consent.status === 'ACTIVE') {
                                customersMap[customerIdentifier].activeConsents++;
                            }
                        });
                    });
                    
                    // Convert map to array for rendering
                    const customers = Object.entries(customersMap).map(([customerIdentifier, customerData]) => {
                        return {
                            customerIdentifier,
                            ...customerData,
                            banks: Object.values(customerData.banks)
                        };
                    });
                    
                    // Calculate overall totals
                    const totalCustomers = customers.length;
                    const totalAllConsents = customers.reduce((sum, cust) => sum + cust.totalConsents, 0);
                    const totalAllAccounts = customers.reduce((sum, cust) => sum + cust.totalAccounts, 0);
                    const totalAllActiveConsents = customers.reduce((sum, cust) => sum + cust.activeConsents, 0);

                    container.innerHTML = `
                        <div>
                            <!-- Summary Stats -->
                            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl p-6 mb-8 border border-purple-100">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-xl mb-2">Consent Overview</h4>
                                        <p class="text-gray-600">
                                            Customer consent data retrieved from Lean Open Banking
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-gray-900">${totalCustomers}</div>
                                            <div class="text-sm text-gray-500">Customers</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-blue-600">${totalAllConsents}</div>
                                            <div class="text-sm text-gray-500">Consents</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-green-600">${totalAllActiveConsents}</div>
                                            <div class="text-sm text-gray-500">Active</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-indigo-600">${totalAllAccounts}</div>
                                            <div class="text-sm text-gray-500">Accounts</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="text-xs text-gray-500 mb-1">Date Range</div>
                                        <div class="text-sm font-medium text-gray-900">${startDateStr} to ${endDateStr}</div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="text-xs text-gray-500 mb-1">Total Entities</div>
                                        <div class="text-sm font-medium text-gray-900">${entities.length}</div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="text-xs text-gray-500 mb-1">Unique Banks</div>
                                        <div class="text-sm font-medium text-gray-900">
                                            ${[...new Set(entities.map(e => e.bank_identifier).filter(Boolean))].length}
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="text-xs text-gray-500 mb-1">Active Rate</div>
                                        <div class="text-sm font-medium text-gray-900">
                                            ${totalAllConsents > 0 ? Math.round((totalAllActiveConsents / totalAllConsents) * 100) : 0}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customers List -->
                            <div class="space-y-6">
                                ${customers.map(customer => renderCustomerCard(customer.customerIdentifier, customer)).join('')}
                            </div>
                            
                            <!-- Empty State if no customers -->
                            ${customers.length === 0 ? `
                                <div class="text-center py-12">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                        <i class="ki-duotone ki-users text-2xl text-gray-400"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-lg mb-2">No Customer Data</h4>
                                    <p class="text-sm text-gray-600">
                                        No customer consent data was found for the selected date range.
                                    </p>
                                </div>
                            ` : ''}
                        </div>`;

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Consents Retrieved',
                        text: `Found ${totalAllConsents} consents for ${totalCustomers} customer(s)`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    throw new Error(data.message || 'Failed to fetch consent data');
                }
            } catch (error) {
                console.error('Error fetching consents:', error);
                container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                            <i class="ki-duotone ki-warning text-2xl text-red-600"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 text-lg mb-2">Fetch Failed</h4>
                        <p class="text-sm text-gray-600 mb-4 max-w-md mx-auto">
                            ${escapeHtml(error.message)}
                        </p>
                        <button onclick="fetchEntities()" 
                                class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="ki-duotone ki-refresh"></i>
                            Try Again
                        </button>
                    </div>`;

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonText: 'OK'
                });
            } finally {
                // Re-enable button
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // Event Listeners
        btn.addEventListener('click', fetchEntities);
    });
</script>
@endpush
{{-- prettier-ignore-end --}}
