<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="card-title">{{ translate('Accounts Information') }}</h3>
        <button id="fetchAccountsBtn" class="btn btn-sm btn-primary">
            {{ translate('Get Data') }}
        </button>
    </div>
    <div class="card-body" id="accountsContainer">
        <p class="text-sm text-gray-600">{{ translate('Click "Get Data" to fetch accounts information.') }}</p>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchAccountsBtn');
            const container = document.getElementById('accountsContainer');

            btn.addEventListener('click', function() {
                container.innerHTML = `<p class="text-gray-500">{{ translate('Loading...') }}</p>`;

                fetch("{{ route('singleview.fetchAccounts', $merchant->user_id) }}")
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.redirect_url) {
                            // Redirect to the bank for consent if a redirect URL is provided
                            container.innerHTML =
                                `<p class="text-sm text-gray-600">{{ translate('Redirecting to bank for authorization...') }}</p>`;
                            window.location.href = data.redirect_url;
                            return; // Stop further execution
                        }

                        if (data.success && data.payload) {
                            let html = '';

                            data.payload.forEach(bank => {
                                html += `<div class="mb-6">
                                <h4 class="font-bold text-lg mb-4">${bank.code ?? ''}</h4>`;

                                if (bank.data.account && bank.data.account.length > 0) {
                                    html +=
                                        `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">`;

                                    bank.data.account.forEach((acc, index) => {
                                        html += `
                                        <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                            <div class="bg-gray-100 px-4 py-3">
                                                <h3 class="text-base font-semibold text-gray-800">
                                                    {{ translate('Account') }} #${index + 1} — ${acc.nickname ?? acc.accountHolderName ?? '-'}
                                                </h3>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="table-auto w-full text-sm">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Account ID') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.accountId ?? '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Holder') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.accountHolderName ?? '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Type') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.accountType ?? '-'} / ${acc.accountSubType ?? '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Status') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">
                                                                ${acc.status 
                                                                    ? (acc.status === 'Active' 
                                                                        ? `<span class="badge badge-sm badge-outline badge-success">{{ translate('Active') }}</span>` 
                                                                        : `<span class="badge badge-sm badge-outline badge-secondary">${acc.status}</span>`) 
                                                                    : '-'}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Currency') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.currency ?? '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Opening Date') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.openingDate ? new Date(acc.openingDate).toLocaleDateString() : '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Maturity Date') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.maturityDate ? new Date(acc.maturityDate).toLocaleDateString() : '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('Description') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.description ?? '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4">{{ translate('IBAN Check') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">${acc.ibanCheck ?? '-'}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-gray-600 py-2 px-4 align-top">{{ translate('Identifiers') }}</td>
                                                            <td class="text-gray-900 py-2 px-4">
                                                                ${acc.accountIdentifiers && acc.accountIdentifiers.length > 0 
                                                                    ? `<ul class="list-disc ml-5">` + 
                                                                        acc.accountIdentifiers.map(i => `<li>${i.identificationType}: ${i.identification}</li>`).join('') +
                                                                      `</ul>`
                                                                    : `<span class="text-gray-400">-</span>`
                                                                }
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    `;
                                    });

                                    html += `</div>`; // grid
                                } else {
                                    html +=
                                        `<p class="text-gray-500">{{ translate('No accounts found for this bank.') }}</p>`;
                                }

                                html += `</div>`; // bank wrapper
                            });

                            container.innerHTML = html;
                        } else {
                            // This part handles a failed response without a redirect,
                            // such as the one you received for the 'Get Balance' call.
                            const errorMessage = (data.payload && data.payload.message) ? data.payload
                                .message : 'No account data available.';
                            container.innerHTML =
                                `<p class="text-sm text-gray-600">${errorMessage}</p>`;
                        }
                    })
                    .catch(err => {
                        container.innerHTML =
                            `<p class="text-red-500">{{ translate('Failed to load accounts.') }}</p>`;
                        console.error(err);
                    });
            });
        });
    </script>
@endpush
