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
                    Customer Sale Report
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
                        Customer Sale Report
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
                                                <span class="sort-label font-normal text-gray-700">Customer Name</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Tax Number</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Invoice Number</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Reference</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Invoice Amount</span>
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
                                                <span class="sort-label font-normal text-gray-700">Service Fee</span>
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Tax Invoice</span>
                                            </span>
                                        </th>

                                        <th class="text-center">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">Total Invoice</span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $index => $customer)
                                        @foreach($customer->orders as $order)
                                        @php
                                            $grandTotal = $order->grand_total;
                                            $shippingCost = $order->shipping_cost;
                                        
                                            $systemCommissionPercent = get_system_commission(0);
                                            $serviceFee = ($systemCommissionPercent / 100) * $grandTotal;
                                        
                                            $taxPercent = get_tax(0);
                                            $taxAmount = ($taxPercent / 100) * $grandTotal;
                                        
                                            $invoiceAmount = $grandTotal;
                                            $taxInvoice = $taxAmount;
                                        
                                            $totalInvoice = $invoiceAmount + $shippingCost + $serviceFee + $taxInvoice;
                                        @endphp
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $customer->user->first_name }} {{ $customer->user->last_name }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $customer->user->business_name }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ $customer->tax_number }}</td>
                                                <td class="text-center">{{ $order->invoice_number }}</td>
                                                <td class="text-center">{{ $order->tracking }}</td>
                                                <td class="text-center">{{ number_format($invoiceAmount, 2) }}</td>
                                                <td class="text-center">{{ $order->created_at->format('Y-m-d') }}</td>
                                                <td class="text-center">{{ number_format($shippingCost, 2) }}</td>
                                                <td class="text-center">{{ number_format($serviceFee, 2) }}</td>
                                                <td class="text-center">{{ number_format($taxInvoice, 2) }}</td>
                                                <td class="text-center">{{ number_format($totalInvoice, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @include('layouts.includes.table-pagination', ['paginator' => $customers])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection
