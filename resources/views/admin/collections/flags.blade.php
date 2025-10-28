@extends('layouts.base')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            /* Light palette variables */
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

        /* --- Card Styles --- */
        .flag-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            transition: background 0.18s ease;
            border-radius: 0.5rem;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            margin-bottom: 0.75rem;
        }

        .flag-card:hover {
            background: #fbfcfd;
        }

        /* --- Icon Wrapper --- */
        .flag-icon-wrap {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* --- Content & Title --- */
        .flag-content {
            flex: 1;
            min-width: 0;
        }

        .flag-title-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-weight: 500;
            color: #4b5563;
            font-size: 0.75rem;
            margin-bottom: 0.125rem;
        }

        .info-value {
            color: #1f2937;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* --- Flag Badge --- */
        .flag-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.15rem 0.5rem;
            border-radius: 0.325rem;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(2, 6, 23, 0.04);
        }

        /* ADDED: Definitions for flag-badge colors */
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

        /* --- Flag Tag (for 'Overdue', 'VIP', etc.) --- */
        .flag-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.625rem;
            font-weight: 600;
            margin: 0.125rem;
            box-shadow: 0 2px 6px rgba(2, 6, 23, 0.04);
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

        /* --- Filter Wrapper --- */
        .filters-wrapper {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        /* --- Text Color Helpers --- */
        .text-light-danger {
            color: var(--light-danger-text) !important;
        }

        .text-light-warning {
            color: var(--light-warning-text) !important;
        }

        .text-light-info {
            color: var(--light-info-text) !important;
        }

        /* --- Background Helpers --- */
        .bg-light-danger {
            background: var(--light-danger-bg) !important;
        }

        .bg-light-warning {
            background: var(--light-warning-bg) !important;
        }

        .bg-light-info {
            background: var(--light-info-bg) !important;
        }

        /* --- Muted text helper --- */
        .text-xs-muted {
            font-size: 0.75rem;
            color: var(--muted);
        }

        .input {
            height: 32px;
        }
    </style>
@endpush
@section('content')
    <main class="grow content pt-5">
        <div class="container-fixed">
            {{-- Page header --}}
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold">{{ translate('Priority Flags') }}</h1>
                    <p class="text-sm text-gray-500">{{ translate('High-priority flags requiring attention') }}</p>
                </div>
                {{-- Filters --}}
                <div class="filters-wrapper">
                    <input type="text" id="date-from" placeholder="From" class="input flatpickr-input" readonly
                        value="{{ $date_from ?? '' }}">
                    <input type="text" id="date-to" placeholder="To" class="input flatpickr-input" readonly
                        value="{{ $date_to ?? '' }}">
                    <button class="btn btn-primary btn-sm" id="apply-filters">
                        <i class="ki-filled ki-filter-search"> </i>
                        {{ translate('Apply') }}
                    </button>
                    <a href="{{ route('collections.flags') }}" class="btn btn-light btn-sm">
                        <i class="ki-filled ki-arrows-circle"> </i>{{ translate('Clear') }}
                    </a>
                </div>
            </div>
            {{-- Flags list --}}
            <div class="grid gap-3 mt-4">
                @if (count($flags) === 0)
                    <div class="text-center py-8 text-gray-500">{{ translate('No flags found for the selected filters.') }}
                    </div>
                @else
                    @foreach ($flags as $flag)
                        @php
                            $type = $flag['type'] ?? 'info';
                            $colorClass = match ($type) {
                                'high_risk' => 'danger',
                                'promise_due' => 'warning',
                                'allocation_pending' => 'info',
                                'failed_payment' => 'danger',
                                default => 'info',
                            };
                        @endphp
                        <div class="flag-card">
                            {{-- Icon Wrapper --}}
                            <div class="flag-icon-wrap bg-light-{{ $colorClass }}">
                                <i
                                    class="{{ $flag['icon'] ?? 'ki-filled ki-information-2' }} text-light-{{ $colorClass }}"></i>
                            </div>
                            {{-- Content --}}
                            <div class="flag-content">
                                <div class="flag-title-row">
                                    <div class="min-w-0 flex-1">
                                        {{-- Title and Description --}}
                                        <p class="font-semibold text-gray-900 mb-1">{{ $flag['title'] }}</p>
                                        <p class="text-sm text-gray-600 mb-2">{!! $flag['description'] !!}</p>

                                        {{-- Additional Information --}}
                                        @if (!empty($flag['additional_info']))
                                            <div class="additional-info">
                                                <div class="info-grid">
                                                    @foreach ($flag['additional_info'] as $key => $value)
                                                        @if (is_array($value) && in_array($key, ['users_with_promises', 'top_pending_items', 'users_affected']))
                                                            <div class="info-item">
                                                                <span
                                                                    class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                                <div class="info-value">
                                                                    @foreach ($value as $item)
                                                                        <span class="user-tag">
                                                                            {{ $item['name'] ?? '-' }}
                                                                            @if (isset($item['promise_amount']))
                                                                                (SAR
                                                                                {{ number_format($item['promise_amount'], 2) }})
                                                                            @elseif (isset($item['amount']))
                                                                                (SAR
                                                                                {{ number_format($item['amount'], 2) }})
                                                                            @endif
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @elseif (!is_array($value))
                                                            <div class="info-item">
                                                                <span
                                                                    class="info-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                                <span class="info-value">
                                                                    @if ($key === 'order_id')
                                                                        {!! $value !!} {{-- Render HTML safely for order_id --}}
                                                                    @else
                                                                        {{ $value }} {{-- Escape HTML for other values --}}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Tags/Mini Badges --}}
                                        @if (!empty($flag['tags']) && is_array($flag['tags']))
                                            <div class="flex gap-1 flex-wrap mt-2">
                                                @foreach ($flag['tags'] as $tag)
                                                    <span
                                                        class="flag-tag bg-light-{{ $colorClass }} text-light-{{ $colorClass }}">
                                                        <i
                                                            class="ki-filled ki-{{ $tag === 'Overdue' ? 'watch' : ($tag === 'VIP' ? 'star' : ($tag === 'Today' ? 'calendar' : ($tag === 'Promise' ? 'check' : ($tag === 'Pending' ? 'clock' : ($tag === 'Failed' ? 'cross' : 'time'))))) }} text-xs mr-1"></i>
                                                        {{ $tag }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    {{-- Status Badge and Action Button --}}
                                    <div class="text-right flex flex-col items-end gap-2 ml-4">
                                        <span
                                            class="flag-badge badge-{{ $colorClass }}">{{ ucfirst(str_replace('_', ' ', $flag['type'])) }}</span>
                                        {{-- <a href="#"
                                            class="btn btn-xs btn-light mt-1">{{ translate('Investigate') }}</a> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
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
