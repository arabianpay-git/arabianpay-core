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
                                        Products
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        {{ number_format($totalOrders) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        Orders
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        <span class="icon-saudi_riyal"></span> {{ number_format($walletBalance, 2) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        Wallet Balance
                                    </span>
                                </div>
                                <span class="[&:not(:last-child)]:border-e border-e-gray-300 my-1"> </span>
                                <div class="grid grid-cols-1 place-content-center flex-1 gap-1 text-center">
                                    <span class="text-gray-900 text-2xl lg:text-2.5xl leading-none font-semibold">
                                        <span class="icon-saudi_riyal"></span> {{ number_format($revenue, 2) }}
                                    </span>
                                    <span class="text-gray-700 text-sm">
                                        Revenue
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
                            <h3 class="card-title">Government information</h3>
                        </div>
                        <div class="card-body">
                            @if (empty($g))
                                <p class="text-sm text-gray-600">No government data available.</p>
                            @else
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    {{-- LEFT COLUMN --}}
                                    <table class="table-auto w-full">
                                        <tbody>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">CR National Number</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['crNationalNumber'] ?? '-' }}
                                                </td>
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
                                                <td class="text-sm text-gray-900 py-2">
                                                    <span
                                                        class="icon-saudi_riyal"></span>{{ number_format($g['crCapital']) ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">Duration (yrs)</td>
                                                <td class="text-sm text-gray-900 py-2">{{ $g['companyDuration'] ?? '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">Main CR National No</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['mainCrNationalNumber'] ?? '-' }}</td>
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
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['headquarterCityName'] ?? '-' }}</td>
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
                                                    {{ !empty($g['status']['confirmationDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['confirmationDate']['gregorian'])->format('d M Y') : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">Reactivation Date</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['reactivationDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['reactivationDate']['gregorian'])->format('d M Y') : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">Suspension Date</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['suspensionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['suspensionDate']['gregorian'])->format('d M Y') : '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-sm text-gray-600 py-2">Deletion Date</td>
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ !empty($g['status']['deletionDate']['gregorian']) ? \Carbon\Carbon::parse($g['status']['deletionDate']['gregorian'])->format('d M Y') : '-' }}
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
                                                <td class="text-sm text-gray-900 py-2">
                                                    {{ $g['contactInfo']['email'] ?? '-' }}</td>
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
                            @endif
                        </div>
                    </div>
                </div>




                <div class="col-span-1">
                    <div class="flex flex-col gap-5 lg:gap-7.5">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Supplier Files
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

                                                @if (strtolower($ext) === 'pdf')
                                                    <img src="{{ asset('assets/media/file-types/pdf.svg') }}"
                                                        class="w-10 h-10" />
                                                @else
                                                    <img src="{{ asset($merchant->registration_number_form) }}"
                                                        class="w-10 h-10 object-cover rounded" />
                                                @endif

                                                <div class="flex flex-col">
                                                    <a target="__blank"
                                                        href="{{ asset($merchant->registration_number_form) }}"
                                                        target="_blank"
                                                        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-primary mb-px">
                                                        Registration Form
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

                                                @if (strtolower($ext) === 'pdf')
                                                    <img src="{{ asset('assets/media/file-types/pdf.svg') }}"
                                                        class="w-10 h-10" />
                                                @else
                                                    <img src="{{ asset($merchant->vat_register_file) }}"
                                                        class="w-10 h-10 object-cover rounded" />
                                                @endif

                                                <div class="flex flex-col">
                                                    <a target="__blank" href="{{ asset($merchant->vat_register_file) }}"
                                                        target="_blank"
                                                        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-primary mb-px">
                                                        VAT Register File
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

                                                @if (strtolower($ext) === 'pdf')
                                                    <img src="{{ asset('assets/media/file-types/pdf.svg') }}"
                                                        class="w-10 h-10" />
                                                @else
                                                    <img src="{{ asset($merchant->return_policy_file) }}"
                                                        class="w-10 h-10 object-cover rounded" />
                                                @endif

                                                <div class="flex flex-col">
                                                    <a target="__blank" href="{{ asset($merchant->return_policy_file) }}"
                                                        target="_blank"
                                                        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-primary mb-px">
                                                        Return Policy File
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

                                                @if (strtolower($ext) === 'pdf')
                                                    <img src="{{ asset('assets/media/file-types/pdf.svg') }}"
                                                        class="w-10 h-10" />
                                                @else
                                                    <img src="{{ asset($merchant->exchange_policy_file) }}"
                                                        class="w-10 h-10 object-cover rounded" />
                                                @endif

                                                <div class="flex flex-col">
                                                    <a target="__blank"
                                                        href="{{ asset($merchant->exchange_policy_file) }}"
                                                        target="_blank"
                                                        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-primary mb-px">
                                                        Exchange Policy File
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

                                                @if (strtolower($ext) === 'pdf')
                                                    <img src="{{ asset('assets/media/file-types/pdf.svg') }}"
                                                        class="w-10 h-10" />
                                                @else
                                                    <img src="{{ asset($merchant->cancel_policy_file) }}"
                                                        class="w-10 h-10 object-cover rounded" />
                                                @endif

                                                <div class="flex flex-col">
                                                    <a target="__blank" href="{{ asset($merchant->cancel_policy_file) }}"
                                                        target="_blank"
                                                        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-primary mb-px">
                                                        Cancel Policy File
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

                                                @if (strtolower($ext) === 'pdf')
                                                    <img src="{{ asset('assets/media/file-types/pdf.svg') }}"
                                                        class="w-10 h-10" />
                                                @else
                                                    <img src="{{ asset($merchant->owner_iqama_image) }}"
                                                        class="w-10 h-10 object-cover rounded" />
                                                @endif

                                                <div class="flex flex-col">
                                                    <a target="__blank" href="{{ asset($merchant->owner_iqama_image) }}"
                                                        target="_blank"
                                                        class="text-sm font-medium text-gray-900 cursor-pointer hover:text-primary mb-px">
                                                        Owner Iqama Image
                                                    </a>
                                                    <span class="text-xs text-gray-700">
                                                        {{ Carbon\Carbon::parse($merchant->created_at)->format('d M Y h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>

                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Bank Information
                                </h3>
                            </div>
                            <div class="card-body pt-3.5 pb-3.5">
                                <table class="table-auto">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Bank Name
                                            </td>
                                            <td class="text-sm ext-gray-900 pb-3">
                                                {{ $supplierBank->bank_name ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Bank Acount Name
                                            </td>
                                            <td class="text-sm ext-gray-900 pb-3">
                                                {{ $supplierBank->account_name ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                IBAN
                                            </td>
                                            <td class="text-sm ext-gray-900 pb-3">
                                                {{ $supplierBank->iban ?? '-' }}
                                            </td>
                                        </tr>
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
                                    Company Profle
                                </h3>
                            </div>
                            <div class="card-body">
                                <table class="table-auto">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Type Business
                                            </td>
                                            <td class="text-sm ext-gray-900 pb-3">
                                                {{ $merchant->businessType?->name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Category
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                <div class="flex flex-wrap gap-2.5">
                                                    @foreach ($businessCategory as $item)
                                                        <span class="badge badge-outline">
                                                            {{ $item->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                CR Number
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->cr_number ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Register VAT
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                @if ($merchant->register_vat)
                                                    <span class="badge badge-sm badge-success badge-outline">
                                                        Yes
                                                    </span>
                                                @else
                                                    <span class="badge badge-sm badge-danger badge-outline">
                                                        No
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                VAT Number
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->vat_number ?? '-' }}
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Company Policy
                                </h3>
                            </div>
                            <div class="card-body">
                                <table class="table-auto">
                                    <tbody>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Number of days for return
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->return_day_count ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Number of days for exchange
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->exchange_day_count ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-sm text-gray-600 pb-3 pe-4 lg:pe-10">
                                                Number of days for cancel
                                            </td>
                                            <td class="text-sm text-gray-900 pb-3">
                                                {{ $merchant->cancel_day_count ?? '-' }}
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
        <div class="modal modal-open:!flex" data-modal="true" data-modal-disable-scroll="false" id="modal_fahman">
            <div class="modal-content modal-center max-w-[500px] w-full" id="modal_fahman_content">
                <div class="modal-header p-0 border-0">
                    <!-- Container -->
                    <div class="container-fixed">
                        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                            <div class="flex items-center justify-between flex-wrap grow gap-5">
                                <div class="flex flex-col justify-center gap-2">
                                    <h1 class="text-xl pt-5 font-semibold leading-none text-gray-900">
                                        Fahman
                                    </h1>
                                    <div class="flex items-center gap-2 text-sm font-normal text-gray-700">
                                        The Friendly FinAdvisor
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <a class="btn btn-sm btn-light" data-modal-dismiss="true" href="#">
                                        Close
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End of Container -->
                </div>
                <div class="modal-body scrollable-y py-0 mb-5 ps-6 pe-3 me-3" id="modal_fahman_body"
                    style="margin: auto">
                    <img src="{{ asset('assets/media/waiting.gif') }}" alt="" style="margin: auto;width:200px">
                    <div class="text-center mt-5">
                        <h3 class="text-lg font-semibold text-gray-900">Loading...</h3>
                        <p class="text-sm text-gray-600">Customer data is being analyzed.</p>
                    </div>
                </div>

            </div>
        </div>

        <div class="modal modal-open:!flex" data-modal="true" data-modal-disable-scroll="false" id="fahmanSupplierDetailsModal">
            <div class="modal-content modal-center w-full" id="fahman-supplier-details-body">
                <div class="modal-header p-0 border-0">
                    <!-- Container -->
                    <div class="container-fixed">
                        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                            <div class="flex items-center justify-between flex-wrap grow gap-5">
                                <div class="flex flex-col justify-center gap-2">
                                    <h1 class="text-xl pt-5 font-semibold leading-none text-gray-900">
                                        Fahman
                                    </h1>
                                    <div class="flex items-center gap-2 text-sm font-normal text-gray-700">
                                        The Friendly FinAdvisor
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
                        <h3 class="text-lg font-semibold text-gray-900">Loading...</h3>
                        <p class="text-sm text-gray-600">Customer data is being analyzed.</p>
                    </div>
                </div>

            </div>
        </div>

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
                    console.error(error);
                    detailsBody.innerHTML = `<div class="text-danger">Failed to load details</div>`;
                });
        }
    </script>
    </main>


@endsection
