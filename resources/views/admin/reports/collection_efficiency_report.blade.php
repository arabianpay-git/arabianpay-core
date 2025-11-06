@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Collection Efficiency Report') }}
                    </h1>
                    <p class="text-gray-600">
                        {{ translate('Tracks recovery efficiency per merchant and collector') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Collection Efficiency Data') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#collection_efficiency_table"
                                        placeholder="{{ translate('Search collectors or merchants') }}" type="text"
                                        value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="collection_efficiency_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center">{{ translate('Collector ID') }}</th>
                                            <th>{{ translate('Recovery Rate (%)') }}</th>
                                            <th>{{ translate('Merchant List') }}</th>
                                            <th>{{ translate('Amount Recovered') }}</th>
                                            <th>{{ translate('Promises Kept') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($collectionData as $data)
                                            <tr>
                                                <td class="text-center">{{ $data->collector_id }}</td>
                                                <td>{{ number_format($data->recovery_rate, 2) }}%</td>
                                                <td>
                                                    @if (!empty($data->merchant_list) && is_array($data->merchant_list))
                                                        <ul class="list-disc list-inside">
                                                            @foreach ($data->merchant_list as $merchant)
                                                                <li>{{ $merchant }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        {{ translate('No merchants') }}
                                                    @endif
                                                </td>
                                                <td><span class="icon-saudi_riyal"></span>
                                                    {{ number_format($data->amount_recovered, 2) }}</td>
                                                <td>{{ $data->promises_kept }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-gray-500 py-4">
                                                    {{ translate('No collection data available.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination Footer --}}
                            @include('layouts.includes.table-pagination', ['paginator' => $collectionData])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
