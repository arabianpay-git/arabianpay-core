@extends('layouts.base')

@section('content')

<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>
    <!-- End of Container -->
    
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Supplier Payouts
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
                        Supplier Payouts
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#team_crew_table" placeholder="Search users" type="text" value="" />
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
                                        <th class="w-[60px] text-center">No</th>
                                        <th class="text-left">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">Seller</span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="text-left">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">Reference</span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Date</span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Amount</span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Payment Details</span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sellers as $seller)
                                        @foreach ($seller->sellerWallet as $wallet)
                                            <tr>
                                                <td class="text-center">{{ $loop->parent->iteration }}</td>
                                                <td>
                                                    {{ $seller->first_name }} {{ $seller->last_name }}
                                                    <br>
                                                    <small class="text-gray-500">— {{ $seller->business_name ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    {{ $wallet->order_id }} <br>
                                                    <small class="text-gray-500">— {{ $wallet->created_at->format('Y-m-d') }}</small>
                                                </td>
                                                <td>
                                                    {{ $wallet->order_id }} <br>
                                                    <small class="text-gray-500">— {{ $wallet->created_at->format('Y-m-d') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="icon-saudi_riyal"></span> {{ number_format($wallet->amount, 2) }}
                                                </td>
                                                <td class="text-center">
                                                    {{ ucwords(str_replace('_', ' ', $wallet->transaction_type)) }}
                                                </td>                                                
                                            </tr>
                                        @endforeach
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
