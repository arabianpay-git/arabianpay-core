<div class="card">
    <div class="card-header flex justify-between items-center px-4 py-3 border-b">
        <h3 class="card-title font-semibold text-gray-800">{{ translate('Accounts Balance') }}</h3>
        <button id="fetchBalanceBtn" class="btn btn-sm btn-primary bg-blue-600 hover:bg-blue-700 border-blue-600">
            {{ translate('Get Balance') }}
        </button>
    </div>
    <div class="card-body p-0" id="balanceContainer">
        <div class="p-4">
            <p class="text-sm text-gray-600">{{ translate('Click "Get Balance" to fetch accounts balance.') }}</p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchBalanceBtn');
            const container = document.getElementById('balanceContainer');

            // Helper function to format numbers with commas, but without decimal places
            function formatNumber(number) {
                if (number === null || number === undefined || isNaN(number)) {
                    return '-';
                }
                return parseFloat(number).toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                } catch (e) {
                    return dateString;
                }
            }

            function renderBalanceCard(balance, index) {
                const formattedAmount = formatNumber(balance.amount?.amount);
                const currency = balance.amount?.currency ?? '';
                const isCredit = balance.creditDebitIndicator === 'Credit' || balance.creditDebitIndicator ===
                    'KSAOB.Credit';

                return `
                    <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-700">{{ translate('Balance') }} #${index + 1}</div>
                                <div class="text-2xl font-bold ${isCredit ? 'text-green-600' : 'text-red-600'} mt-1">
                                    ${formattedAmount} ${currency}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-[5px] text-xs font-medium ${isCredit ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${balance.creditDebitIndicator || '-'}
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">{{ translate('Type') }}:</span>
                                <span class="font-medium text-gray-900">${balance.type || '-'}</span>
                            </div>
                            ${balance.dateTime ? `
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">{{ translate('Date') }}:</span>
                                                        <span class="font-medium text-gray-900">${formatDate(balance.dateTime)}</span>
                                                    </div>
                                                ` : ''}
                        </div>

                        ${balance.creditLine && balance.creditLine.length > 0 ? `
                                                <div class="mt-4 pt-3 border-t border-gray-100">
                                                    <h5 class="text-sm font-semibold text-gray-700 mb-2">{{ translate('Credit Lines') }}</h5>
                                                    <div class="space-y-2">
                                                        ${balance.creditLine.map(cl => {
                                                            const formattedCreditAmount = formatNumber(cl.amount?.amount);
                                                            return `
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-600">${cl.type || 'Credit Line'}:</span>
                                                <span class="font-medium text-blue-600">${formattedCreditAmount} ${cl.amount?.currency || ''}</span>
                                            </div>
                                        `;
                                                        }).join('')}
                                                    </div>
                                                </div>
                                            ` : ''}
                    </div>
                `;
            }

            function renderAccountCard(account, index) {
                const iban = account.accountIdentifiers?.find(id =>
                    id.identificationType === 'KSAOB.IBAN'
                )?.identification ?? '-';

                return `
                    <div class="border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow mb-6">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h4 class="text-base font-semibold text-gray-800">{{ translate('Account') }} #${index + 1} — ${account.nickname || account.accountHolderName || 'N/A'}</h4>
                        </div>
                        <div class="p-4">
                            <!-- Account Details -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 text-sm">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Account ID') }}</div>
                                    <div class="font-medium text-gray-900">${account.accountId || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Holder') }}</div>
                                    <div class="font-medium text-gray-900">${account.accountHolderName || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Currency') }}</div>
                                    <div class="font-medium text-gray-900">${account.currency || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Status') }}</div>
                                    <div class="font-medium ${account.status === 'Active' ? 'text-green-600' : 'text-red-600'}">${account.status || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Type') }}</div>
                                    <div class="font-medium text-gray-900">${account.accountType || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('IBAN') }}</div>
                                    <div class="font-medium text-gray-900">${iban}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Opening Date') }}</div>
                                    <div class="font-medium text-gray-900">${formatDate(account.openingDate)}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Maturity Date') }}</div>
                                    <div class="font-medium text-gray-900">${formatDate(account.maturityDate)}</div>
                                </div>
                            </div>

                            <!-- Balances Section -->
                            <div>
                                <h5 class="font-semibold text-gray-800 mb-4 text-lg">{{ translate('Account Balances') }}</h5>
                                ${account.balance && account.balance.length > 0 ? `
                                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                            ${account.balance.map((bal, bIndex) => renderBalanceCard(bal, bIndex)).join('')}
                                                        </div>
                                                    ` : `
                                                        <div class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
                                                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                            </svg>
                                                            <p class="text-gray-500 text-sm">{{ translate('No balances found for this account.') }}</p>
                                                        </div>
                                                    `}
                            </div>
                        </div>
                    </div>
                `;
            }

            function renderOverallBalance(overallBalance) {
                if (!overallBalance) return '';

                return `
                    <div class="border border-gray-200 rounded-lg shadow-sm">
                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-200">
                            <h4 class="text-lg font-semibold text-blue-800">{{ translate('Overall Balance Summary') }}</h4>
                        </div>
                        <div class="p-6 bg-white">
                            <div class="grid grid-cols-3 md:grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="text-sm text-gray-500 mb-2">{{ translate('Total Opening Balance') }}</div>
                                    <div class="text-2xl font-bold text-blue-600">
                                        ${formatNumber(overallBalance.totalOpeningBalance)} ${overallBalance.currency || ''}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-gray-500 mb-2">{{ translate('Total Available Balance') }}</div>
                                    <div class="text-2xl font-bold text-green-600">
                                        ${formatNumber(overallBalance.totalAvailableBalance)} ${overallBalance.currency || ''}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-gray-500 mb-2">{{ translate('Total Closing Available') }}</div>
                                    <div class="text-2xl font-bold text-purple-600">
                                        ${formatNumber(overallBalance.totalClosingAvailable)} ${overallBalance.currency || ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            async function fetchBalance() {
                btn.setAttribute('disabled', 'disabled');
                const originalText = btn.innerText;
                btn.innerText = '{{ translate('Loading...') }}';
                container.innerHTML =
                    `<div class="p-4"><p class="text-gray-500">{{ translate('Loading...') }}</p></div>`;

                try {
                    const res = await fetch(
                        "{{ route('singleview.fetchAccountsBalance', $merchant->user_id) }}");
                    const data = await res.json();

                    if (data && data.redirect_url) {
                        container.innerHTML =
                            `<div class="p-4"><p class="text-sm text-gray-600">{{ translate('Redirecting to bank for authorization...') }}</p></div>`;
                        window.location.href = data.redirect_url;
                        return;
                    }

                    if (data && data.success && data.payload) {
                        let html = '<div class="px-4 py-6 space-y-6">';

                        data.payload.forEach(bank => {
                            html += `
                                <div class="border-b border-gray-200 pb-6 last:border-b-0">
                                    <h3 class="font-bold text-xl mb-4 pb-2 border-b border-gray-200 text-gray-800">${bank.code || 'Unknown Bank'}</h3>
                                    <div class="space-y-6">
                            `;

                            if (bank.data && bank.data.account && bank.data.account.length > 0) {
                                html += `
                                    <div class="border border-gray-200 rounded-lg shadow-sm">
                                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                            <h4 class="text-lg font-semibold text-gray-800">{{ translate('Accounts') }}</h4>
                                        </div>
                                        <div class="p-6 bg-white">
                                            ${bank.data.account.map((acc, idx) => renderAccountCard(acc, idx)).join('')}
                                        </div>
                                    </div>
                                `;
                            } else {
                                html += `
                                    <div class="border border-gray-200 rounded-lg shadow-sm">
                                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                            <h4 class="text-lg font-semibold text-gray-800">{{ translate('Accounts') }}</h4>
                                        </div>
                                        <div class="p-6 bg-white text-center">
                                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                            </svg>
                                            <p class="text-gray-500">{{ translate('No accounts found for this bank.') }}</p>
                                        </div>
                                    </div>
                                `;
                            }

                            html += `</div></div>`;
                        });

                        // Overall Balance Section
                        if (data.overAllBalance) {
                            html += `
                                <div class="border-t border-gray-200 pt-6">
                                    <h3 class="font-bold text-xl mb-4 pb-2 border-b border-gray-200 text-gray-800">{{ translate('Summary') }}</h3>
                                    ${renderOverallBalance(data.overAllBalance)}
                                </div>
                            `;
                        }

                        html += '</div>';
                        container.innerHTML = html;
                    } else {
                        const errorMessage = (data.payload && data.payload.message) ? data.payload.message :
                            '{{ translate('No balance data available.') }}';
                        container.innerHTML =
                            `<div class="p-4"><p class="text-sm text-gray-600">${errorMessage}</p></div>`;
                    }
                } catch (err) {
                    console.error(err);
                    container.innerHTML =
                        `<div class="p-4"><p class="text-red-500">{{ translate('Failed to load balances.') }}</p></div>`;
                } finally {
                    btn.removeAttribute('disabled');
                    btn.innerText = originalText;
                }
            }

            btn.addEventListener('click', fetchBalance);
        });
    </script>
@endpush
