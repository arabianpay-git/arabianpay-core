@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .border-blue-500 {
            border-color: #3B82F6 !important;
        }

        .border-purple-500 {
            border-color: #A855F7 !important;
        }
    </style>
@endpush

<div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-gray-100">
    <div
        class="px-6 py-4 flex flex-col md:flex-row justify-between items-start md:items-center bg-gray-50 border-b border-gray-200">
        <div class="mb-4 md:mb-0">
            <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                {{ translate('SIMAH Consumer Score Report') }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ translate('Fetch and display SIMAH consumer credit score and report.') }}
            </p>
        </div>
        <div class="flex items-center space-x-3 gap-1">
            <input type="hidden" id="consumerUserIdInput" name="user_id" value="{{ $customer->user_id }}">

            <select id="consumerReportSourceSelect"
                class="border border-gray-300 rounded-lg px-4 py-2 w-56 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="saved">{{ translate('Latest Saved Report') }}</option>
                <option value="new">{{ translate('Fetch New Report') }}</option>
            </select>

            <button id="fetchConsumerSimahBtn"
                class="btn btn-sm btn-primary bg-blue-600 hover:bg-blue-700 border-blue-600 px-4 py-2 text-white font-medium rounded-lg transition duration-150 disabled:opacity-60 disabled:cursor-not-allowed flex-shrink-0">
                {{ translate('Get Data') }}
            </button>
        </div>

    </div>

    <div id="consumerSimahContainer" class="min-h-[5rem]">
        <div class="p-6">
            <p class="text-sm text-gray-600">{{ translate('Click "Get Data" to fetch consumer score report.') }}</p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('fetchConsumerSimahBtn');
            const container = document.getElementById('consumerSimahContainer');
            const userIdInput = document.getElementById('consumerUserIdInput');
            const sourceSelect = document.getElementById('consumerReportSourceSelect');

            const routeFetch = "{{ route('customer.simah.consumer-score') }}";
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
                    // Assumes DD/MM/YYYY format based on common practice for this region/system,
                    // adjusting for Date constructor YYYY-MM-DD
                    const date = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);

                    if (isNaN(date.getTime())) { // Check if the date is invalid
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

            function renderKVTable(obj, keysOrder = null) {
                if (!obj || Object.keys(obj).length === 0) return '<div class="text-sm text-gray-500 p-4">-</div>';
                let rows = '';
                const keys = keysOrder ?? Object.keys(obj);
                keys.forEach(k => {
                    let v = obj[k];
                    if (v === null || v === undefined || v === '') v = '-';
                    else v = String(v);

                    rows += `<tr class="border-b border-gray-100 last:border-b-0">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700 bg-gray-50 w-1/3 md:w-60">${k}</td>
                        <td class="px-4 py-3 text-sm text-gray-800">${v}</td>
                    </tr>`;
                });
                return `<div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                    <table class="w-full divide-y divide-gray-200"><tbody>${rows}</tbody></table>
                </div>`;
            }

            // Reusable section header
            function sectionHeader(title, color = 'green') {
                return `<h4 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-${color}-500 pl-3 pt-2 pb-1">${title}</h4>`;
            }

            // Render Main Report Information (Updated for section title only)
            function renderMainReportInfo(mainData) {
                if (!mainData) return '';
                const map = {
                    'Report Status': mainData.reportStatus ?? '-',
                    'Report Date': mainData.reportDate ? formatDate(mainData.reportDate) : '-',
                    'Is Success': mainData.isSuccess !== undefined ? String(mainData.isSuccess) : '-',
                    'Message': mainData.message ?? '-'
                };
                return `<div>
                    ${sectionHeader('{{ translate('Main Report Information') }}', 'blue')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Report Details (Updated for section title only)
            function renderReportDetails(details) {
                if (!details) return '';
                const map = {
                    'Report Date': details.reportDate ? formatDate(details.reportDate) : '-',
                    'Enquiry Type': details.enquiryType ?? '-',
                    'Product Type': details.productType ?? '-',
                    'Enquiry Number': details.enquiryNumber ?? '-',
                    'Number of Applicants': details.numberOfApplicants ?? '-',
                    'Account Type': details.accountType ?? '-',
                    'Reference Number': details.referenceNumber ?? '-',
                    'Amount': details.amount ?? '-'
                };
                return `<div>
                    ${sectionHeader('{{ translate('Report Details') }}', 'blue')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Member Type
            function renderMemberType(memberType) {
                if (!memberType) return '';
                const map = {
                    'ID': memberType.id ?? '-',
                    'Code': memberType.code ?? '-',
                    'Name (Arabic)': memberType.nameAr ?? '-',
                    'Name': memberType.name ?? '-'
                };
                return `<div class="mt-8">
                    ${sectionHeader('Member Type', 'green')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Status
            function renderStatus(status) {
                if (!status) return '';
                const map = {
                    'ID': status.id ?? '-',
                    'Code': status.code ?? '-',
                    'Name (Arabic)': status.nameAr ?? '-',
                    'Name': status.name ?? '-'
                };
                return `<div class="mt-8">
                    ${sectionHeader('{{ translate('Status') }}', 'green')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Demographics
            function renderDemographics(demo, title) {
                if (!demo) return '';
                const map = {
                    'ID Number': demo.demIDNumber ?? '-',
                    'ID Expiry Date': demo.demIDExpiryDate ? formatDate(demo.demIDExpiryDate) : '-',
                    'Customer Name': demo.demCustomerName ?? '-',
                    'Family Name': demo.demFamilyName ?? '-',
                    'First Name': demo.demFirstName ?? '-',
                    'Second Name': demo.demSecondName ?? '-',
                    'Third Name': demo.demThirdName ?? '-',
                    'Customer Name (Arabic)': demo.demCustomerNameAr ?? '-',
                    'Family Name (Arabic)': demo.demFamilyNameAr ?? '-',
                    'First Name (Arabic)': demo.demFirstNameAr ?? '-',
                    'Second Name (Arabic)': demo.demSecondNameAr ?? '-',
                    'Third Name (Arabic)': demo.demThirdNameAr ?? '-',
                    'Date of Birth': demo.demDateOfBirth ? formatDate(demo.demDateOfBirth) : '-',
                    'Gender': demo.demGender ?? '-'
                };
                return `<div class="mt-4">
                    ${sectionHeader(title, 'blue')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render ID Type
            function renderIDType(idType) {
                if (!idType) return '';
                const map = {
                    'Type ID': idType.typeID ?? '-',
                    'Type Name (English)': idType.typeNameEN ?? '-',
                    'Type Name (Arabic)': idType.typeNameAR ?? '-',
                    'ID Type Code': idType.idTypeCode ?? '-'
                };
                return `<div class="mt-4">
                    ${sectionHeader('{{ translate('ID Type') }}', 'purple')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Applicant Type
            function renderApplicantType(applicantType) {
                if (!applicantType) return '';
                const map = {
                    'Applicant Type ID': applicantType.applicantTypeID ?? '-',
                    'Applicant Type Code': applicantType.applicantTypeCode ?? '-',
                    'Applicant Type Name (English)': applicantType.applicantTypeNameEN ?? '-',
                    'Applicant Type Name (Arabic)': applicantType.applicantTypeNameAR ?? '-'
                };
                return `<div class="mt-4">
                    ${sectionHeader('{{ translate('Applicant Type') }}', 'purple')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Marital Status
            function renderMaritalStatus(maritalStatus) {
                if (!maritalStatus) return '';
                const map = {
                    'Marital Status ID': maritalStatus.matrialStatusId ?? '-',
                    'Status Name (English)': maritalStatus.statusNameEN ?? '-',
                    'Status Name (Arabic)': maritalStatus.statusNameAR ?? '-',
                    'Marital Status Code': maritalStatus.maritalStatusCode ?? '-'
                };
                return `<div class="mt-4">
                    ${sectionHeader('{{ translate('Marital Status') }}', 'purple')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Nationality
            function renderNationality(nationality) {
                if (!nationality) return '';
                const map = {
                    'Country ID': nationality.couid ?? '-',
                    'Country Name (English)': nationality.couNameEN ?? '-',
                    'Country Name (Arabic)': nationality.couNameAR ?? '-',
                    'Country Code': nationality.couCode ?? '-'
                };
                return `<div class="mt-4">
                    ${sectionHeader('{{ translate('Nationality') }}', 'purple')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Score Section - New Top Priority Section
            function renderScore(scoreArray) {
                if (!scoreArray || scoreArray.length === 0) {
                    return `<div class="mt-4">
                        ${sectionHeader('{{ translate('Credit Score') }}', 'blue')}
                        <div class="text-sm text-gray-500 p-4 border border-gray-200 rounded-lg">{{ translate('No score information available.') }}</div>
                    </div>`;
                }

                let html = `<div class="mt-4">
                    ${sectionHeader('{{ translate('Credit Score') }}', 'blue')}`;

                scoreArray.forEach((scoreItem, index) => {
                    const score = scoreItem.score ?? 0;
                    const scoreCard = scoreItem.scoreCard || {};
                    const minScore = scoreItem.minimumScore ?? 0;
                    const maxScore = scoreItem.maximumScore ?? 0;
                    const scoreIndex = scoreItem.scoreIndex ?? 0;
                    const error = scoreItem.error;
                    const reasonCodes = scoreItem.reasonCodes || [];

                    // Determine score color based on value (Enhanced visual feedback)
                    let scoreColor = 'text-gray-600';
                    let bgColor = 'bg-gray-50';
                    let ringColor = 'ring-gray-300';
                    if (score > 700) {
                        scoreColor = 'text-green-600';
                        bgColor = 'bg-green-50';
                        ringColor = 'ring-green-300';
                    } else if (score > 600) {
                        scoreColor = 'text-yellow-600';
                        bgColor = 'bg-yellow-50';
                        ringColor = 'ring-yellow-300';
                    } else if (score > 0) {
                        scoreColor = 'text-red-600';
                        bgColor = 'bg-red-50';
                        ringColor = 'ring-red-300';
                    }

                    html += `<div class="border border-gray-200 rounded-xl p-6 ${bgColor} mb-6 shadow-lg ring-2 ${ringColor}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                            <div class="flex flex-col items-center justify-center border-r md:border-r-2 border-gray-200 p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Current Score</p>
                                <div class="text-6xl font-extrabold ${scoreColor} leading-none">${score}</div>
                                <div class="text-sm text-gray-600 mt-2 font-medium">${safe(scoreCard.scoreCardDescEn)}</div>
                            </div>
                            <div class="md:col-span-2 space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <p class="text-sm"><strong>Score Card Code:</strong> <span class="text-gray-700">${safe(scoreCard.scoreCardCode)}</span></p>
                                    <p class="text-sm"><strong>Range:</strong> <span class="text-gray-700">${minScore} - ${maxScore}</span></p>
                                    <p class="text-sm"><strong>Score Index:</strong> <span class="text-gray-700">${scoreIndex}</span></p>
                                    <p class="text-sm"><strong>Score Card (Arabic):</strong> <span class="text-gray-700">${safe(scoreCard.scoreCardDescAr)}</span></p>
                                </div>
                                ${error ? `<div class="text-sm text-red-700 bg-red-100 p-3 rounded-lg mt-3"><strong>Error:</strong> ${error}</div>` : ''}
                                <div class="mt-3"><strong>Reason Codes:</strong>
                                    <span class="text-sm text-gray-700">${reasonCodes.length > 0 ? reasonCodes.join(', ') : 'None'}</span>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });

                html += `</div>`;
                return html;
            }

            // Render Previous Enquiries (Updated to display two per row on medium screens and up)
            function renderPreviousEnquiries(prevEnquiries) {
                if (!prevEnquiries || prevEnquiries.length === 0) {
                    return `<div class="mt-8">
                        ${sectionHeader('{{ translate('Previous Enquiries') }}', 'blue')}
                        <div class="text-sm text-gray-500 p-4 border border-gray-200 rounded-lg">{{ translate('No previous enquiries.') }}</div>
                    </div>`;
                }

                let html = `<div class="mt-8">
                        ${sectionHeader('{{ translate('Previous Enquiries') }}', 'blue')}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">`; // Added grid layout here

                prevEnquiries.forEach((enq, index) => {
                    html += `<div class="p-5 border border-gray-200 rounded-xl bg-white shadow-md hover:shadow-lg transition duration-200">
                        <p class="text-md font-bold text-blue-600 mb-3 border-b border-gray-100 pb-2">Enquiry #${index + 1} - ${safe(enq.prevEnqDate)}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <p><strong>Date:</strong> ${safe(enq.prevEnqDate)}</p>
                            <p><strong>Member Code:</strong> ${safe(enq.prevEnqEnquirer?.memberCode)}</p>
                            <p><strong>Enquirer (English):</strong> ${safe(enq.prevEnqEnquirer?.memberNameEN)}</p>
                            <p><strong>Enquiry Type (English):</strong> ${safe(enq.preEnqType?.enqTypeDescriptionEn)}</p>
                            <p><strong>Product (English):</strong> ${safe(enq.prevEnqProductTypeDesc?.textEn)}</p>
                            <p><strong>Amount:</strong> ${safe(enq.prevEnqAmount)}</p>
                            <p><strong>Member Reference:</strong> ${safe(enq.prevEnqMemberRef)}</p>
                            <p><strong>Product Code:</strong> ${safe(enq.prevEnqProductTypeDesc?.code)}</p>
                            <p><strong>Other Reason:</strong> ${safe(enq.otherReason)}</p>
                        </div>
                    </div>`;
                });

                html += `</div></div>`;
                return html;
            }

            // Render Summary Information
            function renderSummaryInfo(summary) {
                if (!summary) return '';
                const map = {
                    'Active Credit Instruments': summary.summActiveCreditInstruments ?? '-',
                    'Defaults': summary.summDefaults ?? '-',
                    'Earliest Issue Date': summary.summEarliestIssueDate ? formatDate(summary
                        .summEarliestIssueDate) : '-',
                    'Total Limits': summary.summTotalLimits ?? '-',
                    'Total Guaranteed Limits': summary.summTotalGuaranteedLimits ?? '-',
                    'Total Liabilities': summary.summTotalLiablilites ?? '-',
                    'Total Guaranteed Liabilities': summary.summTotalGuaranteedLiablilites ?? '-',
                    'Total Defaults': summary.summTotalDefaults ?? '-',
                    'Current Delinquent Balance': summary.summCurrentDelinquentBalance ?? '-',
                    'Previous Enquiries': summary.summPreviousEnquires ?? '-',
                    'Previous Enquiries This Month': summary.summPreviousEnquiresThisMonth ?? '-',
                    'Guaranteed Credit Instruments': summary.summGuaranteedCreditInstruments ?? '-'
                };
                return `<div class="mt-8">
                    ${sectionHeader('{{ translate('Summary Information') }}', 'blue')}
                    ${renderKVTable(map, Object.keys(map))}
                </div>`;
            }

            // Render Disclaimer
            function renderDisclaimer(disclaimer) {
                if (!disclaimer) return '';
                const map = {
                    'Disclaimer (English)': disclaimer.discTextDescEn ?? '-',
                    'Disclaimer (Arabic)': disclaimer.discTextDescAr ?? '-'
                };
                return `<div class="mt-8">
                    ${sectionHeader('{{ translate('Disclaimer') }}', 'blue')}
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800 font-medium">${safe(disclaimer.discTextDescEn)}</p>
                        <p class="text-sm text-yellow-800 mt-2">${safe(disclaimer.discTextDescAr)}</p>
                    </div>
                </div>`;
            }

            // Helper for Source Badge
            function getSourceBadge(source) {
                let text = '';
                let color = '';

                if (source === 'database') {
                    text = 'SAVED';
                    color = 'bg-green-100 text-green-800 border-green-300';
                } else if (source === 'simah') {
                    text = 'LIVE SIMAH';
                    color = 'bg-blue-100 text-blue-800 border-blue-300';
                } else if (source === 'mock_error') {
                    text = 'MOCK DATA (Error)';
                    color = 'bg-red-100 text-red-800 border-red-300';
                } else {
                    return '';
                }

                return `<div class="flex justify-start mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${color} border">
                        {{ translate('Source') }}: ${text}
                    </span>
                </div>`;
            }

            // The main render function (logic is updated to put Score first)
            function renderAll(simahResp) {
                const payload = simahResp?.payload ?? simahResp ?? {};
                const source = simahResp?.source ?? 'unknown';

                // Extract the main data from the nested structure
                const mainData = payload?.data?.data ?? payload?.data ?? payload;

                if (!mainData || Object.keys(mainData).length === 0) {
                    return `<div class="p-6 text-sm text-gray-600">{{ translate('No consumer score data found in response.') }}</div>`;
                }

                let html = `<div class="px-6 py-8 space-y-10">`;

                // 1. Add the source badge
                html += getSourceBadge(source);

                // 2. Score section (TOP PRIORITY)
                html += renderScore(mainData.score);

                // --- Detailed Sections ---

                // 3. Main Report Info and Report Details (Side by side)
                html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                    <div>${renderMainReportInfo(mainData)}</div>
                    <div>${renderReportDetails(mainData.reportDetails)}</div>
                </div>`;


                // 4. Member Type & Status
                html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                    <div>${renderMemberType(mainData.reportDetails?.memberType)}</div>
                    <div>${renderStatus(mainData.reportDetails?.status)}</div>
                </div>`;

                // 5. Demographics sections
                html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>${renderDemographics(mainData.providedDemographicsInfo, 'Provided Demographics')}</div>
                    <div>${renderDemographics(mainData.availableDemographicsInfo, 'Available Demographics')}</div>
                </div>`;

                // 6. Nested Demographic details (ID Type, Applicant Type, Marital Status, Nationality)
                if (mainData.providedDemographicsInfo?.demIDType) {
                    html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>${renderIDType(mainData.providedDemographicsInfo.demIDType)}</div>
                        <div>${renderIDType(mainData.availableDemographicsInfo?.demIDType)}</div>
                    </div>`;
                }
                if (mainData.providedDemographicsInfo?.demApplicantType) {
                    html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>${renderApplicantType(mainData.providedDemographicsInfo.demApplicantType)}</div>
                        <div>${renderApplicantType(mainData.availableDemographicsInfo?.demApplicantType)}</div>
                    </div>`;
                }
                if (mainData.providedDemographicsInfo?.demMaritalStatus) {
                    html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>${renderMaritalStatus(mainData.providedDemographicsInfo.demMaritalStatus)}</div>
                        <div>${renderMaritalStatus(mainData.availableDemographicsInfo?.demMaritalStatus)}</div>
                    </div>`;
                }
                if (mainData.providedDemographicsInfo?.demNationality) {
                    html += `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>${renderNationality(mainData.providedDemographicsInfo.demNationality)}</div>
                        <div>${renderNationality(mainData.availableDemographicsInfo?.demNationality)}</div>
                    </div>`;
                }

                // 7. Previous enquiries (Uses updated function for 2 per row)
                html += renderPreviousEnquiries(mainData.prevEnquiries);

                // 8. Summary information
                html += renderSummaryInfo(mainData.summaryInfo);

                // 9. Disclaimer
                html += renderDisclaimer(mainData.disclerText);

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

            // --- Fetch Handler (Unchanged logic) ---
            async function fetchConsumerSimah() {
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
                    '{{ translate('Fetching new consumer score data...') }}' :
                    '{{ translate('Loading saved consumer score data...') }}';

                btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg> {{ translate('Loading...') }}`;
                container.innerHTML = `<div class="p-6"><p class="text-gray-500 flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg> ${loadingMessage}</p></div>`;

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
                        container.innerHTML = renderAll(data);
                    } else {
                        const errorMessage = data.message ? data.message :
                            '{{ translate('An error occurred or no consumer score data was available.') }}';
                        container.innerHTML =
                            `<div class="p-6"><p class="text-sm text-red-600">{{ translate('Error') }}: ${errorMessage}</p></div>`;
                    }
                } catch (err) {
                    console.error(err);
                    container.innerHTML =
                        `<div class="p-6"><p class="text-sm text-red-600">{{ translate('Failed to connect to SIMAH service.') }}</p></div>`;
                } finally {
                    btn.removeAttribute('disabled');
                    updateButtonText(); // Restore button text
                }
            }

            btn.addEventListener('click', fetchConsumerSimah);
        });
    </script>
@endpush
