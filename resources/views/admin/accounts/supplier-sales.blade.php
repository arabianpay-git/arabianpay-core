@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .tooltip-container {
                position: relative;
                display: inline-block;
                cursor: pointer;
            }

            .tooltip-container .tooltip-text {
                visibility: hidden;
                opacity: 0;
                width: max-content;
                max-width: max-content;
                background-color: #000;
                color: #fff;
                text-align: center;
                border-radius: 0.375rem;
                /* rounded-md */
                padding: 4px 8px;
                position: absolute;
                bottom: 125%;
                /* above the icon */
                left: 50%;
                transform: translateX(-50%);
                transition: opacity 0.2s ease-in-out;
                z-index: 50;
                white-space: nowrap;
            }

            .tooltip-container:hover .tooltip-text {
                visibility: visible;
                opacity: 1;
            }

            .tooltip-icon {
                padding: 5px
            }
        </style>
    @endpush
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
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ translate('Supplier Sales') }}
                            </h3>
                            <div class="flex flex-wrap gap-2 lg:gap-5">
                                <div class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"> </i>
                                        <input data-datatable-search="#team_crew_table"
                                            placeholder="{{ translate('Search users') }}" type="text" value="" />
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                                <div class="scrollable-x-auto">
                                    <table class="table table-auto table-border" data-datatable-table="true">
                                        <thead>
                                            <tr>
                                                <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                                <th class="text-left">{{ translate('Order Code') }}</th>
                                                <th class="text-center">{{ translate('Order Date') }}</th>
                                                <th class="text-center">
                                                    <div class="flex flex-col items-center space-y-1">
                                                        <div class="font-normal text-gray-700 flex items-center gap-1">
                                                            {{ translate('Total Amount') }}

                                                            <!-- Tooltip Trigger -->
                                                            <div class="tooltip-container">
                                                                <span
                                                                    class="tooltip-icon inline-block w-4 h-4 rounded-full bg-gray-300 text-xs text-gray-800 flex items-center justify-center">?</span>

                                                                <!-- Tooltip Text -->
                                                                <div class="tooltip-text">
                                                                    {{ translate('Includes tax calculated per product') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </th>

                                                <th class="text-center">{{ translate('Total Invoice Without Tax') }}</th>
                                                <th class="text-center">
                                                    <div class="flex flex-col items-center space-y-1">
                                                        <div class="font-normal text-gray-700 flex items-center gap-1">
                                                            {{ translate('System Commission') }}

                                                            <!-- Tooltip Trigger -->
                                                            <div class="tooltip-container">
                                                                <span
                                                                    class="tooltip-icon inline-block w-4 h-4 rounded-full bg-gray-300 text-xs text-gray-800 flex items-center justify-center">?</span>

                                                                <!-- Tooltip Text -->
                                                                <div class="tooltip-text">
                                                                    {{ translate('Value representing the commission amount calculated as a percentage of the grand total.') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </th>

                                                <th class="text-center">
                                                    <div class="flex flex-col items-center space-y-1">
                                                        <div class="font-normal text-gray-700 flex items-center gap-1">
                                                            {{ translate('Commission Tax') }}

                                                            <!-- Tooltip Trigger -->
                                                            <div class="tooltip-container">
                                                                <span
                                                                    class="tooltip-icon inline-block w-4 h-4 rounded-full bg-gray-300 text-xs text-gray-800 flex items-center justify-center">?</span>

                                                                <!-- Tooltip Text -->
                                                                <div class="tooltip-text">
                                                                    {{ translate('Total amount after applying the commission and adding the applicable commission tax.') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </th>

                                                <th class="text-center">{{ translate('Net Invoice') }}</th>
                                                <th class="text-center">{{ translate('Tax') }}</th>
                                                <th class="text-center">{{ translate('Supplier Due') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $index => $order)
                                                @php
                                                    $calc = $order->calculated;
                                                @endphp

                                                <tr>
                                                    <td class="text-center">{{ $index + 1 }}</td>
                                                    <td class="text-center">{{ $order->tracking ?? '-' }}</td>
                                                    <td class="text-center">{{ $order->created_at->format(dateFormat()) }}
                                                    </td>

                                                    {{-- Total Amount --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['totalAmount'], 2) }}
                                                    </td>

                                                    {{-- Total Invoice Without Tax --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['subTotal'] + $calc['shipping'] - $calc['discount'], 2) }}
                                                    </td>

                                                    {{-- System Commission --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['commissionAmount'], 2) }}
                                                        ({{ $calc['commissionPct'] }}%)
                                                    </td>

                                                    {{-- Commission Tax --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['commissionTaxAmt'], 2) }}
                                                        ({{ $calc['commissionTaxPct'] }}%)
                                                    </td>

                                                    {{-- Net Invoice --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['base'], 2) }}
                                                    </td>

                                                    {{-- Tax --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['tax'], 2) }}
                                                    </td>

                                                    {{-- Supplier Due --}}
                                                    <td class="text-right">
                                                        <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($calc['totalSuplierDue'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                    </table>
                                </div>
                                <!-- Pagination Footer -->
                                @include('layouts.includes.table-pagination', ['paginator' => $orders])
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
