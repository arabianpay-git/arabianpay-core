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
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Customer Profile</h1>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mt-4">

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Credit Score</p>
                                    <p class="text-2xl font-bold text-primary-600">
                                        {{ $data['creditScore']['compositeScore'] }}/100
                                    </p>
                                </div>
                                <div class="bg-primary-100 p-3 rounded-full">
                                    <i class="ki-filled ki-arrow-up-refraction text-2xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Risk Score</p>
                                    <p class="text-2xl font-bold text-primary-600">
                                        {{ $riskScore->total_score }}/100
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

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    @php
                                        $creditScore = $data['creditScore']['compositeScore'];
                                        $riskScore = $riskScore->total_score;
                                        $oldCreditLimit = 20000;

                                        $finalScore = $creditScore * ($riskScore / 100);

                                        $newCreditLimit = $oldCreditLimit * ($finalScore / 100);
                                    @endphp
                                    <p class="text-sm text-gray-500">Total Score</p>
                                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($finalScore, 2) }}</p>
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

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Credit Limit</p>
                                    <p class="text-2xl font-bold text-yellow-600">
                                        <span class="icon-saudi_riyal"></span>{{ number_format($newCreditLimit, 2) }}
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

                    <div class="grid grid-cols-3 lg:grid-cols-1">
                        <div class="card p-4 bg-white dark:bg-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-500">Business Age</p>
                                    <p class="text-2xl font-bold text-green-600">{{ $data['businessAge'] }}</p>
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
                            <h3 class="card-title">Government information</h3>
                        </div>
                        <div class="card-body">

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {{-- LEFT COLUMN --}}
                                <table class="table-auto w-full">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">CR National Number</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['crNationalNumber'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">CR Number</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['crNumber'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Version</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['versionNo'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Name</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['name'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Language</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['nameLangDesc'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Capital
                                                ({{ $g['capital']['currencyName'] ?? '' }})</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['crCapital'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Duration (yrs)</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['companyDuration'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Main CR National No</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['mainCrNationalNumber'] ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Main CR Number</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['mainCrNumber'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">In Liquidation?</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['inLiquidationProcess']) ? 'Yes' : 'No' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">E-Commerce?</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['hasEcommerce']) ? 'Yes' : 'No' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Headquarter City</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['headquarterCityName'] ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">License Based?</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['isLicenseBased']) ? 'Yes' : 'No' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">License Issuer No</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['licenseIssuerNationalNumber'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">License Issuer Name</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['licenseIssuerName'] ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Partners’ Nationality</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['PartnersNationalityName'] ?? '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                {{-- RIGHT COLUMN --}}
                                <table class="table-auto w-full">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Entity Type</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['entityType']['name'] ?? '-' }} –
                                                {{ $g['entityType']['formName'] ?? '' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Status</td>
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
                                            <td class="text-sm text-gray-600 py-2">Issue Date (G)</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['issueDateGregorian']) ? \Carbon\Carbon::parse($g['issueDateGregorian'])->format('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Issue Date (H)</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['issueDateHijri'] ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Confirmation Date</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['confirmationDate']['gregorian']) ? \Carbon\Carbon::parse($g['confirmationDate']['gregorian'])->format('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Reactivation Date</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['reactivationDate']['gregorian']) ? \Carbon\Carbon::parse($g['reactivationDate']['gregorian'])->format('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Suspension Date</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['suspensionDate']['gregorian']) ? \Carbon\Carbon::parse($g['suspensionDate']['gregorian'])->format('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Deletion Date</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ !empty($g['deletionDate']['gregorian']) ? \Carbon\Carbon::parse($g['deletionDate']['gregorian'])->format('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Contact Phone</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['contactInfo']['phoneNo'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Contact Mobile</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['contactInfo']['mobileNo'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Contact Email</td>
                                            <td class="text-sm text-gray-900 py-2">{{ $g['contactInfo']['email'] ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">Website</td>
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
                                            <td class="text-sm text-gray-600 py-2">e-Store URL</td>
                                            <td class="text-sm text-gray-900 py-2">
                                                {{ $g['eCommerce']['eStore'][0]['storeUrl'] ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 py-2">e-Store Platform</td>
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
            <!-- end: grid -->
        </div>
        <!-- End of Container -->
    </main>
@endsection
