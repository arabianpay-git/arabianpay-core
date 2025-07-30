@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Header Container -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Delinquency Aging Report') }}
                    </h1>
                </div>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Delinquency Aging Filter',
        ])

        <!-- Table Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Delinquency Aging Data') }}
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Merchant ID') }}</th>
                                        <th class="text-right">{{ translate('DPD 1-15') }}</th>
                                        <th class="text-right">{{ translate('DPD 16-30') }}</th>
                                        <th class="text-right">{{ translate('DPD 31-60') }}</th>
                                        <th class="text-right">{{ translate('Over 60') }}</th>
                                        <th class="text-right">{{ translate('Total Outstanding') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($delinquencyData as $merchantId => $data)
                                        @php
                                            $merchant = $merchants->get($merchantId);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="whitespace-nowrap">
                                                    <strong>ID:</strong> {{ $merchantId }} <br>
                                                    <strong>Name:</strong>
                                                    {{ ($merchant->first_name ?? '-') . ' ' . ($merchant->last_name ?? '-') }}
                                                    <br>
                                                    <small class="text-gray-500">
                                                        &mdash; {{ $merchant->business_name ?? '—' }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td class="text-right">{{ number_format($data['dpd_1_15'] ?? 0, 2) }}</td>
                                            <td class="text-right">{{ number_format($data['dpd_16_30'] ?? 0, 2) }}</td>
                                            <td class="text-right">{{ number_format($data['dpd_31_60'] ?? 0, 2) }}</td>
                                            <td class="text-right">{{ number_format($data['dpd_over_60'] ?? 0, 2) }}</td>
                                            <td class="text-right font-semibold">
                                                {{ number_format($data['total_outstanding'] ?? 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-6 text-gray-500">
                                                {{ translate('No delinquency data available.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        @if (isset($paginator))
                            @include('layouts.includes.table-pagination', ['paginator' => $paginator])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
