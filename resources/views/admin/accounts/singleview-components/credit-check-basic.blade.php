@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush
<div class="card">
    <div class="card-header flex justify-between items-center px-4 py-3 border-b">
        <h3 class="card-title font-semibold text-gray-800">{{ translate('Credit Check Report') }}</h3>
        <button id="fetchCreditReportBtn" class="btn btn-sm btn-primary bg-blue-600 hover:bg-blue-700 border-blue-600">
            {{ translate('Get Report') }}
        </button>
    </div>
    <div class="card-body p-0" id="creditReportContainer">
        <div class="p-4">
            <p class="text-sm text-gray-600">{{ translate('Click "Get Report" to fetch the credit check data.') }}</p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchCreditReportBtn');
            const container = document.getElementById('creditReportContainer');

            // Helper function to format numbers with commas, without decimal places
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

            function renderAccountDetails(account) {
                const iban = account.accountIdentifiers?.find(id => id.identificationType === 'KSAOB.IBAN')
                    ?.identification ?? '-';

                return `
                    <div class="border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow mb-4">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h4 class="text-base font-semibold text-gray-800">{{ translate('Account') }} — ${account.nickname || account.accountHolderName || 'N/A'}</h4>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Account ID') }}</div>
                                    <div class="font-medium text-gray-900">${account.accountId || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Holder Name') }}</div>
                                    <div class="font-medium text-gray-900">${account.accountHolderName || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Status') }}</div>
                                    <div class="font-medium ${account.status === 'Active' ? 'text-green-600' : 'text-red-600'}">${account.status || '-'}</div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs text-gray-500">{{ translate('Currency') }}</div>
                                    <div class="font-medium text-gray-900">${account.currency || '-'}</div>
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
                        </div>
                    </div>
                `;
            }

            function renderAnalysisSection(title, data, type) {
                if (!data || data.length === 0) {
                    return `
                        <div class="border border-gray-200 rounded-lg shadow-sm">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <h4 class="text-lg font-semibold text-gray-800">${title}</h4>
                            </div>
                            <div class="p-6 bg-white">
                                <p class="text-gray-500 text-sm">{{ translate('No data available.') }}</p>
                            </div>
                        </div>
                    `;
                }

                const cards = data.map(item => {
                    const count = type === 'credit' ? item.noOfCredit : item.noOfDebit;
                    const value = type === 'credit' ? item.valueOfCredit : item.valueOfDebit;
                    const bgColor = type === 'credit' ? 'bg-green-50' : 'bg-red-50';
                    const borderColor = type === 'credit' ? 'border-green-200' : 'border-red-200';
                    const textColor = type === 'credit' ? 'text-green-800' : 'text-red-800';

                    return `
                        <div class="border ${borderColor} rounded-lg p-4 ${bgColor} shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="text-sm font-semibold ${textColor}">${formatDate(item.date)}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">{{ translate('Count') }}</div>
                                    <div class="text-lg font-bold ${textColor}">${formatNumber(count)}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="text-gray-600">{{ translate('Number of Transactions') }}:</div>
                                <div class="text-right font-medium">${formatNumber(count)}</div>
                                
                                <div class="text-gray-600">{{ translate('Total Value') }}:</div>
                                <div class="text-right font-medium">${formatNumber(value)}</div>
                            </div>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="border border-gray-200 rounded-lg shadow-sm">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800">${title}</h4>
                        </div>
                        <div class="p-6 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                ${cards}
                            </div>
                        </div>
                    </div>
                `;
            }

            function renderBalanceReport(data) {
                if (!data || !data.monthlyReport || data.monthlyReport.length === 0) {
                    return `
                        <div class="border border-gray-200 rounded-lg shadow-sm">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <h4 class="text-lg font-semibold text-gray-800">{{ translate('Balance Report') }}</h4>
                            </div>
                            <div class="p-6 bg-white">
                                <p class="text-gray-500 text-sm">{{ translate('No balance data available.') }}</p>
                            </div>
                        </div>
                    `;
                }

                const cards = data.monthlyReport.map(item => `
                    <div class="border border-blue-200 rounded-lg p-4 bg-blue-50 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <div class="text-sm font-semibold text-blue-800">${formatDate(item.date)}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ translate('Average Monthly Balance') }}:</span>
                                <span class="font-bold text-blue-700">${formatNumber(item.avgBalanceOfMonth)}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ translate('Month-end Balance') }}:</span>
                                <span class="font-bold text-blue-700">${formatNumber(item.monthendBalance)}</span>
                            </div>
                        </div>
                    </div>
                `).join('');

                return `
                    <div class="border border-gray-200 rounded-lg shadow-sm">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800">{{ translate('Balance Report') }}</h4>
                        </div>
                        <div class="p-6 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                ${cards}
                            </div>
                        </div>
                    </div>
                `;
            }

            async function fetchCreditReport() {
                btn.setAttribute('disabled', 'disabled');
                const originalText = btn.innerText;
                btn.innerText = '{{ translate('Loading...') }}';
                container.innerHTML =
                    `<div class="p-4"><p class="text-gray-500">{{ translate('Loading...') }}</p></div>`;

                try {
                    const res = await fetch("{{ route('singleview.fetchCreditCheck', $merchant->user_id) }}");
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

                            // Account Details Section
                            if (bank.data && bank.data.account && bank.data.account.length > 0) {
                                html += `
                                    <div class="border border-gray-200 rounded-lg shadow-sm">
                                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                            <h4 class="text-lg font-semibold text-gray-800">{{ translate('Account Details') }}</h4>
                                        </div>
                                        <div class="p-6 bg-white">
                                            ${bank.data.account.map(acc => renderAccountDetails(acc)).join('')}
                                        </div>
                                    </div>
                                `;
                            }

                            // Credit Analysis Section
                            if (bank.data && bank.data.account && bank.data.account[0]
                                ?.creditAnalysis) {
                                html += renderAnalysisSection(
                                    '{{ translate('Credit Analysis') }}',
                                    bank.data.account[0].creditAnalysis,
                                    'credit'
                                );
                            }

                            // Debit Analysis Section
                            if (bank.data && bank.data.account && bank.data.account[0]?.debitAnalysis) {
                                html += renderAnalysisSection(
                                    '{{ translate('Debit Analysis') }}',
                                    bank.data.account[0].debitAnalysis,
                                    'debit'
                                );
                            }

                            html += `</div></div>`;
                        });

                        // Overall Reports Section
                        if (data.overAllBalanceReport || data.overAllCreditsReport) {
                            html += `
                                <div class="border-t border-gray-200 pt-6">
                                    <h3 class="font-bold text-xl mb-4 pb-2 border-b border-gray-200 text-gray-800">{{ translate('Overall Reports') }}</h3>
                                    <div class="space-y-6">
                            `;

                            if (data.overAllBalanceReport) {
                                html += renderBalanceReport(data.overAllBalanceReport);
                            }

                            if (data.overAllCreditsReport) {
                                html += renderAnalysisSection(
                                    '{{ translate('Overall Credits Report') }}',
                                    data.overAllCreditsReport.monthlyReport,
                                    'credit'
                                );
                            }

                            html += `</div></div>`;
                        }

                        html += '</div>';
                        container.innerHTML = html;
                    } else {
                        const errorMessage = (data.payload && data.payload.message) ? data.payload.message :
                            '{{ translate('No credit report data available.') }}';
                        container.innerHTML =
                            `<div class="p-4"><p class="text-sm text-gray-600">${errorMessage}</p></div>`;
                    }
                } catch (err) {
                    console.error(err);
                    container.innerHTML =
                        `<div class="p-4"><p class="text-red-500">{{ translate('Failed to load credit report.') }}</p></div>`;
                } finally {
                    btn.removeAttribute('disabled');
                    btn.innerText = originalText;
                }
            }

            btn.addEventListener('click', fetchCreditReport);
        });
    </script>
@endpush
