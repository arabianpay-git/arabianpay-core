@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>
        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            <!-- Container -->
            @include('admin.accounts.includes.customer')
            <!-- End of Container -->
        </div>
        <!-- Container -->
        @include('admin.accounts.includes.customer-header')
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ translate('Customer Profile') }}</h1>
                @php
                    // Authorization check for financial data
                    $isAuthorized = hasSensitivePermission('credit_data_simah_bureau');

                    // Calculations
                    $creditScoreVal = $data['creditScore']['compositeScore'] ?? 0;
                    $riskScoreVal = $riskScore->total_score ?? 0;
                    $oldCreditLimit = 20000;
                    $finalScore = $creditScoreVal * ($riskScoreVal / 100);
                    $newCreditLimit = $oldCreditLimit * ($finalScore / 100);
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mt-4">

                    {{-- Credit Score Card --}}
                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">{{ translate('Credit Score') }}</p>
                                    <p class="text-2xl font-bold text-primary-600">
                                        {{ $isAuthorized ? $creditScoreVal . '/100' : '***' }}
                                    </p>
                                </div>
                                <div class="bg-primary-100 p-3 rounded-full">
                                    <i class="ki-filled ki-arrow-up-refraction text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Risk Score Card --}}
                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">{{ translate('Risk Score') }}</p>
                                    <p class="text-2xl font-bold text-primary-600">
                                        {{ $isAuthorized ? $riskScoreVal . '/100' : '***' }}
                                    </p>
                                </div>
                                <div class="bg-primary-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Total Score Card --}}
                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">{{ translate('Total Score') }}</p>
                                    <p class="text-2xl font-bold text-yellow-600">
                                        {{ $isAuthorized ? number_format($finalScore, 2) : '***' }}
                                    </p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Credit Limit Card --}}
                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">{{ translate('Credit Limit') }}</p>
                                    <p class="text-2xl font-bold text-yellow-600">
                                        @if ($isAuthorized)
                                            <span class="icon-saudi_riyal"></span>{{ number_format($newCreditLimit, 2) }}
                                        @else
                                            {{ translate('Restricted') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="bg-yellow-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Business Age Card --}}
                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">{{ translate('Business Age') }}</p>
                                    <p class="text-2xl font-bold text-green-600">
                                        {{ $isAuthorized ? $data['businessAge'] ?? 'N/A' : '***' }}
                                    </p>
                                </div>
                                <div class="bg-green-100 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- begin: grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                @php
                    // Decode government_data into array
                    $g = is_string($customer->cr_data)
                        ? json_decode($customer->cr_data, true)
                        : $customer->cr_data ?? [];
                @endphp

                <div class="col-span-1 lg:col-span-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ translate('Government information') }}</h3>
                        </div>
                        <div class="card-body">
                            @if (empty($g))
                                <p class="text-sm text-gray-600">{{ translate('No government data available.') }}</p>
                            @else
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {{-- LEFT COLUMN --}}
                                    <table class="table-auto w-full">
                                        <tbody>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">
                                                    {{ translate('CR National Number') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['crNationalNumber']) ? maskedSensitiveText('business_identity', $g['crNationalNumber']) : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('CR Number') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ maskedSensitiveText('business_identity', $g['crNumber'] ?? '-') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Version') }}</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['versionNo'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Name') }}</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['name'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Language') }}</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['nameLangDesc'] ?? '-' }}</td>
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
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Duration (yrs)') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['companyDuration'] ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">
                                                    {{ translate('Main CR National No') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['mainCrNationalNumber'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Main CR Number') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['mainCrNumber'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('In Liquidation?') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['inLiquidationProcess']) ? translate('Yes') : translate('No') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('E-Commerce?') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['hasEcommerce']) ? translate('Yes') : translate('No') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Headquarter City') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['headquarterCityName']) ? maskedSensitiveText('business_identity', $g['headquarterCityName']) : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('License Based?') }}
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
                                                    {{ translate('Partners’ Nationality') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['PartnersNationalityName'] ?? '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    {{-- RIGHT COLUMN --}}
                                    <table class="table-auto w-full">
                                        <tbody>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Entity Type') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['entityType']['name'] ?? '-' }} –
                                                    {{ $g['entityType']['formName'] ?? '' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Status') }}</td>
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
                                                    <span class="badge badge-sm badge-{{ $color }} badge-outline">
                                                        {{ $g['status']['name'] ?? '-' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Issue Date (G)') }}
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
                                                            ? \Carbon\Carbon::parse($gregorian)->format(dateFormat())
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
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Suspension Date') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['suspensionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['suspensionDate']['gregorian'])->format(dateFormat()) : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Deletion Date') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['deletionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['deletionDate']['gregorian'])->format(dateFormat()) : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Contact Phone') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['contactInfo']['phoneNo'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Contact Mobile') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['contactInfo']['mobileNo'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Contact Email') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['contactInfo']['email'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Website') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    @if (!empty($g['contactInfo']['websiteUrl']))
                                                        <a href="//{{ $g['contactInfo']['websiteUrl'] }}" target="_blank"
                                                            class="text-primary">
                                                            {{ $g['contactInfo']['websiteUrl'] }}
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('e-Store URL') }}</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['eCommerce']['eStore'][0]['storeUrl'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('e-Store Platform') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['eCommerce']['eStore'][0]['authenticationPlatformUrl'] ?? '-' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <h1 class="text-xl card-title pt-5 pb-3">{{ translate('Activities') }}</h1>
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

                                            <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
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

                                <h1 class="text-xl card-title pt-5 pb-3">{{ translate('Parties') }}</h1>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-3 pb-3">
                                    @foreach (array_chunk($g['parties'], 2) as $chunk)
                                        @foreach ($chunk as $index => $party)
                                            <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
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
                                                                    {{ $party['identity']['typeName'] ?? '-' }}</td>
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

                                <h1 class="text-xl card-title pt-5 pb-3">{{ translate('Managers') }}</h1>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-3 pb-3">
                                    @if (!empty($g['management']['managers']) && is_array($g['management']['managers']))
                                        @foreach (array_chunk($g['management']['managers'], 2) as $chunk)
                                            @foreach ($chunk as $index => $manager)
                                                <div class="border border-gray-200 rounded-xl shadow-sm overflow-hidden">
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
                                                                        {{ $manager['identity']['typeName'] ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Nationality') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $manager['nationality']['name'] ?? '-' }}</td>
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
                                                                                {{ $position['name'] ?? '-' }}@if (!$loop->last)
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
                                    @else
                                        <p class="text-gray-500 col-span-2">{{ translate('No manager data available.') }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- end: grid -->
        </div>
        <!-- End of Container -->
        <div class="modal modal-open:!flex" data-modal="true" data-modal-disable-scroll="false" id="modal_settings">
            <div class="modal-content modal-center max-w-[500px] w-full" id="modal_settings_content">
                <div class="modal-header p-0 border-0">
                    <!-- Container -->
                    <div class="container-fixed">
                        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                            <div class="flex items-center justify-between flex-wrap grow gap-5">
                                <div class="flex flex-col justify-center gap-2">
                                    <h1 class="text-xl pt-5 font-semibold leading-none text-gray-900">
                                        {{ translate('Fahman') }}
                                    </h1>
                                    <div class="flex items-center gap-2 text-sm font-normal text-gray-700">
                                        {{ translate('The Friendly FinAdvisor') }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <a class="btn btn-sm btn-light" data-modal-dismiss="true" href="#">
                                        {{ translate('Close') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End of Container -->
                </div>
                <div class="modal-body scrollable-y py-0 mb-5 ps-6 pe-3 me-3" id="modal_settings_body"
                    style="margin: auto">
                    <img src="{{ asset('assets/media/waiting.gif') }}" alt="" style="margin: auto;width:200px">
                    <div class="text-center mt-5">
                        <h3 class="text-lg font-semibold text-gray-900">{{ translate('Loading...') }}</h3>
                        <p class="text-sm text-gray-600">{{ translate('Customer data is being analyzed.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal modal-open:!flex" data-modal="true" data-modal-disable-scroll="false" id="fahmanDetailsModal">
            <div class="modal-content modal-center max-w-[500px] w-full" id="fahman-details-body">
                <div class="modal-header p-0 border-0">
                    <!-- Container -->
                    <div class="container-fixed">
                        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                            <div class="flex items-center justify-between flex-wrap grow gap-5">
                                <div class="flex flex-col justify-center gap-2">
                                    <h1 class="text-xl pt-5 font-semibold leading-none text-gray-900">
                                        {{ translate('Fahman') }}
                                    </h1>
                                    <div class="flex items-center gap-2 text-sm font-normal text-gray-700">
                                        {{ translate('The Friendly FinAdvisor') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End of Container -->
                </div>
                <div class="modal-body scrollable-y py-0 mb-5 ps-6 pe-3 me-3" id="modal_settings_body"
                    style="margin: auto">
                    <img src="{{ asset('assets/media/waiting.gif') }}" alt="" style="margin: auto;width:200px">
                    <div class="text-center mt-5">
                        <h3 class="text-lg font-semibold text-gray-900">{{ translate('Loading...') }}</h3>
                        <p class="text-sm text-gray-600">{{ translate('Customer data is being analyzed.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fahman Details Modal -->
        <div class="modal fade" id="fahmanDetailsModal1" tabindex="-1" aria-labelledby="fahmanDetailsModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="fahmanDetailsModalLabel">
                            📊 {{ translate('Detailed Credit Breakdown') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body" id="fahman-details-body1">
                        <div class="text-center text-muted">
                            <span>{{ translate('Loading details...') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            window.onload = () => {
                const modalEl = KTDom.getElement('#modal_settings');
                const modal = KTModal.getInstance(modalEl);
                modal?.show();
            };
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const fahmanBody = document.getElementById("modal_settings_body");
                // Fetch the initial data for fahman results
                const url = "{{ url('admin/fahman-results/' . $customer->id) }}";
                setTimeout(() => {
                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            fahmanBody.innerHTML = html;
                        })
                        .catch(error => {
                            console.error("Failed to get data:", error);
                            fahmanBody.innerHTML = `
                            <span>
                                Failed to get data
                            </span>
                        `;
                        });
                }, 3000); // 3000 milliseconds = 3 seconds
            });
        </script>
        <script>
            function showDetails(customerId) {
                const modalElF = KTDom.getElement('#fahmanDetailsModal');
                const modalF = KTModal.getInstance(modalElF);

                modalF?.show();
                const detailsBody = document.getElementById('fahman-details-body');

                // عرض المودال
                //modal.show();
                const url = "{{ url('admin/fahman-details/' . $customer->id) }}";
                // تحميل التفاصيل
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        console.log(html);
                        detailsBody.innerHTML = html;
                    })
                    .catch(error => {
                        console.error(error);
                        detailsBody.innerHTML = `<div class="text-danger">Failed to load details</div>`;
                    });
            }
        </script>
    </main>
@endsection
