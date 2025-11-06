@extends('layouts.base')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            /* Color Palette */
            --light-danger-bg: #fee2e2;
            --light-danger-text: #b91c1c;
            --light-warning-bg: #fef3c7;
            --light-warning-text: #b45309;
            --light-info-bg: #dbeafe;
            --light-info-text: #1e40af;
            --muted: #6b7280;
            --card-border: #e6e6e6;
            --card-bg: #ffffff;
        }

        /* Card container styles */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.07), 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .card-body {
            padding: 1rem;
        }

        /* Alert row styles */
        .alert-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            transition: background 0.18s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .alert-card:hover {
            background: #fbfcfd;
        }

        .alert-card:last-child {
            border-bottom: none;
        }

        /* Icon wrapper */
        .alert-icon-wrap {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Content layout */
        .alert-content {
            flex: 1;
            min-width: 0;
        }

        .alert-title-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        /* Alert badge/tag */
        .alert-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.2rem 0.5rem;
            border-radius: 0.325rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* User name underline style */
        .underline {
            text-decoration: underline;
            font-weight: 600;
            color: #374151;
        }

        /* Additional info styles */
        .additional-info {
            background: #f8fafc;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.75rem;
            border-left: 3px solid #e2e8f0;
        }

        .info-item {
            display: flex;
            justify-content: between;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        .info-label {
            font-weight: 500;
            color: #4b5563;
            min-width: 120px;
        }

        .info-value {
            color: #1f2937;
            flex: 1;
        }

        .user-tag {
            display: inline-flex;
            align-items: center;
            background: #edf2f7;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin: 0.125rem;
            border: 1px solid #e2e8f0;
        }

        /* Text helpers */
        .text-xs-muted {
            font-size: 0.75rem;
            color: var(--muted);
        }

        /* Badge color definitions */
        .badge-danger {
            background: var(--light-danger-bg);
            color: var(--light-danger-text);
        }

        .badge-warning {
            background: var(--light-warning-bg);
            color: var(--light-warning-text);
        }

        .badge-info {
            background: var(--light-info-bg);
            color: var(--light-info-text);
        }

        /* Filters */
        .filters-wrapper {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .input {
            height: 32px;
        }

        /* Background helpers for icon wrappers */
        .bg-light-danger {
            background: var(--light-danger-bg) !important;
        }

        .bg-light-warning {
            background: var(--light-warning-bg) !important;
        }

        .bg-light-info {
            background: var(--light-info-bg) !important;
        }

        /* Text helpers for icons */
        .text-light-danger {
            color: var(--light-danger-text) !important;
        }

        .text-light-warning {
            color: var(--light-warning-text) !important;
        }

        .text-light-info {
            color: var(--light-info-text) !important;
        }
    </style>
@endpush

@section('content')
    <main class="grow content pt-5">
        <div class="container-fixed">

            {{-- Page header --}}
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold">{{ translate('All Alerts') }}</h1>
                    <p class="text-sm text-gray-500">{{ translate('View and filter system alerts') }}</p>
                </div>

                {{-- Filters --}}
                <div class="filters-wrapper flex gap-2">
                    <input type="text" id="date-from" placeholder="From" class="input flatpickr-input" readonly
                        value="{{ $date_from ?? '' }}">
                    <input type="text" id="date-to" placeholder="To" class="input flatpickr-input" readonly
                        value="{{ $date_to ?? '' }}">
                    <button class="btn btn-primary btn-sm" id="apply-filters">
                        <i class="ki-filled ki-filter-search"> </i>
                        {{ translate('Apply') }}
                    </button>
                    <a href="{{ route('collections.alerts') }}" class="btn btn-light btn-sm">
                        <i class="ki-filled ki-arrows-circle"> </i>
                        {{ translate('Clear') }}
                    </a>
                </div>
            </div>

            {{-- Alerts list --}}
            <div class="card mt-4">
                <div class="card-body p-0">
                    @if (count($alerts) === 0)
                        <div class="text-center py-8 text-gray-500">
                            {{ translate('No alerts found for the selected filters.') }}
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($alerts as $alert)
                                @php
                                    $type = $alert['type'] ?? 'info';
                                    $colorClass = match ($type) {
                                        'critical' => 'danger',
                                        'high' => 'warning',
                                        default => 'info',
                                    };
                                @endphp

                                <div class="alert-card flex gap-3 p-4 group">
                                    {{-- Icon --}}
                                    <div>
                                        <div class="alert-icon-wrap bg-light-{{ $colorClass }}">
                                            <i
                                                class="{{ $alert['icon'] ?? 'ki-filled ki-information-2' }} text-light-{{ $colorClass }}"></i>
                                        </div>
                                    </div>

                                    {{-- Content --}}
                                    <div class="alert-content flex-1">
                                        <div class="alert-title-row flex justify-between">
                                            <div class="min-w-0 flex-1">
                                                <p class="font-semibold text-gray-900 mb-1 truncate">{{ $alert['title'] }}
                                                </p>
                                                <p class="text-sm text-gray-600 mb-2 truncate">{!! $alert['description'] !!}</p>

                                                {{-- Additional info --}}
                                                @if (!empty($alert['additional_info']))
                                                    <div class="additional-info space-y-1 mt-2">
                                                        @foreach ($alert['additional_info'] as $key => $value)
                                                            @if (is_array($value) && isset($value[0]['name']))
                                                                <div class="info-item">
                                                                    <span
                                                                        class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                                    <div class="info-value flex flex-wrap gap-1">
                                                                        @foreach ($value as $item)
                                                                            <span
                                                                                class="user-tag bg-gray-100 px-2 py-1 rounded text-sm">
                                                                                @if (isset($item['link']))
                                                                                    <a href="{{ $item['link'] }}"
                                                                                        class="underline">{{ $item['name'] ?? '-' }}</a>
                                                                                @else
                                                                                    {{ $item['name'] ?? '-' }}
                                                                                @endif

                                                                                @if (isset($item['dpd']))
                                                                                    ({{ $item['dpd'] }}
                                                                                    {{ $item['dpd'] === 1 ? 'Day' : 'Days' }})
                                                                                @elseif (isset($item['amount']))
                                                                                    (SAR
                                                                                    {{ number_format($item['amount'], 2) }})
                                                                                @elseif (isset($item['promise_amount']))
                                                                                    (SAR
                                                                                    {{ number_format($item['promise_amount'], 2) }})
                                                                                @elseif (isset($item['broken_promises_count']))
                                                                                    ({{ $item['broken_promises_count'] }}
                                                                                    broken)
                                                                                @endif
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @elseif ($key === 'order_id')
                                                                <div class="info-item">
                                                                    <span
                                                                        class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                                    <span class="info-value">{!! $value !!}</span>
                                                                </div>
                                                            @else
                                                                <div class="info-item">
                                                                    <span
                                                                        class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                                    @if (is_array($value))
                                                                        <pre class="info-value">{{ json_encode($value['due'], JSON_PRETTY_PRINT) }}</pre>
                                                                    @else
                                                                        <span class="info-value">{{ $value }}</span>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Badge and time --}}
                                            <div class="text-right ml-4 flex flex-col items-end gap-1">
                                                <span
                                                    class="alert-badge badge-{{ $colorClass }}">{{ ucfirst($type) }}</span>
                                                <p class="text-xs text-gray-400 whitespace-nowrap">
                                                    {{ $alert['time'] ?? '' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#date-from", {
                dateFormat: "Y-m-d"
            });
            flatpickr("#date-to", {
                dateFormat: "Y-m-d"
            });

            document.getElementById('apply-filters').addEventListener('click', function() {
                const from = document.getElementById('date-from').value;
                const to = document.getElementById('date-to').value;
                const params = new URLSearchParams(window.location.search);
                if (from) params.set('date_from', from);
                else params.delete('date_from');
                if (to) params.set('date_to', to);
                else params.delete('date_to');
                window.location.href = window.location.pathname + '?' + params.toString();
            });
        });
    </script>
@endpush
