<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="card-title font-semibold text-gray-800 flex items-center gap-2">
            {{ translate('Bank Statement Report') }}
        </h3>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="text" id="reportIdInput" placeholder="26f8cf58-98d3-4a64-b5ee-c48e6ea790ae"
                    class="input w-64" value="40de217f-866e-4b3f-9ded-3b54024b1323">
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <i class="ki-outline ki-search text-gray-400"></i>
                </div>
            </div>
            <button id="fetchBankStatementBtn"
                class="btn btn-primary bg-green-600 hover:bg-green-700 border-green-600 px-4 py-2 text-white font-medium rounded-lg transition duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ki-outline ki-document-download fs-3"></i>
                {{ translate('Fetch Statement') }}
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="bankStatementContainer" class="p-4">
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                    <i class="ki-duotone ki-document-text text-2xl text-green-600">
                        <i class="path1"></i>
                        <i class="path2"></i>
                    </i>
                </div>
                <h4 class="text-lg font-semibold text-gray-700 mb-2">
                    {{ translate('Fetch Bank Statement') }}
                </h4>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    {{ translate('Enter a Report ID and click "Fetch Statement" to retrieve bank statement details.') }}
                </p>
                <div class="mt-4 text-xs text-gray-400 bg-gray-50 p-3 rounded-lg inline-block">
                    <div class="flex items-center gap-2">
                        <i class="ki-outline ki-information"></i>
                        <span>Example Report ID: 26f8cf58-98d3-4a64-b5ee-c48e6ea790ae</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchBankStatementBtn');
            const container = document.getElementById('bankStatementContainer');
            const reportIdInput = document.getElementById('reportIdInput');
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

            function formatCurrency(amount, currency) {
                if (amount === null || amount === undefined) return '-';
                try {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: currency || 'SAR',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(amount);
                } catch (e) {
                    return `${amount} ${currency || ''}`;
                }
            }

            function formatDate(dateString) {
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

            function formatDateOnly(dateString) {
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

            function renderStatusBadge(status) {
                const statusMap = {
                    'OK': {
                        class: 'bg-green-100 text-green-800 border-green-200',
                        icon: 'ki-duotone ki-check fs-3',
                        text: 'Successful'
                    },
                    'PENDING': {
                        class: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                        icon: 'ki-duotone ki-clock fs-3',
                        text: 'Pending'
                    },
                    'ERROR': {
                        class: 'bg-red-100 text-red-800 border-red-200',
                        icon: 'ki-duotone ki-cross fs-3',
                        text: 'Error'
                    },
                    'PROCESSING': {
                        class: 'bg-blue-100 text-blue-800 border-blue-200',
                        icon: 'ki-duotone ki-refresh fs-3',
                        text: 'Processing'
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

            function renderAccountIdentifiers(identifiers) {
                if (!Array.isArray(identifiers) || identifiers.length === 0) {
                    return '<div class="text-sm text-gray-400">No identifiers</div>';
                }

                return identifiers.map(identifier => `
                <div class="flex items-center justify-between bg-gray-50 rounded p-2 border border-gray-100 mb-2">
                    <div>
                        <span class="text-xs text-gray-500">${escapeHtml(identifier.scheme_name || 'N/A')}</span>
                        <div class="font-medium text-gray-900 text-sm font-mono mt-1 break-all">
                            ${escapeHtml(identifier.identification || 'N/A')}
                        </div>
                        ${identifier.name ? `
                                        <div class="text-xs text-gray-500 mt-1">
                                            ${escapeHtml(identifier.name)}
                                        </div>
                                    ` : ''}
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 ml-2 whitespace-nowrap">
                        ${escapeHtml(identifier.scheme_name || 'N/A')}
                    </span>
                </div>
            `).join('');
            }

            function renderCreditLines(creditLines) {
                if (!Array.isArray(creditLines) || creditLines.length === 0) {
                    return '<div class="text-sm text-gray-400">No credit lines</div>';
                }

                return creditLines.map(line => `
                <div class="flex items-center justify-between bg-gray-50 rounded p-2 border border-gray-100 mb-2">
                    <div class="w-full">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-medium text-sm text-gray-900">${escapeHtml(line.type || 'N/A')}</span>
                            <span class="text-xs px-2 py-0.5 rounded ${line.included ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${line.included ? 'Included' : 'Excluded'}
                            </span>
                        </div>
                        ${line.amount ? `
                                        <div class="text-lg font-bold text-gray-900">
                                            ${formatCurrency(line.amount.amount, line.amount.currency)}
                                        </div>
                                    ` : ''}
                    </div>
                </div>
            `).join('');
            }

            function renderBalances(balances) {
                if (!Array.isArray(balances) || balances.length === 0) {
                    return '<div class="text-sm text-gray-400">No balance data</div>';
                }

                return balances.map(balance => `
                <div class="bg-gradient-to-r from-gray-50 to-white rounded-xl border border-gray-200 p-4 mb-3">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="font-medium text-gray-900">${escapeHtml(balance.type || 'N/A')}</span>
                            <div class="text-xs text-gray-500 mt-1">
                                ${balance.credit_debit_indicator === 'CREDIT' ? 
                                    '<span class="text-green-600">Credit</span>' : 
                                    '<span class="text-red-600">Debit</span>'
                                }
                            </div>
                        </div>
                        ${balance.amount ? `
                                        <div class="text-right">
                                            <div class="text-2xl font-bold ${balance.credit_debit_indicator === 'CREDIT' ? 'text-green-600' : 'text-red-600'}">
                                                ${formatCurrency(balance.amount.amount, balance.amount.currency)}
                                            </div>
                                            <div class="text-xs text-gray-500">${escapeHtml(balance.amount.currency || '')}</div>
                                        </div>
                                    ` : ''}
                    </div>
                    
                    ${balance.credit_line && balance.credit_line.length > 0 ? `
                                    <div class="mt-3 pt-3 border-t border-gray-100">
                                        <h6 class="text-sm font-medium text-gray-700 mb-2">Credit Lines</h6>
                                        <div class="space-y-2">
                                            ${renderCreditLines(balance.credit_line)}
                                        </div>
                                    </div>
                                ` : ''}
                </div>
            `).join('');
            }

            function renderTransaction(transaction) {
                const id = transaction.id || 'N/A';
                const bookingDate = formatDateOnly(transaction.booking_date_time);
                const amount = transaction.amount ? formatCurrency(transaction.amount.amount, transaction.amount
                    .currency) : '-';
                const isCredit = transaction.credit_debit_indicator === 'CREDIT';
                const transactionInfo = transaction.transaction_information || 'No description';
                const balance = transaction.balance;
                const balanceAmount = balance && balance.amount ? formatCurrency(balance.amount.amount, balance
                    .amount.currency) : null;
                const balanceType = balance ? balance.type : null;
                const balanceIndicator = balance ? balance.credit_debit_indicator : null;

                return `
                <div class="bg-white rounded-lg border border-gray-200 p-4 hover:bg-gray-50 transition-colors duration-150">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center ${isCredit ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}">
                                        <i class="ki-duotone ${isCredit ? 'ki-arrow-down' : 'ki-arrow-up'} fs-3"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 text-sm mb-1 break-words">
                                        ${escapeHtml(transactionInfo)}
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <i class="ki-outline ki-calendar"></i>
                                            ${bookingDate}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="ki-outline ki-barcode"></i>
                                            ID: ${escapeHtml(id)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 text-right flex-shrink-0">
                            <div class="text-lg font-bold ${isCredit ? 'text-green-600' : 'text-red-600'}">
                                ${amount}
                            </div>
                            ${balanceAmount ? `
                                            <div class="text-xs text-gray-500 mt-1">
                                                <div class="flex items-center gap-1 justify-end">
                                                    <i class="ki-outline ki-chart-line"></i>
                                                    ${balanceAmount} (${escapeHtml(balanceType || '')})
                                                </div>
                                            </div>
                                        ` : ''}
                        </div>
                    </div>
                    ${balance && balance.amount ? `
                                    <div class="mt-3 pt-3 border-t border-gray-100">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500">Balance after transaction:</span>
                                            <span class="font-medium ${balanceIndicator === 'CREDIT' ? 'text-green-600' : 'text-red-600'}">
                                                ${formatCurrency(balance.amount.amount, balance.amount.currency)}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Type: ${escapeHtml(balance.type || 'N/A')}
                                        </div>
                                    </div>
                                ` : ''}
                </div>
            `;
            }

            function renderTransactionsSection(transactions) {
                if (!Array.isArray(transactions) || transactions.length === 0) {
                    return `
                    <div class="text-center py-8 bg-gray-50 rounded-xl">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
                            <i class="ki-duotone ki-inbox text-xl text-gray-400"></i>
                        </div>
                        <p class="text-sm text-gray-500">No transactions found for this period</p>
                    </div>
                `;
                }

                return `
                <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                    <!-- Transactions Header -->
                    <div class="bg-gradient-to-r from-gray-50 to-white px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h6 class="font-medium text-gray-900 flex items-center gap-2">
                                <i class="ki-duotone ki-arrow-swap text-purple-500"></i>
                                Transactions (${transactions.length})
                            </h6>
                            <div class="text-sm text-gray-500">
                                Scroll to view all transactions
                            </div>
                        </div>
                    </div>
                    
                    <!-- Scrollable Transactions Container -->
                    <div class="h-[400px] overflow-y-auto p-4">
                        <div class="space-y-3">
                            ${transactions.map(transaction => renderTransaction(transaction)).join('')}
                        </div>
                    </div>
                    
                    <!-- Transactions Summary -->
                    <div class="bg-white border-t border-gray-200 px-4 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="text-gray-500">
                                Showing ${transactions.length} transaction${transactions.length !== 1 ? 's' : ''}
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    <span class="text-gray-600">Credit</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                    <span class="text-gray-600">Debit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }

            function renderAccountCard(accountData) {
                const account = accountData.account || {};
                const balances = accountData.balances || [];
                const transactions = accountData.transactions || [];

                return `
                <div class="bg-white rounded-xl border border-gray-200 hover:border-green-300 transition-all duration-200 hover:shadow-lg mb-6 overflow-hidden">
                    <!-- Account Header -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-5 border-b border-green-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-md">
                                    <i class="ki-duotone ki-wallet text-white text-lg"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 text-lg truncate">${escapeHtml(account.nickname || 'Unnamed Account')}</h4>
                                    <p class="text-sm text-gray-600 mt-1 truncate">${escapeHtml(account.description || '')}</p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="flex flex-col gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200 gap-2">
                                        <i class="ki-duotone ki-user-tick"></i>
                                        ${escapeHtml(account.account_holder_name || 'N/A')}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium px-2 py-1 rounded bg-gray-100 text-gray-800">
                                            ${escapeHtml(account.account_type || 'N/A')}
                                        </span>
                                        <span class="text-xs font-medium px-2 py-1 rounded bg-purple-100 text-purple-800">
                                            ${escapeHtml(account.account_sub_type || 'N/A')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Details -->
                    <div class="p-5">
                        <!-- Account Identifiers -->
                        <div class="mb-6">
                            <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ki-duotone ki-key fs-3 text-blue-500"></i>
                                Account Identifiers
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                ${renderAccountIdentifiers(account.account || [])}
                            </div>
                        </div>
                        
                        <!-- Balances -->
                        <div class="mb-6">
                            <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ki-duotone ki-chart-line fs-3 text-green-500"></i>
                                Account Balances
                            </h5>
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                ${renderBalances(balances)}
                            </div>
                        </div>
                        
                        <!-- Transactions -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ki-duotone ki-arrow-swap fs-3 text-purple-500"></i>
                                Transactions
                            </h5>
                            ${renderTransactionsSection(transactions)}
                        </div>
                    </div>
                </div>
            `;
            }

            function renderBankCard(bank) {
                const bankName = bank.bank_name || bank.bank_identifier || 'Unknown Bank';
                const identity = bank.identity || {};
                const accounts = bank.accounts || [];

                return `
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl mb-8 overflow-hidden">
                    <!-- Bank Header -->
                    <div class="p-6 border-b border-blue-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
                                    <i class="ki-duotone ki-bank text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-xl">${escapeHtml(bankName)}</h3>
                                    <div class="flex items-center gap-4 mt-2">
                                        <div class="flex items-center gap-2">
                                            <i class="ki-duotone ki-user text-gray-400"></i>
                                            <span class="text-sm text-gray-600">${escapeHtml(identity.full_name || 'N/A')}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="ki-duotone ki-location text-gray-400"></i>
                                            <span class="text-sm text-gray-600">${escapeHtml(identity.address_line || 'N/A')}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-full bg-blue-100 text-blue-700 border border-blue-200">
                                    <i class="ki-duotone ki-profile-circle"></i>
                                    ${accounts.length} Account${accounts.length !== 1 ? 's' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accounts -->
                    <div class="p-6">
                        <div class="space-y-6">
                            ${accounts.map(accountData => renderAccountCard(accountData)).join('')}
                        </div>
                    </div>
                </div>
            `;
            }

            function renderReportProperties(properties) {
                if (!properties) return '';

                return `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">Start Date</div>
                        <div class="text-sm font-medium text-gray-900">${formatDate(properties.start_date)}</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">End Date</div>
                        <div class="text-sm font-medium text-gray-900">${formatDate(properties.end_date)}</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="text-xs text-gray-500 mb-1">Account Types</div>
                        <div class="text-sm font-medium text-gray-900">
                            ${properties.account_sub_types ? 
                                (Array.isArray(properties.account_sub_types) ? 
                                    properties.account_sub_types.join(', ') : 
                                    properties.account_sub_types) : 
                                'All Types'}
                        </div>
                    </div>
                </div>
            `;
            }

            // Fetch Bank Statement Function
            async function fetchBankStatement() {
                const reportId = reportIdInput.value.trim();

                if (!reportId) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ translate('Report ID Required') }}',
                        text: '{{ translate('Please enter a Report ID.') }}',
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

                // Show loading
                container.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 mb-4">
                        <i class="ki-duotone ki-abstract-12 text-2xl text-white animate-spin"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 text-lg mb-2">Fetching Bank Statement</h4>
                    <p class="text-sm text-gray-600 max-w-md mx-auto">
                        Retrieving bank statement report from Lean Open Banking...
                    </p>
                    <div class="mt-4 text-sm text-gray-500">
                        <div class="inline-flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-full">
                            <i class="ki-outline ki-document"></i>
                            <span class="font-mono">${escapeHtml(reportId)}</span>
                        </div>
                    </div>
                </div>`;

                try {
                    // Build URL
                    const url = `/admin/lean/${customerId}/bank-statement/${encodeURIComponent(reportId)}`;

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
                        const report = data.data;
                        const reportData = report.report || {};
                        const banks = reportData.banks || [];
                        const properties = reportData.properties || {};

                        // Calculate totals
                        let totalAccounts = 0;
                        let totalTransactions = 0;
                        let totalBalance = 0;

                        banks.forEach(bank => {
                            const accounts = bank.accounts || [];
                            totalAccounts += accounts.length;

                            accounts.forEach(account => {
                                const transactions = account.transactions || [];
                                totalTransactions += transactions.length;

                                // Sum closing balances
                                const balances = account.balances || [];
                                balances.forEach(balance => {
                                    if (balance.type === 'CLOSING_BOOKED' && balance
                                        .amount) {
                                        totalBalance += parseFloat(balance.amount
                                            .amount) || 0;
                                    }
                                });
                            });
                        });

                        container.innerHTML = `
                        <div>
                            <!-- Report Header -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl p-6 mb-8 border border-green-100">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                                    <div>
                                        <div class="flex items-center gap-3 mb-2">
                                            <h4 class="font-bold text-gray-900 text-xl">Bank Statement Report</h4>
                                            ${renderStatusBadge(report.status)}
                                        </div>
                                        <p class="text-gray-600">
                                            Detailed bank statement analysis
                                        </p>
                                        <div class="mt-3">
                                            <div class="text-sm text-gray-500 font-mono bg-white px-3 py-1.5 rounded-lg inline-block">
                                                ${escapeHtml(report.report_id)}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-gray-900">${totalAccounts}</div>
                                            <div class="text-sm text-gray-500">Accounts</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-blue-600">${banks.length}</div>
                                            <div class="text-sm text-gray-500">Banks</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-purple-600">${totalTransactions}</div>
                                            <div class="text-sm text-gray-500">Transactions</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-3xl font-bold text-green-600">${formatCurrency(totalBalance)}</div>
                                            <div class="text-sm text-gray-500">Total Balance</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                                            <div class="text-xs text-gray-500 mb-1">Report ID</div>
                                            <div class="text-sm font-medium text-gray-900 truncate">${escapeHtml(report.report_id)}</div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                                            <div class="text-xs text-gray-500 mb-1">Created At</div>
                                            <div class="text-sm font-medium text-gray-900">${formatDate(report.created_at)}</div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                                            <div class="text-xs text-gray-500 mb-1">Status Detail</div>
                                            <div class="text-sm font-medium text-gray-900">${escapeHtml(report.status_detail || 'N/A')}</div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                                            <div class="text-xs text-gray-500 mb-1">Retrieved</div>
                                            <div class="text-sm font-medium text-gray-900">${formatDate(data.meta.timestamp)}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Report Properties -->
                            ${renderReportProperties(properties)}
                            
                            <!-- Banks List -->
                            <div class="space-y-8">
                                ${banks.length > 0 ? 
                                    banks.map(bank => renderBankCard(bank)).join('') : 
                                    `<div class="text-center py-12 bg-gray-50 rounded-xl">
                                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                                        <i class="ki-duotone ki-bank text-2xl text-gray-400"></i>
                                                    </div>
                                                    <h4 class="font-bold text-gray-900 text-lg mb-2">No Bank Data</h4>
                                                    <p class="text-sm text-gray-600">
                                                        No bank data found in this report.
                                                    </p>
                                                </div>`
                                }
                            </div>
                            
                            <!-- Report Summary -->
                            <div class="mt-8 p-6 bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl border border-gray-200">
                                <h5 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                                    <i class="ki-duotone ki-chart-simple fs-3 text-blue-500"></i>
                                    Report Summary
                                </h5>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="text-center p-3">
                                        <div class="text-xs text-gray-500">Report Status</div>
                                        <div class="text-lg font-bold text-gray-900">${escapeHtml(report.status)}</div>
                                    </div>
                                    <div class="text-center p-3">
                                        <div class="text-xs text-gray-500">Banks Analyzed</div>
                                        <div class="text-lg font-bold text-blue-600">${banks.length}</div>
                                    </div>
                                    <div class="text-center p-3">
                                        <div class="text-xs text-gray-500">Total Accounts</div>
                                        <div class="text-lg font-bold text-purple-600">${totalAccounts}</div>
                                    </div>
                                    <div class="text-center p-3">
                                        <div class="text-xs text-gray-500">Data Period</div>
                                        <div class="text-sm font-medium text-gray-900">
                                            ${properties.start_date ? formatDate(properties.start_date) : 'N/A'} 
                                            to 
                                            ${properties.end_date ? formatDate(properties.end_date) : 'N/A'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Retrieved',
                            text: `Successfully fetched bank statement report`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        throw new Error(data.message || 'Failed to fetch bank statement report');
                    }
                } catch (error) {
                    console.error('Error fetching bank statement:', error);
                    container.innerHTML = `
                    <div class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
                            <i class="ki-duotone ki-warning text-2xl text-red-600"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 text-lg mb-2">Fetch Failed</h4>
                        <p class="text-sm text-gray-600 mb-4 max-w-md mx-auto">
                            ${escapeHtml(error.message)}
                        </p>
                        <button onclick="fetchBankStatement()" 
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
            btn.addEventListener('click', fetchBankStatement);

            // Allow Enter key to trigger fetch
            reportIdInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    fetchBankStatement();
                }
            });
        });
    </script>
@endpush
