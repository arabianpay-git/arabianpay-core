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
            @include('admin.accounts.includes.profile')
            <!-- End of Container -->
        </div>

        <!-- Container -->
        @include('admin.accounts.includes.header')
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <!-- begin: grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                <div class="col-span-1 lg:col-span-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="flex gap-2">
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        {{ number_format($totalProducts) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        {{ translate('Products') }}
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        {{ number_format($totalOrders) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        {{ translate('Orders') }}
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        <span class="icon-saudi_riyal"></span> {{ number_format($walletBalance, 2) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        {{ translate('Wallet Balance') }}
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        <span class="icon-saudi_riyal"></span> {{ number_format($revenue, 2) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        {{ translate('Revenue') }}
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                            </div>
                        </div>
                    </div>
                </div>


                @php
                    // Decode government_data into array
                    $g = is_string($merchant->goverment_data)
                        ? json_decode($merchant->goverment_data, true)
                        : $merchant->goverment_data ?? [];
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
                                                <td class="text-sm text-gray-900 py-2">{{ $g['crNationalNumber'] ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('CR Number') }}</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['crNumber'] ?? '-' }}</td>
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
                                                    {{ $g['headquarterCityName'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('License Based?') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['isLicenseBased']) ? translate('Yes') : translate('No') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('License Issuer No') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['licenseIssuerNationalNumber'] ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">
                                                    {{ translate('License Issuer Name') }}</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['licenseIssuerName'] ?? '-' }}
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
                                                    {{ !empty($g['issueDateGregorian']) ? \Carbon\Carbon::parse($g['issueDateGregorian'])->format('d M Y') : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Issue Date (H)') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['issueDateHijri'] ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Confirmation Date') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    @php
                                                        $gregorian =
                                                            $g['status']['confirmationDate']['gregorian'] ?? null;
                                                        $hijri = $g['status']['confirmationDate']['hijri'] ?? null;

                                                        if (empty($gregorian) && !empty($hijri)) {
                                                            $converted = hijriToGregorian($hijri);
                                                            $gregorian = $converted?->format('Y-m-d');
                                                        }
                                                    @endphp

                                                    @if (!empty($gregorian))
                                                        {{ \Carbon\Carbon::parse($gregorian)->format('d M Y') }}<br />
                                                        {{ $hijri }}
                                                    @elseif (!empty($hijri))
                                                        {{ $hijri }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Reactivation Date') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['reactivationDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['reactivationDate']['gregorian'])->format('d M Y') : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Suspension Date') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['suspensionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['suspensionDate']['gregorian'])->format('d M Y') : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">{{ translate('Deletion Date') }}
                                                </td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['deletionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['deletionDate']['gregorian'])->format('d M Y') : '-' }}
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
                                                        {{ $party['name'] }}
                                                    </h3>
                                                </div>
                                                <div class="overflow-x-auto">
                                                    <table class="table-auto w-full text-sm">
                                                        <tbody>
                                                            <tr>
                                                                <td class="text-gray-600 py-2 px-4">
                                                                    {{ translate('Name') }}</td>
                                                                <td class="text-gray-900 py-2 px-4">{{ $party['name'] }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-gray-600 py-2 px-4">
                                                                    {{ translate('Identity ID') }}</td>
                                                                <td class="text-gray-900 py-2 px-4">
                                                                    {{ $party['identity']['id'] ?? '-' }}</td>
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
                                                            {{ $manager['name'] ?? '-' }}
                                                        </h3>
                                                    </div>
                                                    <div class="overflow-x-auto">
                                                        <table class="table-auto w-full text-sm">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Name') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $manager['name'] ?? '-' }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-gray-600 py-2 px-4">
                                                                        {{ translate('Identity ID') }}</td>
                                                                    <td class="text-gray-900 py-2 px-4">
                                                                        {{ $manager['identity']['id'] ?? '-' }}</td>
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

                <div class="col-span-1">
                    <div class="flex flex-col gap-5 lg:gap-7.5">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    {{ translate('Supplier Files') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="grid gap-2.5 lg:gap-5">

                                    {{-- Registration Form --}}
                                    @if (!empty($merchant->registration_number_form))
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center grow gap-2.5">
                                                @php
                                                    $ext = pathinfo(
                                                        $merchant->registration_number_form,
                                                        PATHINFO_EXTENSION,
                                                    );
                                                @endphp

                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                    class="h-10" />


                                                <div class="flex flex-col">
                                                    <a href="{{ supplierMedia($merchant->registration_number_form) }}"
                                                        target="_blank" class="underline">
                                                        {{ translate('Registration Form') }}
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- VAT Register File --}}
                                    @if (!empty($merchant->vat_register_file))
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center grow gap-2.5">
                                                @php
                                                    $ext = pathinfo($merchant->vat_register_file, PATHINFO_EXTENSION);
                                                @endphp

                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                    class="h-10" />


                                                <div class="flex flex-col">
                                                    <a href="{{ supplierMedia($merchant->vat_register_file) }}"
                                                        target="_blank" class="underline">
                                                        {{ translate('VAT Register File') }}
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Return Policy File --}}
                                    @if (!empty($merchant->return_policy_file))
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center grow gap-2.5">
                                                @php
                                                    $ext = pathinfo($merchant->return_policy_file, PATHINFO_EXTENSION);
                                                @endphp

                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                    class="h-10" />


                                                <div class="flex flex-col">
                                                    <a href="{{ supplierMedia($merchant->return_policy_file) }}"
                                                        target="_blank" class="underline">
                                                        {{ translate('Return Policy File') }}
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Exchange Policy File --}}
                                    @if (!empty($merchant->exchange_policy_file))
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center grow gap-2.5">
                                                @php
                                                    $ext = pathinfo(
                                                        $merchant->exchange_policy_file,
                                                        PATHINFO_EXTENSION,
                                                    );
                                                @endphp

                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                    class="h-10" />

                                                <div class="flex flex-col">
                                                    <a href="{{ supplierMedia($merchant->exchange_policy_file) }}"
                                                        target="_blank" class="underline">
                                                        {{ translate('Exchange Policy File') }}
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Cancel Policy File --}}
                                    @if (!empty($merchant->cancel_policy_file))
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center grow gap-2.5">
                                                @php
                                                    $ext = pathinfo($merchant->cancel_policy_file, PATHINFO_EXTENSION);
                                                @endphp

                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                    class="h-10" />

                                                <div class="flex flex-col">
                                                    <a href="{{ supplierMedia($merchant->cancel_policy_file) }}"
                                                        target="_blank" class="underline">
                                                        {{ translate('Cancel Policy File') }}
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Owner Iqama Image --}}
                                    @if (!empty($merchant->owner_iqama_image))
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center grow gap-2.5">
                                                @php
                                                    $ext = pathinfo($merchant->owner_iqama_image, PATHINFO_EXTENSION);
                                                @endphp

                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                    class="h-10" />

                                                <div class="flex flex-col">
                                                    <a href="{{ supplierMedia($merchant->owner_iqama_image) }}"
                                                        target="_blank" class="underline">
                                                        {{ translate('Owner ID') }}
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- IBAN Certificates --}}
                                    @if ($supplierBanks->isEmpty())
                                        <div class="flex items-center gap-3 mt-4">
                                            <span
                                                class="text-red-500 text-sm italic">{{ translate('No IBAN Certificates uploaded') }}</span>
                                        </div>
                                    @else
                                        @foreach ($supplierBanks->groupBy('user_id') as $userId => $banks)
                                            @php
                                                $user = $banks->first()->user;
                                            @endphp

                                            <div
                                                class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-gray-50 dark:bg-gray-900 shadow-sm">
                                                <h5 class="text-md font-semibold text-gray-800 dark:text-white mb-3">
                                                    {{ $user->first_name }} {{ $user->last_name }}
                                                    ({{ $user->business_name ?? translate('N/A') }})
                                                </h5>

                                                <div class="space-y-3">
                                                    @foreach ($banks as $bank)
                                                        <div
                                                            class="flex items-center gap-3 p-2 border rounded-md bg-white dark:bg-gray-800">
                                                            <div class="flex items-center grow gap-2.5">
                                                                @php
                                                                    $ext = pathinfo(
                                                                        $bank->iban_certificate,
                                                                        PATHINFO_EXTENSION,
                                                                    );
                                                                @endphp

                                                                <img src="{{ asset('assets/media/images/default-pdf.png') }}"
                                                                    class="h-10" />

                                                                <div class="flex flex-col">
                                                                    <a href="{{ supplierMedia($bank->iban_certificate) }}"
                                                                        target="_blank" class="underline">
                                                                        {{ translate('IBAN Certificate') }}
                                                                    </a>
                                                                    <span class="text-xs text-gray-700 dark:text-gray-300">
                                                                        {{ Carbon\Carbon::parse($bank->created_at)->format('d M Y h:i A') }}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            @if (!$bank->iban_certificate)
                                                                <span
                                                                    class="text-red-500 text-sm italic">{{ translate('Not uploaded') }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif

                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    {{ translate('Bank Information') }}
                                </h3>
                            </div>
                            <div class="card-body pt-3.5 pb-3.5">
                                <table class="table-auto w-full border-collapse">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left text-sm font-semibold text-gray-700 pb-3 pe-4 lg:pe-10">
                                                {{ translate('Supplier Name') }}
                                            </th>
                                            <th class="text-left text-sm font-semibold text-gray-700 pb-3 pe-4 lg:pe-10">
                                                {{ translate('Bank Name') }}
                                            </th>
                                            <th class="text-left text-sm font-semibold text-gray-700 pb-3 pe-4 lg:pe-10">
                                                {{ translate('Bank Account Name') }}
                                            </th>
                                            <th class="text-left text-sm font-semibold text-gray-700 pb-3 pe-4 lg:pe-10">
                                                {{ translate('IBAN') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($supplierBanks as $supplierBank)
                                            <tr class="border-b">
                                                <td class="text-sm text-gray-900 pb-3 pe-4 lg:pe-10">
                                                    {{ $supplierBank->user->first_name ?? '-' }}
                                                    {{ $supplierBank->user->last_name ?? '' }}
                                                </td>
                                                <td class="text-sm text-gray-900 pb-3 pe-4 lg:pe-10">
                                                    {{ $supplierBank->bank_name ?? '-' }}
                                                </td>
                                                <td class="text-sm text-gray-900 pb-3 pe-4 lg:pe-10">
                                                    {{ $supplierBank->account_name ?? '-' }}
                                                </td>
                                                <td class="text-sm text-gray-900 pb-3">
                                                    {{ $supplierBank->iban ?? '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-sm text-gray-500 py-3">
                                                    {{ translate('No bank details found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-1">
                    <div class="flex flex-col gap-5 lg:gap-7.5">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    {{ translate('Company Profile') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <table class="table-auto">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                {{ translate('Type Business') }}
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->businessType?->name }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                {{ translate('CR Number') }}
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->cr_number ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                {{ translate('Register VAT') }}
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                @if ($merchant->vat_register_file || $merchant->vat_register_number)
                                                    <span class="badge badge-sm badge-success badge-outline">
                                                        {{ translate('Yes') }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-sm badge-danger badge-outline">
                                                        {{ translate('No') }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                {{ translate('VAT Number') }}
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->vat_register_number ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                {{ translate('Category') }}
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                <div class="flex flex-wrap gap-2.5">
                                                    @foreach ($businessCategory as $item)
                                                        <span class="badge badge-sm badge-outline badge-info">
                                                            {{ $item->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @include('admin.accounts.includes.profile-compliance')
                    </div>
                </div>
            </div>
            <!-- end: grid -->
        </div>
        <!-- End of Container -->

        <div class="modal modal-open:!flex" data-modal="true" data-modal-disable-scroll="false" id="modal_fahman">
            <div class="modal-content modal-center max-w-[500px] w-full" id="modal_fahman_content">
                <div class="modal-header p-0 border-0">
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
                </div>
                <div class="modal-body scrollable-y py-0 mb-5 ps-6 pe-3 me-3" id="modal_fahman_body"
                    style="margin: auto">
                    <img src="{{ asset('assets/media/waiting.gif') }}" alt="" style="margin: auto;width:200px">
                    <div class="text-center mt-5">
                        <h3 class="text-lg font-semibold text-gray-900">{{ translate('Loading...') }}</h3>
                        <p class="text-sm text-gray-600">{{ translate('Customer data is being analyzed.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal modal-open:!flex" data-modal="true" data-modal-disable-scroll="false"
            id="fahmanSupplierDetailsModal">
            <div class="modal-content modal-center w-full" id="fahman-supplier-details-body">
                <div class="modal-header p-0 border-0">
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
    </main>

@endsection

@push('scripts')
    <script>
        window.onload = () => {
            const modalEl = KTDom.getElement('#modal_fahman');
            const modal = KTModal.getInstance(modalEl);
            modal?.show();
        };
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const fahmanBody = document.getElementById("modal_fahman_body");
            // Fetch the initial data for fahman results
            const url = "{{ url('admin/fahman-supplier-results/' . $merchant->id) }}";
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
        function showSupplierRiskDetails() {
            const modalElF = KTDom.getElement('#fahmanSupplierDetailsModal');
            const modalF = KTModal.getInstance(modalElF);

            modalF?.show();
            const detailsBody = document.getElementById('fahman-supplier-details-body');

            // عرض المودال
            //modal.show();
            const url = "{{ url('admin/fahman-supplier-details/' . $merchant->id) }}";
            // تحميل التفاصيل
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    console.log(html);
                    detailsBody.innerHTML = html;
                })
                .catch(error => {
                    w
                    console.error(error);
                    detailsBody.innerHTML = `<div class="text-danger">Failed to load details</div>`;
                });
        }
    </script>
@endpush
