<div class="modal" data-modal="true" id="cr_data_modal">
    <div class="modal-content max-w-6xl">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('CR Data') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        @php
            $g = is_string($merchant->goverment_data)
                ? json_decode($merchant->goverment_data, true)
                : $merchant->goverment_data ?? [];
        @endphp

        <div class="modal-body p-0">
            @if (empty($g))
                <div class="text-center py-10">
                    <p class="text-sm text-gray-600">{{ translate('No government data available.') }}</p>
                </div>
            @else
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 px-5">
                    <nav class="flex space-x-6 overflow-x-auto" id="crDataTabs">
                        <button type="button"
                            class="tab-btn py-4 px-1 text-sm font-medium border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap active"
                            data-tab="basic">
                            {{ translate('Basic Information') }}
                        </button>
                        <button type="button"
                            class="tab-btn py-4 px-1 text-sm font-medium border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                            data-tab="activities">
                            {{ translate('Activities') }}
                            <span class="ml-1 badge badge-sm badge-light">
                                {{ count($g['activities'] ?? []) }}
                            </span>
                        </button>
                        <button type="button"
                            class="tab-btn py-4 px-1 text-sm font-medium border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                            data-tab="parties">
                            {{ translate('Parties') }}
                            <span class="ml-1 badge badge-sm badge-light">
                                {{ count($g['parties'] ?? []) }}
                            </span>
                        </button>
                        <button type="button"
                            class="tab-btn py-4 px-1 text-sm font-medium border-b-2 border-transparent hover:text-primary transition-colors whitespace-nowrap"
                            data-tab="managers">
                            {{ translate('Managers') }}
                            <span class="ml-1 badge badge-sm badge-light">
                                {{ count($g['management']['managers'] ?? []) }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Contents -->
                <div class="p-5">
                    <!-- Basic Information Tab -->
                    <div class="tab-content active" id="basic-tab">
                        <div class="col-span-1 lg:col-span-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ translate('Government information') }}</h3>
                                </div>
                                <div class="card-body">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        {{-- LEFT COLUMN --}}
                                        <table class="table-auto w-full">
                                            <tbody>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('CR National Number') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ maskedSensitiveText('business_identity', $g['crNationalNumber'] ?? '-') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('CR Number') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ maskedSensitiveText('business_identity', $g['crNumber'] ?? '-') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('Version') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">{{ $g['versionNo'] ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('Name') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">{{ $g['name'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('Language') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['nameLangDesc'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('Capital') }}
                                                        ({{ $g['capital']['currencyName'] ?? '' }})</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        <span
                                                            class="icon-saudi_riyal"></span>{{ number_format($g['crCapital']) ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Duration (yrs)') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['companyDuration'] ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Main CR National No') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['mainCrNationalNumber'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Main CR Number') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['mainCrNumber'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('In Liquidation?') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ !empty($g['inLiquidationProcess']) ? translate('Yes') : translate('No') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('E-Commerce?') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ !empty($g['hasEcommerce']) ? translate('Yes') : translate('No') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Headquarter City') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ maskedSensitiveText('business_identity', $g['headquarterCityName'] ?? '-') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('License Based?') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ !empty($g['isLicenseBased']) ? translate('Yes') : translate('No') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('License Issuer No') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['licenseIssuerNationalNumber'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('License Issuer Name') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['licenseIssuerName'] ?? '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Partners\' Nationality') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['PartnersNationalityName'] ?? '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        {{-- RIGHT COLUMN --}}
                                        <table class="table-auto w-full">
                                            <tbody>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Entity Type') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['entityType']['name'] ?? '-' }} –
                                                        {{ $g['entityType']['formName'] ?? '' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('Status') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        @php
                                                            $st = strtolower($g['status']['name'] ?? '');
                                                            $color =
                                                                $st === 'فعال' || $st === 'active'
                                                                    ? 'success'
                                                                    : ($st === 'معلق' || $st === 'suspended'
                                                                        ? 'warning'
                                                                        : 'danger');
                                                        @endphp
                                                        <span
                                                            class="badge badge-sm badge-{{ $color }} badge-outline">
                                                            {{ $g['status']['name'] ?? '-' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Issue Date (G)') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ maskedSensitiveText(
                                                            'business_identity',
                                                            !empty($g['issueDateGregorian']) ? \Carbon\Carbon::parse($g['issueDateGregorian'])->format(dateFormat()) : '-',
                                                        ) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Issue Date (H)') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ maskedSensitiveText('business_identity', $g['issueDateHijri'] ?? '-') }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Confirmation Date') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        @php
                                                            $gregorian =
                                                                $g['status']['confirmationDate']['gregorian'] ?? null;
                                                            $hijri = $g['status']['confirmationDate']['hijri'] ?? null;

                                                            if (empty($gregorian) && !empty($hijri)) {
                                                                $converted = hijriToGregorian($hijri);
                                                                $gregorian = $converted?->format(dateFormat());
                                                            }

                                                            $formattedGregorian = $gregorian
                                                                ? \Carbon\Carbon::parse($gregorian)->format(
                                                                    dateFormat(),
                                                                )
                                                                : null;
                                                        @endphp

                                                        @if ($formattedGregorian)
                                                            {{ maskedSensitiveText('business_identity', $formattedGregorian) }}<br>
                                                            {{ maskedSensitiveText('business_identity', $hijri) }}
                                                        @elseif ($hijri)
                                                            {{ maskedSensitiveText('business_identity', $hijri) }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Reactivation Date') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ !empty($g['status']['reactivationDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['reactivationDate']['gregorian'])->format(dateFormat()) : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Suspension Date') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ !empty($g['status']['suspensionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['suspensionDate']['gregorian'])->format(dateFormat()) : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Deletion Date') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ !empty($g['status']['deletionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['deletionDate']['gregorian'])->format(dateFormat()) : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Contact Phone') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['contactInfo']['phoneNo'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Contact Mobile') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['contactInfo']['mobileNo'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('Contact Email') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['contactInfo']['email'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">{{ translate('Website') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        @if (!empty($g['contactInfo']['websiteUrl']))
                                                            <a href="//{{ $g['contactInfo']['websiteUrl'] }}"
                                                                target="_blank" class="text-primary">
                                                                {{ $g['contactInfo']['websiteUrl'] }}
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('e-Store URL') }}</td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['eCommerce']['eStore'][0]['storeUrl'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-sm text-gray-600 py-2">
                                                        {{ translate('e-Store Platform') }}
                                                    </td>
                                                    <td class="text-sm text-gray-900 py-2">
                                                        {{ $g['eCommerce']['eStore'][0]['authenticationPlatformUrl'] ?? '-' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activities Tab -->
                    <div class="tab-content hidden" id="activities-tab">
                        <div class="col-span-1 lg:col-span-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ translate('Activities') }}</h3>
                                    <span
                                        class="badge badge-sm badge-light">{{ count($g['activities'] ?? []) }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-3 pb-3">
                                        @foreach (array_chunk($g['activities'], 2) as $chunk)
                                            @foreach ($chunk as $index => $activity)
                                                @php
                                                    $groupName = null;
                                                    if (!empty($activity['id'])) {
                                                        $groupName = DB::table('activities')
                                                            ->where('activity_code', $activity['id'])
                                                            ->value('group_name');
                                                    }
                                                @endphp

                                                <div
                                                    class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                                    <div class="bg-gray-100 px-4 py-3">
                                                        <h3 class="text-base font-semibold text-gray-800">
                                                            {{ translate('Activity') }}
                                                            #{{ $loop->parent->index * 2 + $index + 1 }}
                                                        </h3>
                                                    </div>
                                                    <div class="overflow-x-auto">
                                                        <table class="table-auto w-full text-sm">
                                                            <tbody>
                                                                @if ($groupName)
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Group Name') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ $groupName ?? '-' }}</td>
                                                                    </tr>
                                                                @endif
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Activity ID') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $activity['id'] ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Activity Name') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $activity['name'] ?? '-' }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parties Tab -->
                    <div class="tab-content hidden" id="parties-tab">
                        <div class="col-span-1 lg:col-span-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ translate('Parties') }}</h3>
                                    <span class="badge badge-sm badge-light">{{ count($g['parties'] ?? []) }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-3 pb-3">
                                        @foreach (array_chunk($g['parties'], 2) as $chunk)
                                            @foreach ($chunk as $index => $party)
                                                <div
                                                    class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                                    <div class="bg-gray-100 px-4 py-3">
                                                        <h3 class="text-base font-semibold text-gray-800">
                                                            {{ translate('Partner') }}
                                                            #{{ $loop->parent->index * 2 + $index + 1 }} —
                                                            {{ maskedSensitiveText('authorized_person_name', $party['name'], 3, 3, 7) }}
                                                        </h3>
                                                    </div>
                                                    <div class="overflow-x-auto">
                                                        <table class="table-auto w-full text-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Name') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ maskedSensitiveText('authorized_person_name', $party['name'], 3, 3, 7) }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Identity ID') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ maskedSensitiveText('national_id_iqama', $party['identity']['id'] ?? '-') }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Identity Type') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $party['identity']['typeName'] ?? '-' }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Nationality') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $party['nationality']['name'] ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Type') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $party['typeName'] ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Partnership') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        @if (!empty($party['partnership']) && is_array($party['partnership']))
                                                                            @foreach ($party['partnership'] as $p)
                                                                                {{ $p['name'] }}@if (!$loop->last)
                                                                                    ,
                                                                                @endif
                                                                            @endforeach
                                                                        @else
                                                                            <span
                                                                                class="text-gray-400">{{ translate('N/A') }}</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Cash Contribution') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $party['partnerShare']['cashContributionCount'] ?? 0 }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('In-Kind Contribution') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $party['partnerShare']['inKindContributionCount'] ?? 0 }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Total Contribution') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $party['partnerShare']['totalContributionCount'] ?? 0 }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Managers Tab -->
                    <div class="tab-content hidden" id="managers-tab">
                        <div class="col-span-1 lg:col-span-3">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">{{ translate('Managers') }}</h3>
                                    @if (!empty($g['management']['managers']))
                                        <span
                                            class="badge badge-sm badge-light">{{ count($g['management']['managers']) }}</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if (!empty($g['management']['managers']) && is_array($g['management']['managers']))
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-3 pb-3">
                                            @foreach (array_chunk($g['management']['managers'], 2) as $chunk)
                                                @foreach ($chunk as $index => $manager)
                                                    <div
                                                        class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                                        <div class="bg-gray-100 px-4 py-3">
                                                            <h3 class="text-base font-semibold text-gray-800">
                                                                {{ translate('Manager') }}
                                                                #{{ $loop->parent->index * 2 + $index + 1 }} —
                                                                {{ maskedSensitiveText('authorized_person_name', $manager['name'] ?? '-', 3, 3, 7) }}
                                                            </h3>
                                                        </div>
                                                        <div class="overflow-x-auto">
                                                            <table class="table-auto w-full text-sm">
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Name') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ maskedSensitiveText('authorized_person_name', $manager['name'] ?? '-', 3, 3, 7) }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Identity ID') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ maskedSensitiveText('national_id_iqama', $manager['identity']['id'] ?? '-') }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Identity Type') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ $manager['identity']['typeName'] ?? '-' }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Nationality') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ $manager['nationality']['name'] ?? '-' }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Type') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ $manager['typeName'] ?? '-' }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Position(s)') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            @if (!empty($manager['positions']) && is_array($manager['positions']))
                                                                                @foreach ($manager['positions'] as $position)
                                                                                    {{ $position['name'] ?? '-' }}
                                                                                    @if (!$loop->last)
                                                                                        ,
                                                                                    @endif
                                                                                @endforeach
                                                                            @else
                                                                                <span class="text-gray-400">-</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-gray-600 py-2 px-4">
                                                                            {{ translate('Is Licensed?') }}</td>
                                                                        <td class="text-gray-900 py-2 px-4">
                                                                            {{ isset($manager['isLicensed']) ? ($manager['isLicensed'] ? translate('Yes') : translate('No')) : '-' }}
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-500">{{ translate('No manager data available.') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

<style>
    .tab-btn {
        position: relative;
        transition: all 0.2s ease;
    }

    .tab-btn.active {
        border-bottom-color: #3b82f6 !important;
        color: #3b82f6 !important;
        font-weight: 600;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('#crDataTabs .tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                // Update active tab button
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-primary', 'text-primary');
                    btn.classList.add('border-transparent', 'text-gray-600');
                });
                this.classList.add('active', 'border-primary', 'text-primary');
                this.classList.remove('border-transparent', 'text-gray-600');

                // Show active tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    content.classList.add('hidden');
                });

                const activeContent = document.getElementById(tabId + '-tab');
                if (activeContent) {
                    activeContent.classList.add('active');
                    activeContent.classList.remove('hidden');
                }
            });
        });
    });
</script>
