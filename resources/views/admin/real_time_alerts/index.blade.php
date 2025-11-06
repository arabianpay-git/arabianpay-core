@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Real-Time Alerts') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Alerts') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#alerts_table" placeholder="{{ translate('Search') }}"
                                        type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="alerts_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">ID</th>
                                            <th>{{ translate('User') }}</th>
                                            <th>{{ translate('Risk Score') }}</th>
                                            <th>{{ translate('Activity') }}</th>
                                            <th>{{ translate('Transaction') }}</th>
                                            <th>{{ translate('Created At') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($alerts as $item)
                                            <tr>
                                                <td class="text-center">{{ $item->id }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $item->user?->first_name }} {{ $item->user?->last_name }}
                                                        <br>
                                                        <small class="text-gray-500">
                                                            — {{ $item->user?->business_name ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>

                                                <td>{{ $item->risk_score ?? '-' }}</td>
                                                <td>{{ $item->activity_name ?? '-' }}</td>
                                                <td>Transaction Refrence: {{ $item->transaction->refrence_payment ?? '-' }}
                                                </td>
                                                <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $alerts])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
