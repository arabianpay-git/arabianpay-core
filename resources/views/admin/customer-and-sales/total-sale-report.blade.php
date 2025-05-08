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
                    Total Sale Report
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
                        Total Sale Report
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#team_crew_table" placeholder="Search customers" type="text" value="" />
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
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Number of Invoices</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">From Invoice Number</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">To Invoice Number</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Invoice Amount</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">VAT Amount</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Order Date</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Shipping Cost</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Total Invoice Amount</span>
                                            </span>
                                        </th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">1</td>
                                        <td class="text-center">{{ $invoiceCount }}</td>
                                        <td class="text-center">{{ $invoiceFrom }}</td>
                                        <td class="text-center">{{ $invoiceTo }}</td>
                                        <td class="text-center">{{ number_format($grandTotal, 2) }}</td>
                                        <td class="text-center">{{ number_format($taxAmount, 2) }}</td>
                                        <td class="text-center">{{ $orderDateFrom }} - {{ $orderDateTo }}</td>
                                        <td class="text-center">{{ number_format($shippingTotal, 2) }}</td>
                                        <td class="text-center font-semibold text-primary">{{ number_format($totalInvoice, 2) }}</td>
                                    </tr>
                                </tbody>
                                
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection
