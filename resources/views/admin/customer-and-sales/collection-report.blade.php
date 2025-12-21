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
                        {{ translate('Collection Report') }}
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
                            {{ translate('Collection Report') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table"
                                        placeholder="{{ translate('Search customers') }}" type="text" value="" />
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
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Customer') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Payment Number') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Payment Date') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Tax Number') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Collected Amount') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Invoice Number Collected') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Total Invoice Amount') }}</span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Remaining Invoice Amount') }}</span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $index => $transaction)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-center">
                                                    {{ $transaction->user->first_name }}
                                                    {{ $transaction->user->last_name }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        — {{ $transaction->user->business_name ?? '—' }}
                                                    </small>
                                                </td>
                                                <td class="text-center">{{ $transaction->id }}</td>
                                                <td class="text-center">
                                                    {{ $transaction->created_at->format(dateFormat()) }}
                                                </td>
                                                <td class="text-center">
                                                    {{ $transaction->user->customer->tax_number ?? '-' }}</td>
                                                <td class="text-center">{{ number_format($transaction->collected, 2) }}
                                                </td>
                                                <td class="text-center">{{ $transaction->order->invoice_number ?? '-' }}
                                                </td>

                                                @php
                                                    $items = map_product_details($transaction->order->product_details);

                                                    $subTotal = $items->sum('total');

                                                    $tax = calculate_order_tax($transaction->order);
                                                    $shipping = $transaction->order->shipping_cost ?? 0;
                                                    $discount = $transaction->order->coupon_discount ?? 0;

                                                    $base = $subTotal + $tax + $shipping - $discount;
                                                    $remaining = $base - $transaction->collected;
                                                @endphp

                                                <td class="text-center">{{ number_format($base, 2) }}</td>
                                                <td class="text-center">{{ number_format($remaining, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @include('layouts.includes.table-pagination', ['paginator' => $transactions])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
