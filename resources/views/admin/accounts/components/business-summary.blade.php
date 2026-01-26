<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-3">
            <h3 class="font-bold text-slate-800">{{ translate('Business Summary') }}</h3>
            <span class="text-[10px] text-slate-400 ml-3">
                {{ translate('Updated') }} {{ $merchant->updated_at?->format('M d, Y') ?? now()->format('M Y') }}
            </span>
        </div>

        @if (Auth::user()->user_type === 'admin' || (Auth::user()->user_type === 'employee' && Auth::user()->is_manager))
            <button type="button" class="btn btn-sm btn-outline btn-secondary fetch-wathiq-btn"
                data-user-id="{{ $merchant->user_id }}" data-cr-number="{{ $merchant->cr_number }}"
                aria-label="{{ translate('Get Latest Wathiq Data') }}">
                {{ translate('Get Latest Wathiq Data') }}
            </button>
        @endif
    </div>


    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        <div class="md:col-span-7 space-y-4 border-r border-slate-50 pr-4">
            @php
                $crData = is_string($merchant->goverment_data)
                    ? json_decode($merchant->goverment_data, true)
                    : $merchant->goverment_data ?? [];
                $establishmentYear = !empty($crData['issueDateGregorian'])
                    ? \Carbon\Carbon::parse($crData['issueDateGregorian'])->format('Y')
                    : null;
                $yearsInBusiness = $establishmentYear ? now()->year - $establishmentYear : null;

                $status = $crData['status']['name'] ?? '';
                $color =
                    $status === 'فعال' || $status === 'Active'
                        ? 'green'
                        : ($status === 'معلق' || $status === 'Suspended'
                            ? 'yellow'
                            : 'red');
                $icon =
                    $status === 'فعال' || $status === 'Active'
                        ? 'ki-check-circle text-emerald-500'
                        : 'ki-cross-circle text-red-500';

            @endphp

            <div class="flex items-center gap-3 text-sm">
                <i class="ki-outline {{ $icon }} text-emerald-500 text-lg"></i>
                <span class="text-slate-500 w-28">{{ translate('Status') }}:</span>
                <span class="font-bold text-{{ $color }}-600">{{ $status }}</span>
            </div>

            @if ($establishmentYear)
                <div class="flex items-center gap-3 text-sm">
                    <i class="ki-outline ki-calendar text-blue-500 text-lg"></i>
                    <span class="text-slate-500 w-28">{{ translate('Established') }}:</span>
                    <span class="font-bold text-slate-800">
                        {{ translate('Since') }} {{ $establishmentYear }}
                        <span class="text-slate-400 font-medium ml-1">({{ $yearsInBusiness }}
                            {{ translate('years') }})</span>
                    </span>
                </div>
            @endif

            @if ($merchant->cr_number)
                <div class="flex items-center gap-3 text-sm">
                    <i class="ki-outline ki-document text-blue-500 text-lg"></i>
                    <span class="text-slate-500 w-28">{{ translate('CR Number') }}:</span>
                    <span class="font-bold text-slate-800">{{ $merchant->cr_number }}</span>
                </div>
            @endif

            @if (!empty($crData['crCapital']))
                <div class="flex items-center gap-3 text-sm">
                    <span class="icon-saudi_riyal"></span>
                    <span class="text-slate-500 w-28">{{ translate('Capital') }}:</span>
                    <span class="font-bold text-slate-800">SAR
                        {{ number_format($crData['crCapital']) }}</span>
                </div>
            @endif

            <div class="flex items-center gap-3 text-sm">
                <i class="ki-outline ki-verify text-blue-500 text-lg"></i>
                <span class="text-slate-500 w-28">{{ translate('VAT Registered') }}:</span>
                <span
                    class="font-bold text-slate-800">{{ $merchant->vat_register ? translate('Yes') : translate('No') }}</span>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <i class="ki-outline ki-files text-blue-500 text-lg"></i>
                <span class="text-slate-500 w-28">{{ translate('Balady Registered') }}:</span>
                <span
                    class="font-bold text-slate-800">{{ $merchant->balady_certificate ? translate('Yes') : translate('No') }}</span>
            </div>
        </div>

        <div class="md:col-span-5 flex flex-col justify-between space-y-6">
            <div class="bg-slate-50/50 p-4 rounded-lg border border-slate-100">
                <p class="text-xs text-slate-600 leading-relaxed italic">
                    @if (!empty($crData['name']))
                        "{{ $crData['name'] }}"
                        {{ !empty($crData['entityType']['name']) ? 'is a ' . $crData['entityType']['name'] : '' }}
                        {{ $establishmentYear ? 'established in ' . $establishmentYear : '' }}
                        {{ !empty($crData['headquarterCityName']) ? 'based in ' . $crData['headquarterCityName'] . '.' : '' }}
                        {{ $merchant->user?->description ?? '' }}
                    @else
                        {{ translate('No business description available.') }}
                    @endif
                </p>
            </div>

            @if (!empty($crData))
                <div class="grid grid-cols-2 gap-y-4 gap-x-6 pt-2">
                    @if (!empty($crData['crNationalNumber']))
                        <div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                {{ translate('CR National No') }}
                            </div>
                            <div class="text-xs font-bold text-slate-700">{{ $crData['crNationalNumber'] }}
                            </div>
                        </div>
                    @endif

                    @if (!empty($crData['issueDateGregorian']))
                        <div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                {{ translate('Issue Date') }}
                            </div>
                            <div class="text-xs font-bold text-slate-700">
                                {{ \Carbon\Carbon::parse($crData['issueDateGregorian'])->format('d M Y') }}
                            </div>
                        </div>
                    @endif

                    @if (!empty($crData['headquarterCityName']))
                        <div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                {{ translate('Headquarter City') }}
                            </div>
                            <div class="text-xs font-bold text-slate-700">
                                {{ $crData['headquarterCityName'] }}
                            </div>
                        </div>
                    @endif

                    @if (!empty($crData['activities']))
                        <div>
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">
                                {{ translate('Activities') }}
                            </div>
                            <div class="text-xs font-bold text-slate-700">
                                {{ count($crData['activities']) }} {{ translate('Registered') }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-2">
                    <button class="text-blue-600 text-xs font-bold flex items-center gap-1 hover:underline"
                        data-modal-toggle="#cr_data_modal">
                        {{ translate('View Full CR Data') }} <i class="ki-outline ki-arrow-right"></i>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@include('admin.accounts.components.cr-data-modal')


@push('scripts')
    <!-- SweetAlert2 (CDN). Remove if already loaded globally. -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function() {
            // Route URL (rendered server-side)
            const fetchUrl = {!! json_encode(route('merchants.fetchWathiq')) !!};

            function getCsrfToken() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            }

            document.querySelectorAll('.fetch-wathiq-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const userId = btn.dataset.userId;
                    const currentCr = btn.dataset.crNumber || '';

                    if (!userId) {
                        Swal.fire('Error', 'Merchant user id not found.', 'error');
                        return;
                    }

                    Swal.fire({
                        title: {!! json_encode(translate('Fetch Latest Wathiq Data')) !!},
                        html: '<p class="text-sm mb-3">' +
                            {!! json_encode(
                                translate(
                                    'Enter Commercial Registration (CR) national number (at least 7 digits). Leave empty to use stored CR number',
                                ),
                            ) !!} +
                            ': <strong>' +
                            (currentCr || 'none') +
                            '</strong></p>',

                        input: 'text',
                        inputPlaceholder: 'e.g. 7001234567',
                        inputValue: currentCr,
                        showCancelButton: true,
                        confirmButtonText: 'Fetch',
                        cancelButtonText: 'Cancel',
                        preConfirm: (value) => {
                            // Trim and validate: allow empty (we'll fallback), otherwise must be digits and length >=7
                            const v = (value || '').toString().trim();
                            if (v === '') {
                                return '';
                            }
                            if (!/^\d+$/.test(v)) {
                                Swal.showValidationMessage('CR must contain only digits.');
                                return false;
                            }
                            if (v.length < 7) {
                                Swal.showValidationMessage('CR must be at least 7 digits.');
                                return false;
                            }
                            return v;
                        },
                        focusConfirm: false,
                    }).then(function(result) {
                        if (!result.isConfirmed) return;

                        // result.value will be '' (empty string) or the CR entered
                        const crNumber = result.value || '';

                        // Final confirm (optional): show another confirm if you want extra warning — skipping to fetch directly
                        // visual feedback on button
                        const originalHtml = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = 'Fetching...';

                        fetch(fetchUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': getCsrfToken(),
                                },
                                body: JSON.stringify({
                                    user_id: parseInt(userId, 10),
                                    cr_number: crNumber || null
                                })
                            })
                            .then(function(res) {
                                return res.json().catch(function() {
                                    throw new Error(
                                        'Invalid JSON response from server.');
                                }).then(function(json) {
                                    return {
                                        status: res.status,
                                        json: json
                                    };
                                });
                            })
                            .then(function(resp) {
                                const status = resp.status;
                                const json = resp.json;

                                if (json && json.success) {
                                    Swal.fire('Updated', json.message ||
                                            'Wathiq data updated', 'success')
                                        .then(function() {
                                            window.location.reload();
                                        });
                                    return;
                                }

                                // handle Wathq specific code (like 400.1.5) shown from server
                                if (json && json.code) {
                                    Swal.fire('Wathiq Error', json.message ||
                                        'Wathiq returned an error: ' + json.code,
                                        'warning');
                                } else {
                                    Swal.fire('Error', (json && json.message) ? json
                                        .message : 'Failed to update Wathiq data',
                                        'error');
                                }

                                btn.disabled = false;
                                btn.innerHTML = originalHtml;
                            })
                            .catch(function(err) {
                                console.error(err);
                                Swal.fire('Error',
                                    'Request failed. Check console for details.',
                                    'error');
                                btn.disabled = false;
                                btn.innerHTML = originalHtml;
                            });
                    });
                });
            });
        })();
    </script>
@endpush
