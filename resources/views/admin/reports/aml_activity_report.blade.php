@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Header Container -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('AML Activity Report') }}
                    </h1>
                    <p class="text-gray-600">
                        {{ translate('Suspicious activities detected, screened, or flagged under AML procedures') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('AML Suspicious Activities') }}
                        </h3>

                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#aml_activity_table"
                                        placeholder="{{ translate('Search alerts') }}" type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="aml_activity_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="text-center">{{ translate('Alert ID') }}</th>
                                            <th>{{ translate('Type') }}</th>
                                            <th>{{ translate('Screening Status') }}</th>
                                            <th>{{ translate('SAR Status') }}</th>
                                            <th>{{ translate('Submission Date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($amlActivities as $activity)
                                            <tr>
                                                <td class="text-center">{{ $activity->alert_id }}</td>
                                                <td>{{ $activity->type }}</td>
                                                <td>{{ $activity->screening_status }}</td>
                                                <td>
                                                    @php
                                                        $status = strtolower($activity->sar_status);
                                                        $badgeClass = 'badge badge-outline badge-secondary'; // default

                                                        if ($status === 'submitted') {
                                                            $badgeClass = 'badge badge-outline badge-success';
                                                        } elseif ($status === 'pending') {
                                                            $badgeClass = 'badge badge-outline badge-warning';
                                                        } elseif ($status === 'n/a' || $status === 'na') {
                                                            $badgeClass = 'badge badge-outline badge-info';
                                                        }
                                                    @endphp

                                                    <span class="{{ $badgeClass }}">
                                                        {{ $activity->sar_status }}
                                                    </span>
                                                </td>

                                                <td>{{ \Carbon\Carbon::parse($activity->submission_date)->format(dateFormat()) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-gray-500 py-4">
                                                    {{ translate('No suspicious AML activities found.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination Footer --}}
                            @include('layouts.includes.table-pagination', ['paginator' => $amlActivities])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
