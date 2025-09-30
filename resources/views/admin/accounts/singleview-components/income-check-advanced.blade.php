@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush
<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="card-title">{{ translate('Income Check (Advanced)') }}</h3>
        <div class="flex items-center space-x-2">
            <input id="incomeFromDate" type="date" class="form-input form-input-sm" />
            <input id="incomeToDate" type="date" class="form-input form-input-sm" />
            <select id="incomeTimeLine" class="form-select form-select-sm">
                <option value="byDay">{{ translate('By Day') }}</option>
                <option value="byWeek">{{ translate('By Week') }}</option>
                <option value="byMonth" selected>{{ translate('By Month') }}</option>
            </select>
            <button id="fetchIncomeBtn" class="btn btn-sm btn-primary">
                {{ translate('Get Income Data') }}
            </button>
        </div>
    </div>

    <div class="card-body" id="incomeContainer">
        <p class="text-sm text-gray-600">{{ translate('Click "Get Income Data" to fetch income check results.') }}</p>
    </div>
</div>

{{-- prettier-ignore-start --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchIncomeBtn');
            const container = document.getElementById('incomeContainer');

            // Translation constants
            const T = {
                LOADING: "{{ translate('Loading...') }}",
                REDIRECTING: "{{ translate('Redirecting to bank for authorization...') }}",
                NO_INCOME_INSIGHTS: "{{ translate('No income insights returned by the API.') }}",
                NO_DATA: "{{ translate('No advanced income data available.') }}",
                NO_SOURCES: "{{ translate('No Sources') }}",
                NO_MONTHLY_DATA: "{{ translate('No monthly data.') }}",
                MONTH: "{{ translate('Month') }}",
                AMOUNT: "{{ translate('Amount') }}",
                COUNT: "{{ translate('Count') }}",
                ACCOUNT: "{{ translate('Account') }}",
                ACCOUNT_ID: "{{ translate('Account ID') }}",
                HOLDER: "{{ translate('Holder') }}",
                TYPE: "{{ translate('Type') }}",
                STATUS: "{{ translate('Status') }}",
                CURRENCY: "{{ translate('Currency') }}",
                OPENING_DATE: "{{ translate('Opening Date') }}",
                MATURITY_DATE: "{{ translate('Maturity Date') }}",
                DESCRIPTION: "{{ translate('Description') }}",
                INCOME_SOURCES: "{{ translate('Income Sources') }}",
                LAST_3: "{{ translate('Last 3 Months') }}",
                LAST_6: "{{ translate('Last 6 Months') }}",
                LAST_9: "{{ translate('Last 9 Months') }}",
                LAST_12: "{{ translate('Last 12 Months') }}",
                NO_TRANSACTIONS: "{{ translate('No transactions found for this account.') }}",
                TRANSACTIONS: "{{ translate('Transactions') }}",
                DATE: "{{ translate('Date') }}",
                REFERENCE: "{{ translate('Reference') }}",
                INFO: "{{ translate('Info') }}",
                BALANCE: "{{ translate('Balance') }}",
            };

            // Helpers
            function safe(v, fallback = '-') {
                return (v === null || v === undefined || v === '') ? fallback : v;
            }

            function money(v, currency) {
                if (v === null || v === undefined || v === 0) return '0.00 ' + (currency ? currency : '');
                const formatted = parseFloat(v).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                return `${formatted} ${currency ? currency : ''}`.trim();
            }

            function formatDate(d) {
                try {
                    if (!d) return '-';
                    const date = new Date(d);
                    if (isNaN(date)) return d;
                    return date.toLocaleDateString('en-US', {
                        year: '2-digit',
                        month: 'short',
                        day: 'numeric'
                    });
                } catch (e) {
                    return d;
                }
            }

            function getRiskColor(risk) {
                if (!risk) return 'gray';
                if (risk.includes('Very High')) return 'red';
                if (risk.includes('High')) return 'orange';
                if (risk.includes('Medium')) return 'yellow';
                if (risk.includes('Low')) return 'green';
                return 'gray';
            }

            function getApplicantColor(type) {
                if (!type) return 'gray';
                if (type === 'Reject') return 'red';
                if (type === 'Approved') return 'green';
                if (type === 'Pending') return 'yellow';
                return 'gray';
            }

            function renderMonthlyList(monthlyList) {
                if (!Array.isArray(monthlyList) || monthlyList.length === 0) {
                    return `<p class="text-gray-500 text-sm">${T.NO_MONTHLY_DATA}</p>`;
                }
                const rows = monthlyList.map(m => {
                    const month = safe(m.month);
                    const amount = safe(m.value ?? m.amount);
                    const currency = m.currency ?? '';
                    const count = safe(m.count);
                    return `<tr class="border-b last:border-b-0 hover:bg-gray-100 transition-colors">
                        <td class="py-1 px-2 text-xs text-gray-600">${month}</td>
                        <td class="py-1 px-2 text-xs text-gray-900">${money(amount, currency)}</td>
                        <td class="py-1 px-2 text-xs text-gray-700">${count}</td>
                    </tr>`;
                }).join('');

                return `<div class="overflow-x-auto">
                    <table class="table-auto w-full text-xs">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="text-left py-1 px-2">${T.MONTH}</th>
                                <th class="text-left py-1 px-2">${T.AMOUNT}</th>
                                <th class="text-left py-1 px-2">${T.COUNT}</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>`;
            }

            function periodCardHtml(periodLabel, periodObj) {
                if (!periodObj) {
                    return `<div class="p-3 border border-gray-100 rounded text-sm text-gray-500 bg-gray-100">
                        <div class="font-medium">${periodLabel}</div>
                        <div class="mt-1 text-xs text-gray-400">${T.NO_MONTHLY_DATA}</div>
                    </div>`;
                }
                const count = safe(periodObj.totalCount ?? periodObj.count);
                const value = safe(periodObj.value);
                const currency = periodObj.currency ?? '';
                const monthlyList = periodObj.monthlyList ?? [];

                return `<div class="border border-gray-200 rounded-lg p-3 bg-white shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <div class="text-xs font-medium text-gray-600">${periodLabel}</div>
                            <div class="text-lg font-bold text-gray-900">${money(value, currency)}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">${T.COUNT}</div>
                            <div class="text-lg font-semibold text-blue-600">${count}</div>
                        </div>
                    </div>
                    ${renderMonthlyList(monthlyList)}
                </div>`;
            }

            // Render transactions table (minimal design)
            function renderTransactions(transactions) {
                if (!Array.isArray(transactions) || transactions.length === 0) {
                    return `<h5 class="font-semibold text-sm mt-4 mb-2">${T.TRANSACTIONS}</h5>
                            <p class="text-gray-500 text-xs">${T.NO_TRANSACTIONS}</p>`;
                }
                const rows = transactions.map(t => {
                    const amountObj = t.amount ?? t.chargeAmount ?? null;
                    const amount = safe(amountObj?.amount);
                    const currency = amountObj?.currency ?? '';
                    const info = safe(t.transactionInformation ?? t.merchantDetails?.merchantName);
                    const indicator = t.creditDebitIndicator === 'KSAOB.Credit' ? 'text-green-600' : 'text-red-600';
                    const reference = safe(t.transactionReference ?? t.transactionId);

                    return `<div class="p-2 border-b last:border-b-0 hover:bg-gray-100 transition-colors">
                        <div class="flex justify-between items-start text-xs">
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="font-medium truncate">${info}</div>
                                <div class="text-gray-500">${formatDate(t.bookingDateTime ?? t.transactionDateTime)}</div>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <div class="font-semibold ${indicator}">${money(amount, currency)}</div>
                                <div class="text-gray-400 text-xs mt-1">Ref: ${reference}</div>
                            </div>
                        </div>
                    </div>`;
                }).join('');

                return `<h5 class="font-semibold text-sm mt-4 mb-2">${T.TRANSACTIONS}</h5>
                        <div class="border border-gray-200 rounded-lg max-h-60 overflow-y-auto bg-white shadow-sm">${rows}</div>`;
            }

            // Build Income Summary section - MOVED TO TOP
            function renderIncomeSummary(insights) {
                if (!insights) {
                    return `<div class="p-4 border border-gray-100 rounded text-sm text-gray-500">${T.NO_INCOME_INSIGHTS}</div>`;
                }

                const riskColor = getRiskColor(insights.creditLendRisk);
                const applicantColor = getApplicantColor(insights.applicantType);

                return `
                    <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden mt-4">
                        <div class="bg-blue-600 px-4 py-3">
                            <h4 class="text-lg font-semibold text-white">{{ translate('Overall Income Analysis') }}</h4>
                        </div>
                        <div class="p-4 bg-white">
                            <!-- Main Metrics Grid -->
                            <div class="grid lg:grid-cols-5 gap-y-3 lg:gap-4 items-stretch mt-4">
                                <div class="border border-gray-200 rounded-lg p-4 bg-white text-center shadow-sm">
                                    <div class="text-xs text-gray-500 mb-2">{{ translate('Income Score') }}</div>
                                    <div class="text-3xl font-bold text-blue-600">${safe(insights.incomeScore, 'N/A')}</div>
                                    <div class="text-xs text-gray-400 mt-1">out of 100</div>
                                </div>
                                <div class="border border-gray-200 rounded-lg p-4 bg-white text-center shadow-sm">
                                    <div class="text-xs text-gray-500 mb-2">{{ translate('Applicant Type') }}</div>
                                    <div class="text-xl font-semibold ${applicantColor === 'red' ? 'text-red-600' : applicantColor === 'green' ? 'text-green-600' : 'text-yellow-600'}">${safe(insights.applicantType, 'N/A')}</div>
                                    <div class="text-xs text-gray-400 mt-1">Assessment</div>
                                </div>
                                <div class="border border-gray-200 rounded-lg p-4 bg-white text-center shadow-sm">
                                    <div class="text-xs text-gray-500 mb-2">{{ translate('Credit Lend Risk') }}</div>
                                    <div class="text-xl font-semibold ${riskColor === 'red' ? 'text-red-600' : riskColor === 'orange' ? 'text-orange-600' : riskColor === 'green' ? 'text-green-600' : 'text-yellow-600'}">${safe(insights.creditLendRisk, 'N/A')}</div>
                                    <div class="text-xs text-gray-400 mt-1">Risk Level</div>
                                </div>
                                <div class="border border-gray-200 rounded-lg p-4 bg-white text-center shadow-sm">
                                    <div class="text-xs text-gray-500 mb-2">{{ translate('Period of Months') }}</div>
                                    <div class="text-3xl font-bold text-purple-600">${safe(insights.periodOfMonths, 'N/A')}</div>
                                    <div class="text-xs text-gray-400 mt-1">Analysis Period</div>
                                </div>
                                <div class="border border-gray-200 rounded-lg p-4 bg-white text-center shadow-sm">
                                    <div class="text-xs text-gray-500 mb-2">{{ translate('Currency') }}</div>
                                    <div class="text-xl font-semibold text-gray-700">${safe(insights.currency, 'N/A')}</div>
                                    <div class="text-xs text-gray-400 mt-1">Default Currency</div>
                                </div>
                            </div>

                            <!-- Description Card -->
                            ${insights.description ? `
                                <div class="border border-gray-200 rounded-lg p-4 bg-yellow-50 shadow-sm mt-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h5 class="text-sm font-semibold text-yellow-800 mb-1">{{ translate('Assessment Description') }}</h5>
                                            <p class="text-sm text-yellow-700">${insights.description}</p>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Extra Info Section -->
                            <div class="grid md:grid-cols-2 gap-4 mt-4">
                                <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    <div class="text-xs text-gray-500">{{ translate('Number of Accounts') }}</div>
                                    <div class="text-lg font-semibold text-gray-800">${safe(insights.noOfAccounts, 'N/A')}</div>
                                </div>
                                <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                    <div class="text-xs text-gray-500">{{ translate('Income Frequency (Last Month)') }}</div>
                                    <div class="text-sm text-gray-800">
                                        ${Object.entries(insights.incomeFrequency?.lastMonth || {}).map(([k, v]) => `<div>${k}: <span class="font-semibold">${v}</span></div>`).join('')}
                                    </div>
                                </div>
                            </div>

                            <!-- Attribute Statistics -->
                            <div class="mt-4">
                                <h5 class="text-sm font-semibold mb-2">{{ translate('Attributes Statistics') }}</h5>
                                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    ${Object.values(insights.attibutesStatistics || {}).map(attr => `
                                        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                            <div class="text-xs text-gray-500">${attr.attributeDetails}</div>
                                            <div class="text-lg font-semibold text-gray-800">${safe(attr.attributeScore, 'N/A')} / ${safe(attr.maxScoreValue, 'N/A')}</div>
                                            ${attr.amount ? `<div class="text-xs text-gray-600">Amount: ${money(attr.amount, insights.currency)}</div>` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Income Categories -->
                            ${Array.isArray(insights.incomeCategories) && insights.incomeCategories.length ? `
                                <div class="mt-4">
                                    <h5 class="text-sm font-semibold mb-2">{{ translate('Income Categories') }}</h5>
                                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        ${insights.incomeCategories.map(cat => `
                                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                                                <div class="text-xs text-gray-500">${cat.attributeDetails}</div>
                                                <div class="text-lg font-semibold text-gray-800">${cat.weightagePercentage.toFixed(2)}%</div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }

            // Build Income Sources section
            function renderIncomeSources(insights) {
                if (!insights) return '';

                const sourceList = Array.isArray(insights.incomeSourceList) ? insights.incomeSourceList : [];

                if (!sourceList.length) return '';

                return `
                    <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden mt-4">
                        <div class="bg-green-600 px-4 py-3">
                            <h4 class="text-lg font-semibold text-white">${T.INCOME_SOURCES}</h4>
                        </div>
                        <div class="p-4 bg-white">
                            ${sourceList.map(src => {
                                const label = safe(src.label ?? src.name, 'Unknown Source');
                                const val = src.value ?? {};
                                const p3 = val.lastThreeMonths ?? val.last_3_months ?? null;
                                const p6 = val.lastSixMonths ?? val.last_6_months ?? null;
                                const p9 = val.lastNineMonths ?? val.last_9_months ?? null;
                                const p12 = val.lastTweleveMonths ?? val.lastTwelveMonths ?? val.last_12_months ?? null;
                                const currency = val.currency ?? insights.currency ?? '';

                                // prettier-ignore
                                return `<div class="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-center mb-3">
                                        <h5 class="text-lg font-semibold text-gray-800">${label}</h5>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${currency}</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        ${periodCardHtml(T.LAST_3, p3)}
                                        ${periodCardHtml(T.LAST_6, p6)}
                                        ${periodCardHtml(T.LAST_9, p9)}
                                        ${periodCardHtml(T.LAST_12, p12)}
                                    </div>
                                </div>`;
                            }).join('')}
                        </div>
                    </div>
                `;
            }

            // Click handler
            btn.addEventListener('click', function() {
                container.innerHTML = `<p class="text-gray-500">${T.LOADING}</p>`;
                btn.setAttribute('disabled', 'disabled');
                const originalText = btn.innerText;
                btn.innerText = T.LOADING;

                const fromDate = document.getElementById('incomeFromDate').value;
                const toDate = document.getElementById('incomeToDate').value;
                const timeLine = document.getElementById('incomeTimeLine').value;

                const url = new URL(
                    "{{ route('singleview.fetchIncomeCheckAdvanced', $merchant->user_id) }}",
                    window.location.origin
                );
                if (fromDate) url.searchParams.set('fromDate', fromDate);
                if (toDate) url.searchParams.set('toDate', toDate);
                if (timeLine) url.searchParams.set('timeLine', timeLine);

                fetch(url.toString())
                    .then(res => res.json())
                    .then(data => {
                        console.log('income-check-advanced response:', data);

                        try {
                            if (data && data.redirect_url) {
                                container.innerHTML = `<p class="text-sm text-gray-600">${T.REDIRECTING}</p>`;
                                window.location.href = data.redirect_url;
                                return;
                            }

                            if (!data || !data.success || !data.payload) {
                                const errMsg = (data && data.payload && data.payload.message) ?
                                    data.payload.message : T.NO_DATA;
                                container.innerHTML = `<p class="text-sm text-gray-600">${errMsg}</p>`;
                                return;
                            }

                            // Build UI from payload array
                            let finalHtml = '';
                            data.payload.forEach((bank, bankIndex) => {
                                const bankCode = bank.code ?? 'Unknown Bank';
                                const dataObj = bank.data ?? {};

                                finalHtml += `<div class="mb-8">
                                    <h3 class="font-bold text-xl mb-3 border-b pb-2 text-gray-800">${bankCode}</h3>`;

                                // INCOME SUMMARY BLOCK - MOVED TO TOP
                                const incomeInsights = bank.incomeInsights || dataObj.incomeInsights;
                                console.log('Income Insights Data:', incomeInsights);

                                if (incomeInsights) {
                                    finalHtml += renderIncomeSummary(incomeInsights);
                                }

                                // ACCOUNTS BLOCK
                                if (Array.isArray(dataObj.account) && dataObj.account.length) {
                                    finalHtml += `<div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden mt-4">
                                        <div class="bg-indigo-600 px-4 py-3">
                                            <h4 class="text-lg font-semibold text-white">{{ translate('Bank Accounts & Transactions') }}</h4>
                                        </div>
                                        <div class="p-4 bg-white">
                                            ${dataObj.account.map((acc, idx) => {
                                                const iban = acc.accountIdentifiers?.find(i =>
                                                    i.identificationType === 'KSAOB.IBAN'
                                                )?.identification ?? '-';

                                                return `<div class="border border-gray-200 rounded-lg mb-4 last:mb-0 shadow-sm hover:shadow-md transition-shadow">
                                                    <div class="bg-gray-100 px-4 py-3 border-b">
                                                        <h4 class="text-base font-semibold text-gray-800">${T.ACCOUNT} #${idx+1} — ${safe(acc.nickname ?? acc.accountHolderName)}</h4>
                                                    </div>
                                                    <div class="p-4">
                                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">${T.ACCOUNT_ID}</div>
                                                                <div class="font-medium text-gray-900">${safe(acc.accountId)}</div>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">${T.HOLDER}</div>
                                                                <div class="font-medium text-gray-900">${safe(acc.accountHolderName)}</div>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">${T.TYPE}</div>
                                                                <div class="font-medium text-gray-900">${safe(acc.accountType)}</div>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">${T.STATUS}</div>
                                                                <div class="font-medium ${acc.status === 'Active' ? 'text-green-600' : 'text-red-600'}">${safe(acc.status)}</div>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">${T.CURRENCY}</div>
                                                                <div class="font-medium text-gray-900">${safe(acc.currency)}</div>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">{{ translate('IBAN') }}</div>
                                                                <div class="font-medium text-gray-900">${iban}</div>
                                                            </div>
                                                            <div class="space-y-1">
                                                                <div class="text-xs text-gray-500">${T.OPENING_DATE}</div>
                                                                <div class="font-medium text-gray-900">${formatDate(acc.openingDate)}</div>
                                                            </div>
                                                        </div>
                                                        ${renderTransactions(acc.transactions)}
                                                    </div>
                                                </div>`;
                                            }).join('')}
                                        </div>
                                    </div>`;
                                } else {
                                    finalHtml += `<p class="text-gray-500 mt-4">{{ translate('No accounts found for this bank.') }}</p>`;
                                }

                                // INCOME SOURCES BLOCK
                                if (incomeInsights) {
                                    finalHtml += renderIncomeSources(incomeInsights);
                                }

                                finalHtml += `</div>`; // end of bank block
                            });

                            container.innerHTML = finalHtml;

                        } catch (e) {
                            console.error('Error processing data:', e);
                            container.innerHTML = `<p class="text-red-500">{{ translate('An error occurred while processing the data.') }}</p>`;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        container.innerHTML = `<p class="text-red-500">{{ translate('Failed to load income data.') }}</p>`;
                    })
                    .finally(() => {
                        btn.removeAttribute('disabled');
                        btn.innerText = originalText;
                    });
            });
        });
    </script>
@endpush
{{-- prettier-ignore-end --}}
