<div class="card">
    <!-- Header -->
    <div class="card-header flex justify-between items-center">
        <h3 class="card-title font-semibold text-gray-800 flex items-center gap-2">
            {{ translate('Lean Banks') }}
        </h3>
        <div class="flex items-center gap-3">
            <!-- Account Type Filter -->
            <div class="relative">
                <select id="bankAccountType"
                    class="bg-white border border-gray-300 rounded-lg text-gray-800 text-sm px-3 py-2 focus:outline-none focus:ring-0 w-48">
                    <option value="">{{ translate('All Account Types') }}</option>
                    <option value="BUSINESS">{{ translate('Business') }}</option>
                    <option value="PERSONAL">{{ translate('Personal') }}</option>
                </select>
            </div>

            <!-- Fetch Button -->
            <button id="fetchBanksBtn"
                class="btn btn-primary bg-blue-600 hover:bg-blue-700 border-blue-600 px-4 py-2 text-white font-medium rounded-lg transition duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ki-outline ki-search fs-3"></i>
                {{ translate('Fetch Banks') }}
            </button>
        </div>
    </div>

    <!-- Content -->
    <div class="card-body p-0">
        <div id="banksContainer" class="p-4">
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                    <i class="ki-duotone ki-bank text-2xl text-blue-600">
                        <i class="path1"></i>
                        <i class="path2"></i>
                    </i>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 mb-2">
                    {{ translate('Ready to Fetch Banks') }}
                </h4>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    {{ translate('Select an account type and click "Fetch Banks" to retrieve the list of available banks from Lean Open Banking.') }}
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchBanksBtn');
            const container = document.getElementById('banksContainer');
            const accountTypeSelect = document.getElementById('bankAccountType');
            const customerId = {{ $customer->id }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const Swal = window.Swal;

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

            function renderArrayAsChips(array) {
                if (!Array.isArray(array) || array.length === 0) {
                    return '<span class="text-gray-400 text-sm">-</span>';
                }
                return `<div class="flex flex-wrap gap-1">${array.map(item => 
                `<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">${escapeHtml(item)}</span>`
            ).join('')}</div>`;
            }

            function renderBooleanBadge(value) {
                return value ?
                    '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Yes</span>' :
                    '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">No</span>';
            }

            function renderStatusBadge(active, mock) {
                let badges = [];
                if (active) {
                    badges.push(
                        '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 gap-1">' +
                        '<i class="ki-duotone ki-check fs-3"></i>Active</span>'
                    );
                } else {
                    badges.push(
                        '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 gap-1">' +
                        '<i class="ki-duotone ki-cross fs-3"></i>Inactive</span>'
                    );
                }
                if (mock) {
                    badges.push(
                        '<span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 gap-1">' +
                        '<i class="ki-duotone ki-mockup fs-3"></i>Mock</span>'
                    );
                }
                return badges.join(' ');
            }

            // Render Bank Card
            function renderBankCard(bank, index) {
                const logo = bank.logo || bank.logo_url || bank.logo_alt;
                const name = bank.name || bank.bank_name || 'Unknown Bank';
                const identifier = bank.identifier || bank.bank_identifier || bank.id || '-';
                const country = bank.country_code || bank.country || '-';
                const accountType = bank.account_type || bank.bank_type || '-';
                const mainColor = bank.main_color || '#00008b';
                const theme = bank.theme || 'light';
                const active = bank.active || false;
                const mock = bank.mock || false;
                const traits = bank.traits || [];
                const supportedAccounts = bank.supported_account_types || [];
                const availability = bank.availability || {};

                return `
                <div class="bg-white border border-gray-200 rounded-xl hover:border-blue-300 transition-all duration-200 hover:shadow-md mb-4">
                    <!-- Bank Header -->
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    ${logo ? `
                                                                <img src="${escapeHtml(logo)}" alt="${escapeHtml(name)}" 
                                                                     class="w-12 h-12 rounded-lg border border-gray-200 object-contain" style="background-color: ${mainColor};">
                                                            ` : `
                                                                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                                                    <i class="ki-duotone ki-bank text-white text-lg"></i>
                                                                </div>
                                                            `}
                                    <div class="absolute -top-1 -right-1">
                                        <div class="w-3 h-3 rounded-full ${active ? 'bg-green-500' : 'bg-red-500'} border-2 border-white"></div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900">${escapeHtml(name)}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        ${renderStatusBadge(active, mock)}
                                    </div>
                                </div>
                            </div>
                            <button onclick="toggleBankDetails(${index})" 
                                    class="text-blue-600 hover:text-blue-800 flex items-center gap-1 text-sm font-medium">
                                <i class="ki-outline ki-down fs-3"></i>
                                {{ translate('Details') }}
                            </button>
                        </div>
                        
                        <!-- Quick Info -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500">{{ translate('Identifier') }}</div>
                                <div class="font-semibold text-gray-800 text-sm truncate">${escapeHtml(identifier)}</div>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500">{{ translate('Country') }}</div>
                                <div class="font-semibold text-gray-800 text-sm">${escapeHtml(country)}</div>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500">{{ translate('Account Type') }}</div>
                                <div class="font-semibold text-gray-800 text-sm">${escapeHtml(accountType)}</div>
                            </div>
                            <div class="text-center p-2 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500">{{ translate('Theme') }}</div>
                                <div class="font-semibold text-gray-800 text-sm capitalize">${escapeHtml(theme)}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collapsible Details -->
                    <div id="bankDetails-${index}" class="hidden p-5 border-t border-gray-100">
                        <!-- Bank Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Left Column -->
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Arabic Name') }}</label>
                                    <div class="text-gray-900">${safe(bank.arabic_name)}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Main Color') }}</label>
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded border border-gray-300" 
                                             style="background-color: ${mainColor}"></div>
                                        <span class="text-gray-900 font-mono">${mainColor}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Connection Type') }}</label>
                                    <div class="text-gray-900">${safe(bank.connection_type)}</div>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Traits') }}</label>
                                    ${renderArrayAsChips(traits)}
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Supported Accounts') }}</label>
                                    ${renderArrayAsChips(supportedAccounts)}
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ translate('Availability') }}</label>
                                    <div class="space-y-1">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">{{ translate('Active - Data') }}:</span>
                                            ${renderBooleanBadge(availability.active?.data)}
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">{{ translate('Active - Payments') }}:</span>
                                            ${renderBooleanBadge(availability.active?.payments)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Info -->
                        ${bank.background_color || bank.transfer_limits ? `
                                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                                        <h5 class="font-medium text-gray-700 mb-2">{{ translate('Additional Information') }}</h5>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                            ${bank.background_color ? `
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">{{ translate('Background Color') }}</label>
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded border border-gray-300" 
                                                     style="background-color: ${bank.background_color}"></div>
                                                <span class="text-gray-900 font-mono">${bank.background_color}</span>
                                            </div>
                                        </div>
                                    ` : ''}
                                                            ${bank.transfer_limits && Array.isArray(bank.transfer_limits) && bank.transfer_limits.length > 0 ? `
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">{{ translate('Transfer Limits') }}</label>
                                            <div class="text-sm text-gray-900">
                                                ${bank.transfer_limits.map(limit => escapeHtml(limit)).join(', ')}
                                            </div>
                                        </div>
                                    ` : ''}
                                                        </div>
                                                    </div>
                                                ` : ''}
                    </div>
                </div>`;
            }

            // Render All Banks
            function renderAllBanks(banksData) {
                if (!banksData || (Array.isArray(banksData) && banksData.length === 0)) {
                    return `
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <i class="ki-duotone ki-inbox text-2xl text-gray-400"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 text-lg mb-2">{{ translate('No Banks Found') }}</h4>
                        <p class="text-sm text-gray-600 max-w-md mx-auto">
                            {{ translate('No banks were found for the selected account type.') }}
                        </p>
                    </div>`;
                }

                const banks = Array.isArray(banksData) ? banksData : [banksData];
                const activeBanks = banks.filter(b => b.active).length;
                const mockBanks = banks.filter(b => b.mock).length;

                return `
                <div>
                    <!-- Summary Stats -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 mb-8 border border-blue-100">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                            <div>
                                <h4 class="font-bold text-gray-900 text-xl mb-2">{{ translate('Available Banks') }}</h4>
                                <p class="text-gray-600">{{ translate('List of banks retrieved from Lean Open Banking') }}</p>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-gray-900">${banks.length}</div>
                                    <div class="text-sm text-gray-500">{{ translate('Total') }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-green-600">${activeBanks}</div>
                                    <div class="text-sm text-gray-500">{{ translate('Active') }}</div>
                                </div>
                                ${mockBanks > 0 ? `
                                                            <div class="text-center">
                                                                <div class="text-3xl font-bold text-purple-600">${mockBanks}</div>
                                                                <div class="text-sm text-gray-500">{{ translate('Mock') }}</div>
                                                            </div>
                                                        ` : ''}
                            </div>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">{{ translate('Account Type') }}</div>
                                <div class="text-sm font-medium text-gray-900">${document.getElementById('bankAccountType').value || 'All'}</div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">{{ translate('Total Countries') }}</div>
                                <div class="text-sm font-medium text-gray-900">
                                    ${[...new Set(banks.map(b => b.country_code).filter(Boolean))].length}
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">{{ translate('Unique Types') }}</div>
                                <div class="text-sm font-medium text-gray-900">
                                    ${[...new Set(banks.map(b => b.account_type).filter(Boolean))].length}
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="text-xs text-gray-500 mb-1">{{ translate('Active Rate') }}</div>
                                <div class="text-sm font-medium text-gray-900">
                                    ${banks.length > 0 ? Math.round((activeBanks / banks.length) * 100) : 0}%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Banks List -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        ${banks.map((bank, index) => renderBankCard(bank, index)).join('')}
                    </div>
                </div>`;
            }

            // Toggle Bank Details
            window.toggleBankDetails = function(index) {
                const detailsElement = document.getElementById(`bankDetails-${index}`);
                const button = document.querySelector(`button[onclick="toggleBankDetails(${index})"]`);

                if (!detailsElement || !button) return;

                const icon = button.querySelector('i');
                if (detailsElement.classList.contains('hidden')) {
                    detailsElement.classList.remove('hidden');
                    icon.classList.remove('ki-down');
                    icon.classList.add('ki-up');
                    button.innerHTML = '<i class="ki-outline ki-up fs-3"></i> {{ translate('Hide') }}';
                } else {
                    detailsElement.classList.add('hidden');
                    icon.classList.remove('ki-up');
                    icon.classList.add('ki-down');
                    button.innerHTML = '<i class="ki-outline ki-down fs-3"></i> {{ translate('Details') }}';
                }
            };

            // Fetch Banks Function
            async function fetchBanks() {
                const accountType = accountTypeSelect.value;

                if (!accountType) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ translate('Account Type Required') }}',
                        text: '{{ translate('Please select an account type.') }}',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                // Disable button and show loading
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = `
                <i class="ki-duotone ki-abstract-12 fs-3 animate-spin"></i>
                {{ translate('Fetching...') }}
            `;

                container.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 mb-4">
                        <i class="ki-duotone ki-abstract-12 text-2xl text-white animate-spin"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 text-lg mb-2">{{ translate('Fetching Banks Data') }}</h4>
                    <p class="text-sm text-gray-600 max-w-md mx-auto">
                        {{ translate('Retrieving bank information from Lean Open Banking...') }}
                    </p>
                    <div class="mt-4 text-sm text-gray-500">
                        <div class="inline-flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full">
                            <i class="ki-outline ki-bank"></i>
                            <span>Account Type: ${accountType}</span>
                        </div>
                    </div>
                </div>`;

                try {
                    // Build URL with query parameters
                    const url =
                        `/admin/lean/${customerId}/banks?account_types=${encodeURIComponent(accountType)}`;

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
                        // Render banks data
                        container.innerHTML = renderAllBanks(data.data);

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: '{{ translate('Banks Retrieved') }}',
                            text: `Found ${Array.isArray(data.data) ? data.data.length : 1} bank(s)`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        throw new Error(data.message || '{{ translate('Failed to fetch banks') }}');
                    }
                } catch (error) {
                    console.error('Error fetching banks:', error);
                    container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                            <i class="ki-duotone ki-warning text-2xl text-red-600"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 text-lg mb-2">{{ translate('Fetch Failed') }}</h4>
                        <p class="text-sm text-gray-600 mb-4 max-w-md mx-auto">
                            ${escapeHtml(error.message)}
                        </p>
                        <button onclick="fetchBanks()" 
                                class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="ki-duotone ki-refresh"></i>
                            {{ translate('Try Again') }}
                        </button>
                    </div>`;

                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('Error') }}',
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
            btn.addEventListener('click', fetchBanks);

            // Auto-fetch when account type changes
            accountTypeSelect.addEventListener('change', function() {
                if (this.value) {
                    fetchBanks();
                }
            });

            // NOTE: No initial fetch on load - fetch only when user clicks the button or changes account type
        });
    </script>
@endpush
