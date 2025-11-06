@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Header Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Supplier Transaction Report') }}
                    </h1>
                </div>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Supplier Transaction Filter',
        ])

        <!-- Table Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Supplier Transactions') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="supplier_transaction_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center">{{ translate('Supplier ID') }}</th>
                                            <th>{{ translate('Supplier Name') }}</th>
                                            <th>{{ translate('Order Volume') }}</th>
                                            <th>{{ translate('Fulfilled %') }}</th>
                                            <th>{{ translate('Total Payouts') }}</th>
                                            <th>{{ translate('Disputes') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($suppliers as $supplier)
                                            <tr>
                                                <td class="text-center">{{ $supplier->id }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $supplier->user->first_name ?? '-' }}
                                                        {{ $supplier->user->last_name ?? '-' }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $supplier->user->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ $supplier->order_volume }}
                                                </td>
                                                <td>
                                                    {{ $supplier->fulfillment_count }} delivered /
                                                    {{ $supplier->fulfillment_percentage }}%
                                                </td>
                                                <td>
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($supplier->total_payouts, 2) }}
                                                </td>
                                                <td>
                                                    {{ $supplier->dispute_count }}
                                                    @if ($supplier->dispute_percentage !== null)
                                                        ({{ $supplier->dispute_percentage }}%)
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $suppliers])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
