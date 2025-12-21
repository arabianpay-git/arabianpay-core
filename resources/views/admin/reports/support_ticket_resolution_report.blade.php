@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="main">
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    {{ translate('Support Ticket Resolution Report') }}
                </h1>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Support Ticket Resolution Filter',
        ])

        <!-- Table -->
        <div class="container-fixed">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        {{ translate('Support Ticket Resolution Data') }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border" data-datatable-table="true">
                            <thead>
                                <tr>
                                    <th>{{ translate('User') }}</th>
                                    <th>{{ translate('Ticket ID') }}</th>
                                    <th>{{ translate('Issue Type') }}</th>
                                    <th>{{ translate('Open Date') }}</th>
                                    <th>{{ translate('Resolution Date') }}</th>
                                    <th>{{ translate('SLA Breached?') }}</th>
                                    <th>{{ translate('Feedback Score') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tickets as $ticket)
                                    <tr>
                                        <td>
                                            @if (!empty($ticket->user_id))
                                                <div class="whitespace-nowrap">
                                                    <div class="font-medium text-sm text-gray-900">
                                                        ID: {{ $ticket->user_id }}
                                                    </div>
                                                    <div class="text-sm text-gray-700">
                                                        {{ $ticket->first_name ?? '-' }} {{ $ticket->last_name ?? '' }}
                                                    </div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $ticket->ticket_id ?? '-' }}</td>
                                        <td>{{ $ticket->issue_type ?? '-' }}</td>
                                        <td>{{ $ticket->open_date ? $ticket->open_date->format(dateFormat()) : '-' }}</td>
                                        <td>{{ $ticket->resolution_date ? $ticket->resolution_date->format(dateFormat()) : '-' }}
                                        </td>
                                        <td>
                                            @php
                                                $status = $ticket->status ?? null;
                                                $statusClasses = [
                                                    'active' => 'badge badge-outline badge-warning',
                                                    'solved' => 'badge badge-outline badge-success',
                                                    'draft' => 'badge badge-outline badge-secondary',
                                                    'canceled' => 'badge badge-outline badge-danger',
                                                ];
                                                $badgeClass =
                                                    $statusClasses[$status] ?? 'badge badge-outline badge-secondary';
                                            @endphp

                                            @if ($status)
                                                <span class="{{ $badgeClass }}">
                                                    {{ translate(ucfirst($status)) }}
                                                </span>
                                            @else
                                                -
                                            @endif

                                        </td>

                                        <td>{{ $ticket->feedback_score ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="flex gap-1 justify-center">
                                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                    href="{{ route('showTickets', $ticket->ticket_id) }}">
                                                    <i class="ki-filled ki-eye"> </i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-gray-500">
                                            {{ translate('No support ticket data available.') }}
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
