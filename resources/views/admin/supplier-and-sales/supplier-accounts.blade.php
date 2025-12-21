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

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Supplier Accounts') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Supplier Accounts') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table" placeholder="Search users"
                                        type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>

                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Seller') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Due to Seller') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Order Date') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Settlement Status') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Register Date') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Action') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($sellers as $seller)
                                            @php
                                                // Retrieve the first wallet for the seller (if exists)
                                                $wallet = $seller->sellerWallet->first(); // Use first() to get the first wallet in case there are multiple
                                                // Sum of the relevant amounts from transactions for this seller
                                                $collected = $seller->transactions->sum('collected');
                                                $retrieved = $seller->transactions->sum('retrieved');
                                                $canceled = $seller->transactions->sum('canceled');
                                            @endphp
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>
                                                    {{ $seller->first_name }} {{ $seller->last_name }}
                                                    <br>
                                                    <small class="text-gray-500">—
                                                        {{ $seller->business_name ?? translate('N/A') }}</small>
                                                </td>
                                                <td>
                                                    @if ($wallet)
                                                        {{ translate('wallet') }} : <span class="icon-saudi_riyal"></span>
                                                        {{ number_format($wallet->balance_after, 2) }}
                                                    @else
                                                        {{ translate('wallet') }} : <span class="icon-saudi_riyal"></span>
                                                        0.00
                                                    @endif
                                                    <br>
                                                    <small class="text-gray-500">— {{ translate('Collected') }}: <span
                                                            class="icon-saudi_riyal"></span>
                                                        {{ number_format($collected, 2) }}</small>
                                                    <br>
                                                    <small class="text-gray-500">— {{ translate('Retrieved') }}: <span
                                                            class="icon-saudi_riyal"></span>
                                                        {{ number_format($retrieved, 2) }}</small>
                                                    <br>
                                                    <small class="text-gray-500">— {{ translate('Canceled') }}: <span
                                                            class="icon-saudi_riyal"></span>
                                                        {{ number_format($canceled, 2) }}</small>
                                                </td>
                                                <td class="text-center">
                                                    {{ $seller->created_at->format(dateFormat()) }}
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $status = $seller->sales->first()?->settlement_status ?? null;
                                                    @endphp

                                                    @if ($status === 'settled')
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ ucfirst($status) }}</span>
                                                    @else
                                                        <span class="badge badge-sm badge-outline badge-secondary">
                                                            {{ $status ? ucfirst($status) : translate('-') }}
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    {{ $seller->created_at->format(dateFormat()) }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="tooltip-container">
                                                        <span class="tooltip-icon">
                                                            <a class="btn btn-sm btn-icon btn-clear b
     @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $sellers])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
