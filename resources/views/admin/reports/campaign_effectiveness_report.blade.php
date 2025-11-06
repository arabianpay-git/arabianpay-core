@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="main">
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Campaign Effectiveness Report') }}
                </h1>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Campaign Effectiveness Filter',
        ])

        <!-- Table -->
        <div class="container-fixed">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        {{ translate('Campaign Effectiveness Data') }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border" data-datatable-table="true">
                            <thead>
                                <tr>
                                    <th>{{ translate('Campaign ID') }}</th>
                                    <th>{{ translate('Target Segment') }}</th>
                                    <th>{{ translate('Offers Accepted %') }}</th>
                                    <th>{{ translate('Incremental Orders %') }}</th>
                                    <th>{{ translate('ROI') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    <tr>
                                        <td>{{ $campaign->campaign_id ?? '-' }}</td>
                                        <td>{{ $campaign->target_segment ?? '-' }}</td>
                                        <td>{{ $campaign->offers_accepted_percentage ?? '-' }}%</td>
                                        <td>{{ $campaign->incremental_orders_percentage ?? '-' }}%</td>
                                        <td>{{ $campaign->roi ?? '-' }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-gray-500">
                                            {{ translate('No campaign data available.') }}
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
