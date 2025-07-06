@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Payment of Suppliers') }}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Payment of Suppliers') }}
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
                                                        class="sort-label font-normal text-gray-700">{{ translate('Payment Invoice Number') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Payment Date') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Invoice Payment') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Tax Number') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Amount Paid') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Value Added Tax') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>

                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Total Bills') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($summary as $index => $item)
                                            <tr>
                                                {{-- Row number with pagination offset --}}
                                                <td class="text-center">
                                                    {{ $index + 1 + ($paginator->currentPage() - 1) * $paginator->perPage() }}
                                                </td>

                                                {{-- Seller name and business --}}
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $item['seller_name'] }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $item['seller_business'] }}
                                                        </small>
                                                    </div>
                                                </td>

                                                {{-- Payment Invoice Number --}}
                                                <td class="text-left">
                                                    {{ $item['invoice_number'] }}
                                                </td>

                                                {{-- Payment Date --}}
                                                <td class="text-center">
                                                    {{ $item['payment_date'] }}
                                                </td>

                                                {{-- Invoice Payment (BASE) --}}
                                                <td class="text-center">
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($item['payment_invoice'], 2) }}
                                                </td>

                                                {{-- Tax Number --}}
                                                <td class="text-center">
                                                    {{ $item['tax_number'] }}
                                                </td>

                                                {{-- Amount Paid (balance_after) --}}
                                                <td class="text-center">
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($item['amount_paid'], 2) }}
                                                </td>

                                                {{-- Value Added Tax --}}
                                                <td class="text-center">
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($item['tax_total'], 2) }}
                                                </td>

                                                {{-- Total Bills (BASE + commission + commission_tax) --}}
                                                <td class="text-center">
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($item['total_bills'], 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $paginator])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
