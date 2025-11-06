@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="main">
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Product & SKU Performance Report') }}
                </h1>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Product & SKU Performance Filter',
        ])

        <!-- Table -->
        <div class="container-fixed">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        {{ translate('Product & SKU Performance Data') }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border" data-datatable-table="true">
                            <thead>
                                <tr>
                                    <th>{{ translate('Product ID') }}</th>
                                    <th class="text-right">{{ translate('Sales Volume') }}</th>
                                    <th class="text-right">{{ translate('Avg Time to Sell (days)') }}</th>
                                    <th class="text-right">{{ translate('Return Rate (%)') }}</th>
                                    <th class="text-right">{{ translate('Merchant Coverage') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($skuPerformanceData as $sku)
                                    <tr>
                                        <td>{{ $sku['product_id'] ?? '-' }}</td>
                                        <td class="text-right">{{ number_format($sku['sales_volume'] ?? 0) }}</td>
                                        <td class="text-right">{{ number_format($sku['avg_time_to_sell'] ?? 0, 2) }}</td>
                                        <td class="text-right">{{ number_format($sku['return_rate'] ?? 0, 2) }}%</td>
                                        <td class="text-right">{{ number_format($sku['merchant_coverage'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-gray-500">
                                            {{ translate('No SKU performance data available.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination (optional) -->
                    @if (isset($paginator))
                        @include('layouts.includes.table-pagination', ['paginator' => $paginator])
                    @endif
                </div>
            </div>
        </div>
    </main>
@endsection
