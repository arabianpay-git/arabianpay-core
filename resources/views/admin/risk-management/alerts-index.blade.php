@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@endpush
@extends('layouts.base')

@section('content')
    <div class="container-fixed mt-4">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Risk Management Alerts</h1>
                <p class="text-gray-600 mt-1">Comprehensive view of all risk alerts and notifications</p>
            </div>

            <div class="flex items-center gap-2.5">
                <div class="flex items-center gap-2">
                    <div class="flex gap-2">
                        <input type="text" id="date-from" name="date_from" class="input" placeholder="From Date"
                            style="height: 32px;" value="{{ request('date_from', '') }}">
                        <input type="text" id="date-to" name="date_to" class="input" placeholder="To Date"
                            style="height: 32px;" value="{{ request('date_to', '') }}">

                        <button class="btn btn-sm btn-primary" id="filter-btn" type="button">
                            <i class="ki-filled ki-filter-search"> </i>
                            {{ translate('Filter') }}
                        </button>
                    </div>

                    <button class="btn btn-sm btn-light" id="clear-filters-btn" type="button">
                        <i class="ki-filled ki-arrows-circle"> </i>
                        {{ translate('Clear') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Alerts -->
            <div class="card border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300">
                <div class="card-body p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ki-filled ki-information-2 text-2xl text-blue-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Alerts</h3>
                            <p class="text-2xl font-semibold text-gray-900">{{ $totalAlerts }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Critical Alerts -->
            <div
                class="card border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-l-red-600">
                <div class="card-body p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ki-filled ki-shield-cross text-2xl text-red-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Critical</h3>
                            <p class="text-2xl font-semibold text-red-600">{{ $criticalCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- High Priority -->
            <div
                class="card border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-l-orange-500">
                <div class="card-body p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ki-filled ki-notification text-2xl text-orange-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">High Priority</h3>
                            <p class="text-2xl font-semibold text-orange-600">{{ $highCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medium Priority -->
            <div
                class="card border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 border-l-4 border-l-yellow-500">
                <div class="card-body p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="ki-filled ki-security-user text-2xl text-yellow-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Medium Priority</h3>
                            <p class="text-2xl font-semibold text-yellow-600">{{ $mediumCount }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Table -->
        <div class="card border border-gray-200 rounded-lg shadow-sm">
            <div class="card-header border-b border-gray-200 px-6 py-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h3 class="card-title font-semibold text-gray-900 text-lg">All Alerts</h3>

                    <!-- Filter Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <button class="btn btn-light btn-sm severity-filter active" data-severity="all">
                            All ({{ $totalAlerts }})
                        </button>
                        <button class="btn btn-light btn-sm severity-filter" data-severity="critical">
                            Critical ({{ $criticalCount }})
                        </button>
                        <button class="btn btn-light btn-sm severity-filter" data-severity="high">
                            High ({{ $highCount }})
                        </button>
                        <button class="btn btn-light btn-sm severity-filter" data-severity="medium">
                            Medium ({{ $mediumCount }})
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                @if (count($alerts) > 0)
                    <div class="divide-y divide-gray-100" id="alerts-container">
                        @foreach ($alerts as $alert)
                            <div class="relative flex items-start gap-4 p-6 hover:bg-gray-50 transition-colors duration-200 border-b border-gray-100 last:border-b-0 alert-item"
                                data-severity="{{ $alert['severity_level'] }}" data-type="{{ $alert['type'] }}">

                                <!-- Severity Indicator -->
                                <div
                                    class="absolute left-0 top-0 h-full w-1 rounded-l
                                @if ($alert['severity_level'] === 'critical') bg-red-600
                                @elseif($alert['severity_level'] === 'high') bg-orange-500
                                @elseif($alert['severity_level'] === 'medium') bg-yellow-500
                                @elseif($alert['severity_level'] === 'low') bg-blue-500
                                @else bg-gray-500 @endif">
                                </div>

                                <!-- Alert Icon -->
                                <div
                                    class="flex-shrink-0 w-10 h-10 rounded-[5px] flex items-center justify-center mt-1
                                @if ($alert['severity_level'] === 'critical') bg-red-50 text-red-600
                                @elseif($alert['severity_level'] === 'high') bg-orange-50 text-orange-600
                                @elseif($alert['severity_level'] === 'medium') bg-yellow-50 text-yellow-600
                                @elseif($alert['severity_level'] === 'low') bg-blue-50 text-blue-600
                                @else bg-gray-50 text-gray-600 @endif">
                                    <i class="{{ $alert['icon'] }} text-lg"></i>
                                </div>

                                <!-- Alert Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-2">
                                        <div class="flex items-center flex-wrap gap-3">
                                            <!-- Severity Badge -->
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-[5px] text-xs font-semibold
                                            @if ($alert['severity_level'] === 'critical') bg-red-100 text-red-800
                                            @elseif($alert['severity_level'] === 'high') bg-orange-100 text-orange-800
                                            @elseif($alert['severity_level'] === 'medium') bg-yellow-100 text-yellow-800
                                            @elseif($alert['severity_level'] === 'low') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                                {{ $alert['severity_badge'] }}
                                            </span>

                                            <!-- Title -->
                                            <p class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $alert['title'] }}
                                            </p>

                                            <!-- Type Badge -->
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-[5px] text-xs font-medium
                                            {{ $alert['type'] == 'alert' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                                {{ $alert['type'] == 'alert' ? 'Alert' : 'Risk Flag' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <!-- Time -->
                                            <span class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap">
                                                <i class="ki-filled ki-clock text-xs"></i>
                                                {{ $alert['created_at'] }}
                                            </span>

                                            <!-- Actions -->
                                            <div class="flex items-center transition-opacity duration-200">
                                                <button class="btn btn-outline btn-sm btn-primary"
                                                    onclick="toggleDetails('{{ $alert['id'] }}')" title="View Details">
                                                    <i class="ki-filled ki-eye text-base"></i>
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <p class="text-sm text-gray-600 mb-3 leading-relaxed">
                                        {!! $alert['description'] !!}
                                    </p>

                                    <!-- Additional Information (Collapsible) -->
                                    <div id="details-{{ $alert['id'] }}"
                                        class="hidden mt-4 p-6 bg-gray-50 rounded-lg border border-gray-200">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-4">Detailed Information</h4>

                                        @if (!empty($alert['additional_info']))
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                @foreach ($alert['additional_info'] as $key => $value)
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                                                            {{ str_replace('_', ' ', $key) }}
                                                        </span>
                                                        <div class="text-sm text-gray-900">
                                                            @if (is_array($value))
                                                                @if (in_array($key, [
                                                                        'top_affected_merchants',
                                                                        'top_low_capital_merchants',
                                                                        'top_high_activity_merchants',
                                                                        'top_high_exposure_accounts',
                                                                    ]))
                                                                    <div class="space-y-3">
                                                                        @foreach ($value as $merchant)
                                                                            <div
                                                                                class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                                                                                @if (isset($merchant['name']) && isset($merchant['link']))
                                                                                    <div
                                                                                        class="font-semibold text-gray-900 mb-3">
                                                                                        <a href="{{ $merchant['link'] }}"
                                                                                            class="underline hover:text-blue-600 transition-colors duration-200">
                                                                                            {{ $merchant['name'] }}
                                                                                        </a>
                                                                                    </div>
                                                                                @endif
                                                                                <div class="space-y-2">
                                                                                    @foreach ($merchant as $k => $v)
                                                                                        @if (!in_array($k, ['name', 'link']))
                                                                                            <div
                                                                                                class="flex justify-between items-start gap-4">
                                                                                                <span
                                                                                                    class="text-xs font-medium text-gray-500 capitalize flex-shrink-0">
                                                                                                    {{ str_replace('_', ' ', $k) }}:
                                                                                                </span>
                                                                                                <span
                                                                                                    class="text-sm text-gray-900 text-right break-words flex-1">
                                                                                                    {!! $v !!}
                                                                                                </span>
                                                                                            </div>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <ul class="list-disc list-inside space-y-1">
                                                                        @foreach ($value as $item)
                                                                            @if (is_array($item))
                                                                                <li class="text-sm">
                                                                                    @foreach ($item as $k => $v)
                                                                                        <span
                                                                                            class="font-medium text-gray-600">{{ $k }}:</span>
                                                                                        <span
                                                                                            class="text-gray-900">{!! $v !!}</span>
                                                                                        @if (!$loop->last)
                                                                                            <span
                                                                                                class="text-gray-300 mx-2">|</span>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </li>
                                                                            @else
                                                                                <li class="text-sm">{!! $item !!}
                                                                                </li>
                                                                            @endif
                                                                        @endforeach
                                                                    </ul>
                                                                @endif
                                                            @else
                                                                {!! $value !!}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500 text-center py-4">No additional information
                                                available.</p>
                                        @endif

                                        <!-- Action Buttons -->
                                        <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                                            <button class="btn btn-light btn-sm"
                                                onclick="toggleDetails('{{ $alert['id'] }}')">
                                                Close Details
                                            </button>
                                            @if ($alert['type'] == 'alert')
                                                <button class="btn btn-primary btn-sm"
                                                    onclick="investigateAlert('{{ $alert['id'] }}')">
                                                    Investigate
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Quick Stats -->
                                    @if (!empty($alert['additional_info']))
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            @php
                                                $quickStats = array_slice($alert['additional_info'], 0, 3);
                                            @endphp
                                            @foreach ($quickStats as $key => $value)
                                                @if (
                                                    !is_array($value) &&
                                                        in_array($key, [
                                                            'affected_merchants_count',
                                                            'low_capital_count',
                                                            'high_exposure_count',
                                                            'current_npl',
                                                            'current_utilization',
                                                            'capital_threshold',
                                                            'exposure_threshold',
                                                            'dpd_threshold',
                                                        ]))
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium
                                                    @if ($alert['severity_level'] === 'critical') bg-red-100 text-red-800
                                                    @elseif($alert['severity_level'] === 'high') bg-orange-100 text-orange-800
                                                    @elseif($alert['severity_level'] === 'medium') bg-yellow-100 text-yellow-800
                                                    @elseif($alert['severity_level'] === 'low') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                        {{ str_replace('_', ' ', $key) }}: {!! $value !!}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="ki-filled ki-check-circle text-green-500 text-4xl mb-3"></i>
                        <p class="text-gray-500 text-lg font-medium">No active alerts at this time</p>
                        <p class="text-sm text-gray-400 mt-1">All risk indicators are within normal thresholds</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            @if (count($alerts) > 0)
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="text-sm text-gray-500">
                            Showing {{ count($alerts) }} alerts
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('risk.dashboard') }}"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center transition-colors duration-200">
                                Back to Dashboard
                                <i
                                    class="ki-filled ki-arrow-right text-xs ml-1 group-hover:translate-x-1 transition-transform duration-200"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // ----------------------------
                // Initialize Flatpickr
                // ----------------------------
                const dateFromInput = document.getElementById('date-from');
                const dateToInput = document.getElementById('date-to');

                const dateFromPicker = flatpickr(dateFromInput, {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    onChange: function(selectedDates) {
                        if (selectedDates.length) dateToPicker.set('minDate', selectedDates[0]);
                        else dateToPicker.set('minDate', null);
                    }
                });

                const dateToPicker = flatpickr(dateToInput, {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    onChange: function(selectedDates) {
                        if (selectedDates.length) dateFromPicker.set('maxDate', selectedDates[0]);
                        else dateFromPicker.set('maxDate', null);
                    }
                });

                // ----------------------------
                // Filter button click
                // ----------------------------
                const filterBtn = document.getElementById('filter-btn');
                if (filterBtn) {
                    filterBtn.addEventListener('click', function() {
                        const dateFrom = dateFromInput.value;
                        const dateTo = dateToInput.value;
                        const url = new URL(window.location.href);
                        if (dateFrom) url.searchParams.set('date_from', dateFrom);
                        else url.searchParams.delete('date_from');
                        if (dateTo) url.searchParams.set('date_to', dateTo);
                        else url.searchParams.delete('date_to');
                        window.location.href = url.toString();
                    });
                }

                // ----------------------------
                // Clear filters button
                // ----------------------------
                const clearBtn = document.getElementById('clear-filters-btn');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        dateFromInput.value = '';
                        dateToInput.value = '';
                        dateFromPicker.set('maxDate', null);
                        dateToPicker.set('minDate', null);
                        const url = new URL(window.location.href);
                        url.searchParams.delete('date_from');
                        url.searchParams.delete('date_to');
                        window.location.href = url.toString();
                    });
                }

                // ----------------------------
                // Severity filtering
                // ----------------------------
                const severityFilters = document.querySelectorAll('.severity-filter');
                severityFilters.forEach(filter => {
                    filter.addEventListener('click', function() {
                        const severity = this.dataset.severity;

                        // Update active state
                        severityFilters.forEach(f => {
                            f.classList.remove('active', 'btn-primary');
                            f.classList.add('btn-light');
                        });
                        this.classList.remove('btn-light');
                        this.classList.add('btn-primary', 'active');

                        // Filter alerts
                        const alertItems = document.querySelectorAll('.alert-item');
                        alertItems.forEach(item => {
                            if (severity === 'all' || item.dataset.severity === severity) {
                                item.style.display = 'flex';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    });
                });

            }); // end DOMContentLoaded

            // ----------------------------
            // Toggle Details & Investigate
            // ----------------------------
            function toggleDetails(alertId) {
                const detailsElement = document.getElementById(`details-${alertId}`);
                detailsElement.classList.toggle('hidden');
            }

            function investigateAlert(alertId) {
                console.log('Investigating alert:', alertId);
                alert('Investigation feature would open here for alert: ' + alertId);
            }
        </script>
    @endpush
@endsection
