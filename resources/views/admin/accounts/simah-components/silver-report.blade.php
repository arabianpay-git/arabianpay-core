@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush
<div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-200">
    <div class="px-6 py-4 flex justify-between items-center bg-gray-50 border-b border-gray-200">
        <div>
            <h3 class="text-xl font-semibold text-gray-800">{{ translate('SIMAH Report') }}</h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ translate('Fetch and display a SIMAH commercial silver report.') }}
            </p>
        </div>
        <div class="flex items-center space-x-3 gap-1">

            <input type="hidden" id="userIdInput" name="user_id" value="{{ $customer->user_id }}">

            <select id="reportSourceSelect"
                class="border border-gray-300 rounded-lg px-4 py-2 w-56 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="saved">{{ translate('Latest Saved Report') }}</option>
                <option value="new">{{ translate('Fetch New Report') }}</option>
            </select>

            <button id="fetchSimahBtn"
                class="btn btn-sm btn-primary bg-blue-600 hover:bg-blue-700 border-blue-600 px-4 py-2 text-white font-medium rounded-lg transition duration-150 disabled:opacity-60 disabled:cursor-not-allowed">
                {{ translate('Get Data') }}
            </button>
        </div>
    </div>

    <div id="simahContainer" class="min-h-[10rem]">
        <div class="p-6">
            <p class="text-sm text-gray-600">{{ translate('Click "Get Data" to fetch SIMAH report.') }}</p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchSimahBtn');
            const container = document.getElementById('simahContainer');
            const userIdInput = document.getElementById('userIdInput');
            const sourceSelect = document.getElementById('reportSourceSelect');

            // NOTE: Update these with your actual route and CSRF token handling.
            const routeFetch = "{{ route('customer.simah.fetch') }}";
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ??
                '{{ csrf_token() }}';

            function safe(v) {
                if (v === null || v === undefined || v === '') return '-';
                return v;
            }

            function formatDate(dateString) {
                if (!dateString) return '-';
                try {
                    const parts = dateString.split(' ')[0].split('/');
                    const date = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);

                    if (isNaN(date)) {
                        return dateString;
                    }

                    return date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                } catch (e) {
                    return dateString;
                }
            }

            // A clean, Tailwind-based Key-Value Table renderer (Unchanged)
            function renderKVTable(obj, keysOrder = null) {
                if (!obj || Object.keys(obj).length === 0) return '<div class="text-sm text-gray-500 p-4">-</div>';
                let rows = '';
                const keys = keysOrder ?? Object.keys(obj);
                keys.forEach(k => {
                    let v = obj[k];
                    if (v === null || v === undefined || v === '') v = '-';
                    else if (typeof v === 'object') v =
                        `<span class="text-gray-800">${JSON.stringify(v)}</span>`;
                    else v = `<span class="text-gray-800">${v}</span>`;

                    rows += `<tr class="border-b border-gray-100 last:border-b-0">
                        <td class="px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 w-1/3 md:w-48">${k}</td>
                        <td class="px-4 py-3 text-sm text-gray-800">${v}</td>
                     </tr>`;
                });
                return `<div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                    <table class="w-full divide-y divide-gray-200"><tbody>${rows}</tbody></table>
                </div>`;
            }

            // --- Render Sections (Unchanged) ---
            function renderReportDetail(r) {
                if (!r) return '';
                const map = {
                    'Report Date': r.reportDate ? formatDate(r.reportDate) : '-',
                    'Enquiry Number': r.enquiryNumber ?? '-',
                    'Reference Number': r.referenceNumber ?? '-',
                    'Amount': r.amount ?? '-',
                    'Product': r.productType?.textEn ?? r.productType?.textAr ?? '-',
                    'Enquiry Type': r.enquiryType?.enqTypeDescriptionEn ?? '-',
                    'Credit Facility': r.creditFacility?.nameEn ?? '-',
                    'Government Guaranteed': r.governmentGuaranteed ?? '-'
                };
                return `<div class="mt-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-l-4 border-blue-500 pl-3">{{ translate('Report Detail') }}</h4>
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            function renderDemographics(d) {
                if (!d) return '';
                const map = {
                    'Name (EN)': d.name ?? '-',
                    'Name (AR)': d.nameAr ?? '-',
                    'Date': d.date ? formatDate(d.date) : '-',
                    'Issuer': d.issuer?.idIssuerNameEN ?? d.issuer?.idIsuuerNameAR ?? '-',
                    'City': d.isscit?.cityNameEN ?? d.isscit?.cityNameAR ?? '-',
                    'Expiry Date': d.expDate ? formatDate(d.expDate) : '-',
                    'Legal Form': d.legalForm?.legalFormNameEN ?? '-',
                    'Business Activity': d.businessActivity?.industryNameEN ?? '-',
                    'National No': d.nationalNo ?? '-'
                };
                return `<div class="mt-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-l-4 border-blue-500 pl-3">{{ translate('Demographics') }}</h4>
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            function renderPreviousEnquiries(list) {
                if (!list || list.length === 0) {
                    return `<div class="mt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 border-l-4 border-blue-500 pl-3">{{ translate('Previous Enquiries') }}</h4>
                        <div class="text-sm text-gray-500 p-4 border border-gray-200 rounded-lg">{{ translate('No previous enquiries.') }}</div>
                    </div>`;
                }

                let html = `<div class="mt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 border-l-4 border-blue-500 pl-3">{{ translate('Previous Enquiries') }}</h4>
                        <div class="space-y-4">`;
                list.forEach(enq => {
                    html += `<div class="p-4 border border-gray-200 rounded-lg bg-white shadow-sm hover:shadow-md transition duration-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-4 text-sm">
                            <div class="flex justify-between md:block"><span class="text-gray-600 font-medium">{{ translate('Date') }}:</span> <span class="text-gray-800">${safe(enq.enquiryDate)}</span></div>
                            <div class="flex justify-between md:block"><span class="text-gray-600 font-medium">{{ translate('Enquirer') }}:</span> <span class="text-gray-800">${safe(enq.enquirer?.memberNameEN ?? enq.enquirer?.memberNameAR)}</span></div>
                            <div class="flex justify-between md:block"><span class="text-gray-600 font-medium">{{ translate('Product') }}:</span> <span class="text-gray-800">${safe(enq.product?.textEn ?? enq.product?.textAr)}</span></div>
                            <div class="flex justify-between md:block"><span class="text-gray-600 font-medium">{{ translate('Amount') }}:</span> <span class="text-gray-800">${safe(enq.amount)}</span></div>
                            <div class="col-span-full text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100">{{ translate('Member Reference') }}: ${safe(enq.memberReference)}</div>
                        </div>
                    </div>`;
                });
                html += `</div></div>`;
                return html;
            }

            function renderFacilities(f) {
                if (!f) return '';
                const levelOne = f.levelOneCISummary || [];
                const levelTwo = f.levelTwoCISummary || [];

                let html =
                    `<div class="mt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3 border-l-4 border-blue-500 pl-3">{{ translate('Facilities Summary') }}</h4>`;

                if (levelOne.length) {
                    html +=
                        `<div class="mb-6"><h5 class="text-base font-medium text-gray-700 mb-2 border-l-2 border-gray-400 pl-2">{{ translate('Level One') }}</h5>`;
                    levelOne.forEach(item => {
                        html += `<div class="border border-gray-200 rounded-lg mb-3 shadow-sm">
                            ${renderKVTable({
                                'Creditor': item.creditor?.memberNameEN ?? item.creditor?.memberNameAR ?? '-',
                                'Global Limit': item.globalLimit ?? '-',
                                'Utilisation': item.utilisation ?? '-',
                                'Past Due': item.pastDue ?? '-',
                                'Status': item.status?.name ?? '-',
                                'Collateral': item.collateral ?? '-'
                            }, ['Creditor','Global Limit','Utilisation','Past Due','Status','Collateral'])}
                         </div>`;
                    });
                    html += `</div>`;
                }

                if (levelTwo.length) {
                    html +=
                        `<div><h5 class="text-base font-medium text-gray-700 mb-2 border-l-2 border-gray-400 pl-2">{{ translate('Level Two') }}</h5>`;
                    levelTwo.forEach(item => {
                        html += `<div class="border border-gray-200 rounded-lg mb-3 shadow-sm">
                            ${renderKVTable({
                                'Creditor': item.creditor?.memberNameEN ?? '-',
                                'Funded Limit': item.fundedLimit ?? '-',
                                'Funded Utilisation': item.fundedUtilisation ?? '-',
                                'Non-Funded Utilisation': item.nonFundedUtilisation ?? '-'
                            }) }
                         </div>`;
                    });
                    html += `</div>`;
                }

                html += `</div>`;
                return html;
            }

            function renderCreditInstrumentSummary(c) {
                if (!c) return '';
                const map = {
                    'Total Limit': c.totalLimit ?? '-',
                    'Total Utilization': c.totalUtilization ?? '-',
                    'Total Past Due': c.totalPastDue ?? '-',
                    'Funded': c.funded ?? '-',
                    'Non Funded': c.nonFunded ?? '-'
                };
                return `<div class="mt-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-3 border-l-4 border-blue-500 pl-3">{{ translate('Credit Instrument Summary') }}</h4>
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // --- Helper for Source Badge ---
            function getSourceBadge(source) {
                let text = '';
                let color = '';

                if (source === 'database') {
                    text = 'SAVED';
                    color = 'bg-green-100 text-green-700';
                } else if (source === 'simah') {
                    text = 'LIVE SIMAH';
                    color = 'bg-blue-100 text-blue-700';
                } else if (source === 'mock_error') {
                    text = 'MOCK DATA (Error Handled)';
                    color = 'bg-red-100 text-red-700';
                } else {
                    return '';
                }

                return `<span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium ${color} mt-4 mb-4">
                    {{ translate('Source') }}: ${text}
                </span>`;
            }


            function renderAll(simahResp) {
                // Safely extract the core data payload
                const payload = simahResp?.payload ?? simahResp ?? {};
                const source = simahResp?.source ?? 'unknown';

                const inner = payload?.data?.data?.companyResponseJson ?? payload?.data?.companyResponseJson ??
                    payload?.companyResponseJson ?? payload;

                if (!inner || Object.keys(inner).length === 0) {
                    return `<div class="p-6 text-sm text-gray-600">{{ translate('No SIMAH data found in response.') }}</div>`;
                }

                let html = `<div class="px-6 py-8 space-y-10">`;

                // Add the source badge
                html += getSourceBadge(source);

                // report + demographics (side-by-side on large screens)
                html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    ${renderReportDetail(inner.reportDetail)}
                    ${renderDemographics(inner.demographicsDetail)}
                 </div>`;

                // previous enquiries + credit summary (side-by-side on large screens)
                html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    ${renderPreviousEnquiries(inner.previousEnquiries)}
                    ${renderCreditInstrumentSummary(inner.creditInstrumentSummary)}
                 </div>`;

                // facilities (full width)
                html += renderFacilities(inner.facilitiesSummaryLevel);

                html += `</div>`;
                return html;
            }

            // --- UI Update Handler ---

            function updateButtonText() {
                if (sourceSelect.value === 'new') {
                    btn.innerText = '{{ translate('Fetch New Report') }}';
                } else {
                    btn.innerText = '{{ translate('View Saved Report') }}';
                }
            }

            updateButtonText();
            sourceSelect.addEventListener('change', updateButtonText);


            // --- Fetch Handler ---

            async function fetchSimah() {
                const userId = userIdInput.value?.trim();
                const selectedSource = sourceSelect.value;
                const fetchLatest = selectedSource === 'new';

                if (!userId) {
                    container.innerHTML =
                        `<div class="p-6"><p class="text-sm text-red-600">{{ translate('User ID is missing.') }}</p></div>`;
                    return;
                }

                btn.setAttribute('disabled', 'disabled');
                const originalText = btn.innerText;

                const loadingMessage = fetchLatest ?
                    '{{ translate('Fetching new SIMAH data...') }}' :
                    '{{ translate('Loading saved SIMAH data...') }}';

                btn.innerText = '{{ translate('Loading...') }}';
                container.innerHTML =
                    `<div class="p-6"><p class="text-gray-500 flex items-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> ${loadingMessage}</p></div>`;

                try {
                    const res = await fetch(routeFetch, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            latest: fetchLatest
                        })
                    });

                    const data = await res.json();

                    if (data && data.success && data.payload) {
                        // Success response, including the new 'source' field
                        container.innerHTML = renderAll(data);
                    } else {
                        const errorMessage = data.message ? data.message :
                            '{{ translate('An error occurred or no SIMAH data was available.') }}';
                        container.innerHTML =
                            `<div class="p-6"><p class="text-sm text-red-600">{{ translate('Error') }}: ${errorMessage}</p></div>`;
                    }
                } catch (err) {
                    console.error(err);
                    container.innerHTML =
                        `<div class="p-6"><p class="text-sm text-red-600">{{ translate('Failed to connect to SIMAH service.') }}</p></div>`;
                } finally {
                    btn.removeAttribute('disabled');
                    updateButtonText(); // Restore appropriate button text after loading
                }
            }

            btn.addEventListener('click', fetchSimah);
        });
    </script>
@endpush
