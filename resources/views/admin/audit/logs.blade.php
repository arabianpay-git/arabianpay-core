@extends('layouts.base')
@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom Badge Styles */
        .badge-critical {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .badge-high {
            background-color: #FFFBEB;
            color: #92400E;
            border: 1px solid #FCD34D;
        }

        .badge-medium {
            background-color: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #93C5FD;
        }

        .badge-low {
            background-color: #F0FDF4;
            color: #166534;
            border: 1px solid #86EFAC;
        }

        .badge-info {
            background-color: #F8FAFC;
            color: #475569;
            border: 1px solid #E2E8F0;
        }

        .badge-success {
            background-color: #DCFCE7;
            color: #166534;
            border: 1px solid #86EFAC;
        }

        .badge-failed {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .badge-warning {
            background-color: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
        }

        /* Status Pills */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1;
        }

        .status-success {
            background-color: #DCFCE7;
            color: #166534;
        }

        .status-failed {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        /* Log Card Styles */
        .log-card {
            border-left: 4px solid;
            transition: all 0.2s ease;
        }

        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .log-card.critical {
            border-left-color: #DC2626;
        }

        .log-card.high {
            border-left-color: #F59E0B;
        }

        .log-card.medium {
            border-left-color: #3B82F6;
        }

        .log-card.low {
            border-left-color: #10B981;
        }

        .log-card.info {
            border-left-color: #6B7280;
        }

        /* Method Badges */
        .method-get {
            background-color: #DCFCE7;
            color: #166534;
        }

        .method-post {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .method-put {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .method-delete {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        .method-patch {
            background-color: #F3E8FF;
            color: #6B21A8;
        }

        /* Flatpickr Custom */
        .flatpickr-input {
            background-color: white !important;
            border: 1px solid #E5E7EB !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            width: 100% !important;
        }

        /* Custom Toast Styles */
        .swal2-popup {
            font-family: inherit !important;
        }

        /* Modal Styles - Same as audit trail */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(15, 23, 42, 0.5);
            align-items: flex-start;
            justify-content: center;
            padding: 2.5rem 1rem;
            overflow: auto;
        }

        .modal.open {
            display: flex;
        }

        .modal-content {
            width: 100%;
            max-width: 800px;
            background: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.2);
            max-height: calc(100vh - 120px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            flex: 0 0 auto;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-body {
            flex: 1 1 auto;
            overflow: auto;
            padding: 1.25rem;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }

        /* Helper Classes */
        .kv-list dt {
            font-weight: 600;
            font-size: 0.825rem;
            color: #374151;
        }

        .kv-list dd {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, "Roboto Mono", "Helvetica Neue", monospace;
            font-size: 0.775rem;
            color: #111827;
            margin-bottom: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Header Section -->
        <div class="container-fixed">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ translate('Security Audit Logs') }}
                    </h1>
                    <p class="text-gray-600">
                        {{ translate('Monitor and review all security events with SAMA CSF compliance') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('audit.trails') }}" class="btn btn-outline btn-secondary">
                        <i class="ki-filled ki-subtitle mr-2"></i>
                        {{ translate('Business Trails') }}
                    </a>
                    <button id="exportExcelBtn" class="btn btn-outline btn-success">
                        <i class="ki-filled ki-exit-down mr-2"></i>
                        {{ translate('Export Excel') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="container-fixed mb-8">
            <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                @php
                    $statCards = [
                        [
                            'icon' => 'ki-shield-cross',
                            'color' => 'red',
                            'title' => 'Critical Events',
                            'value' => $stats['critical_events'] ?? 0,
                            'trend' => '+12%',
                        ],
                        [
                            'icon' => 'ki-information-2',
                            'color' => 'yellow',
                            'title' => 'Warning Events',
                            'value' => $stats['warning_events'] ?? 0,
                            'trend' => '+5%',
                        ],
                        [
                            'icon' => 'ki-shield-tick',
                            'color' => 'blue',
                            'title' => 'Auth Events',
                            'value' => $stats['auth_events'] ?? 0,
                            'trend' => '-2%',
                        ],
                        [
                            'icon' => 'ki-document',
                            'color' => 'green',
                            'title' => 'Total Logs',
                            'value' => $stats['total_records'] ?? 0,
                            'trend' => '+8%',
                        ],
                        [
                            'icon' => 'ki-shield-search',
                            'color' => 'purple',
                            'title' => 'Failed Events',
                            'value' => $stats['failed_events'] ?? 0,
                            'trend' => '+15%',
                        ],
                        [
                            'icon' => 'ki-security-user',
                            'color' => 'orange',
                            'title' => 'High Sensitivity',
                            'value' => $stats['high_sensitive_count'] ?? 0,
                            'trend' => '+3%',
                        ],
                    ];
                @endphp

                @foreach ($statCards as $card)
                    <div class="card hover:shadow-md transition-shadow duration-200">
                        <div class="card-body">
                            <div class="flex items-center justify-between">
                                <div class="p-3 bg-{{ $card['color'] }}-100 rounded-xl mr-3">
                                    <i class="ki-filled {{ $card['icon'] }} text-{{ $card['color'] }}-600 text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-500 mb-1">{{ translate($card['title']) }}</p>
                                    <div class="flex items-baseline">
                                        <p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
                                        <span
                                            class="ml-2 text-xs {{ str_starts_with($card['trend'], '+') ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $card['trend'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Filters Card -->
        <div class="container-fixed mb-6">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="card-title flex items-center">
                        <i class="ki-filled ki-filter-search mr-2"></i>
                        {{ translate('Filter Logs') }}
                    </h3>
                    @if (!empty(array_filter($filters)))
                        <button onclick="window.location.href='{{ route('audit.logs') }}'"
                            class="btn btn-sm btn-outline btn-secondary">
                            <i class="ki-filled ki-cross-circle mr-2"></i>
                            {{ translate('Clear All') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('audit.logs') }}" id="filterForm">
                        <div class="grid grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="form-label">{{ translate('Event Category') }}</label>
                                <select name="log_category" class="select" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Categories') }}</option>
                                    @foreach ($filterOptions['log_categories'] ?? [] as $category)
                                        <option value="{{ $category }}"
                                            {{ ($filters['log_category'] ?? '') == $category ? 'selected' : '' }}>
                                            {{ $category }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label">{{ translate('Severity Level') }}</label>
                                <select name="severity" class="select" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Levels') }}</option>
                                    <option value="Critical"
                                        {{ ($filters['severity'] ?? '') == 'Critical' ? 'selected' : '' }}
                                        class="text-red-600">🔴 Critical</option>
                                    <option value="High" {{ ($filters['severity'] ?? '') == 'High' ? 'selected' : '' }}
                                        class="text-orange-600">🟠 High</option>
                                    <option value="Medium" {{ ($filters['severity'] ?? '') == 'Medium' ? 'selected' : '' }}
                                        class="text-blue-600">🔵 Medium</option>
                                    <option value="Low" {{ ($filters['severity'] ?? '') == 'Low' ? 'selected' : '' }}
                                        class="text-green-600">🟢 Low</option>
                                    <option value="Info" {{ ($filters['severity'] ?? '') == 'Info' ? 'selected' : '' }}
                                        class="text-gray-600">⚪ Info</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">{{ translate('Event Status') }}</label>
                                <select name="status" class="select" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Status') }}</option>
                                    <option value="success"
                                        {{ ($filters['status'] ?? '') == 'success' ? 'selected' : '' }}>Success</option>
                                    <option value="failed" {{ ($filters['status'] ?? '') == 'failed' ? 'selected' : '' }}>
                                        Failed</option>
                                    <option value="pending"
                                        {{ ($filters['status'] ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">{{ translate('Date Range') }}</label>
                                <input type="text" name="date_range" id="dateRange" class="flatpickr-input"
                                    placeholder="{{ translate('Select date range...') }}" data-mode="range"
                                    value="{{ ($filters['date_from'] ?? '') && ($filters['date_to'] ?? '') ? $filters['date_from'] . ' to ' . $filters['date_to'] : '' }}">
                                <input type="hidden" name="date_from" id="dateFrom"
                                    value="{{ $filters['date_from'] ?? '' }}">
                                <input type="hidden" name="date_to" id="dateTo"
                                    value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label class="form-label">{{ translate('Event Type') }}</label>
                                <select name="event_type" class="select" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Types') }}</option>
                                    @foreach ($filterOptions['event_types'] ?? [] as $type)
                                        <option value="{{ $type }}"
                                            {{ ($filters['event_type'] ?? '') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label">{{ translate('Resource') }}</label>
                                <select name="resource" class="select" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Resources') }}</option>
                                    @foreach ($filterOptions['resources'] ?? [] as $resource)
                                        <option value="{{ $resource }}"
                                            {{ ($filters['resource'] ?? '') == $resource ? 'selected' : '' }}>
                                            {{ $resource }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label">{{ translate('User Type') }}</label>
                                <select name="subject_type" class="select" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Users') }}</option>
                                    @foreach ($filterOptions['subject_types'] ?? [] as $type)
                                        <option value="{{ $type }}"
                                            {{ ($filters['subject_type'] ?? '') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="form-label">{{ translate('Authentication') }}</label>
                                <select name="idp_provider" class="select" onchange="this.form.submit()">
                                    @php
                                        $selectedIdp = $filters['idp_provider'] ?? '';
                                    @endphp

                                    <option value="">{{ translate('All Providers') }}</option>
                                    <option value="local" {{ $selectedIdp == 'local' ? 'selected' : '' }}>
                                        Local System
                                    </option>

                                    @foreach ($filterOptions['idp_providers'] ?? [] as $provider)
                                        @if (!is_null($provider))
                                            <option value="{{ $provider }}"
                                                {{ $selectedIdp == $provider ? 'selected' : '' }}>
                                                {{ $provider }}
                                            </option>
                                        @endif
                                    @endforeach

                                    <option value="__null__" {{ $selectedIdp === '__null__' ? 'selected' : '' }}>
                                        Not Available
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex-1 mr-4">
                                <label class="form-label">{{ translate('Search Logs') }}</label>
                                <div class="relative">
                                    <input type="text" name="search" class="input pr-10"
                                        placeholder="{{ translate('Search by user, IP, request ID...') }}"
                                        value="{{ $filters['search'] ?? '' }}">
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ki-filled ki-filter-search mr-2"></i>
                                    {{ translate('Apply Filters') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Audit Logs List -->
        <div class="container-fixed mb-8">
            <div class="card">
                <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="card-title">
                            <i class="ki-filled ki-document mr-2"></i>
                            {{ translate('Security Events') }}
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $auditLogs->total() }} {{ translate('events found') }}
                            @if (!empty(array_filter($filters)))
                                <span class="text-primary">({{ translate('Filtered') }})</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="input input-sm">
                            <i class="ki-filled ki-magnifier"></i>
                            <input type="text" id="liveSearch" placeholder="{{ translate('Search in list...') }}">
                        </div>
                    </div>
                </div>

                <!-- Logs List -->
                <div class="p-4" id="logsList">
                    @forelse($auditLogs as $log)
                        <div
                            class="log-card mb-4 {{ strtolower($log['severity']) }} bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                            <div class="p-5">
                                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                    <!-- Left Column -->
                                    <div class="flex-1">
                                        <div class="flex items-start gap-3 mb-3">
                                            <div class="p-2 rounded-lg bg-{{ $log['severity_color'] }}-100">
                                                <i
                                                    class="ki-filled {{ $log['log_category_icon'] }} text-{{ $log['severity_color'] }}-600 text-lg"></i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                                    <h4 class="font-semibold text-gray-900">{{ $log['event_type'] }}</h4>
                                                    <span class="badge badge-sm {{ $log['severity_color'] }}">
                                                        {{ $log['severity'] }}
                                                    </span>
                                                    <span class="status-pill status-{{ $log['status'] }}">
                                                        @if ($log['status'] == 'success')
                                                            <i class="ki-filled ki-check-circle mr-1"></i>
                                                        @elseif($log['status'] == 'failed')
                                                            <i class="ki-filled ki-cross-circle mr-1"></i>
                                                        @else
                                                            <i class="ki-filled ki-clock mr-1"></i>
                                                        @endif
                                                        {{ ucfirst($log['status']) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600">
                                                    {{ $log['description'] ?? $log['log_category'] }}</p>
                                            </div>
                                        </div>

                                        <!-- Details Grid -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                                            <div>
                                                <label class="text-xs text-gray-500 mb-1">{{ translate('User') }}</label>
                                                <div class="flex items-center gap-2">
                                                    <i class="ki-filled ki-user text-gray-400"></i>

                                                    <span
                                                        class="text-sm font-medium text-gray-900">{{ $log['subject_name']->first_name ?? '-' }}
                                                        {{ $log['subject_name']->last_name ?? '-' }}</span>
                                                    <span
                                                        class="text-xs text-gray-500">({{ $log['subject_type'] }})</span>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs text-gray-500 mb-1">{{ translate('Resource') }}</label>
                                                <div class="flex items-center gap-2">
                                                    <i class="ki-filled ki-server text-gray-400"></i>
                                                    <span
                                                        class="text-sm font-medium text-gray-900 truncate">{{ $log['resource'] }}</span>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs text-gray-500 mb-1">{{ translate('IP Address') }}</label>
                                                <div class="flex items-center gap-2">
                                                    <i class="ki-filled ki-wifi text-gray-400"></i>
                                                    <span
                                                        class="text-sm font-medium text-gray-900">{{ $log['ip_address'] }}</span>
                                                    @if (!empty($log['geo_location']))
                                                        <span
                                                            class="text-xs badge badge-info">{{ $log['geo_location'] }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs text-gray-500 mb-1">{{ translate('Endpoint') }}</label>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="px-2 py-1 text-xs font-medium rounded {{ $log['method_color'] }}">
                                                        {{ $log['method'] }}
                                                    </span>
                                                    <span
                                                        class="text-sm text-gray-900 truncate">{{ $log['endpoint'] }}</span>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs text-gray-500 mb-1">{{ translate('Authentication') }}</label>
                                                <div class="flex items-center gap-2">
                                                    <i class="ki-filled ki-shield-tick text-gray-400"></i>
                                                    <span class="text-sm text-gray-900">
                                                        {{ $log['idp_provider'] ?? 'Local System' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-xs text-gray-500 mb-1">{{ translate('Compliance') }}</label>
                                                <div class="flex items-center gap-2">
                                                    <span class="badge badge-sm {{ $log['pdpl_color'] }}">
                                                        {{ $log['pdpl_category'] }}
                                                    </span>
                                                    @if ($log['masking_state'] == 'Masked')
                                                        <span class="badge badge-sm badge-success">
                                                            <i class="ki-filled ki-lock mr-1"></i>
                                                            {{ $log['masking_state'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Additional Info -->
                                        <div class="mt-4 pt-4 border-t border-gray-100">
                                            <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                                <div class="flex items-center gap-1">
                                                    <i class="ki-filled ki-clock"></i>
                                                    <span>{{ $log['timestamp'] }}</span>
                                                    <span class="text-gray-400 ml-1">({{ $log['timestamp_diff'] }})</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <i class="ki-filled ki-barcode"></i>
                                                    <span>ID: {{ $log['log_id'] }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <i class="ki-filled ki-briefcase"></i>
                                                    <span>{{ $log['environment'] }}</span>
                                                </div>
                                                @if (!empty($log['pii_fields_array']))
                                                    <div class="flex items-center gap-1">
                                                        <i class="ki-filled ki-lock"></i>
                                                        <span>{{ count($log['pii_fields_array']) }} PII fields</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column - Actions -->
                                    <div class="lg:w-48 flex flex-col gap-2">
                                        <button class="btn btn-outline btn-primary text-center justify-center"
                                            onclick="loadLogDetails({{ $log['log_id'] }})">
                                            <i class="ki-filled ki-eye mr-2"></i>
                                            {{ translate('View Details') }}
                                        </button>

                                        @if ($log['severity'] == 'Critical' || $log['severity'] == 'High')
                                            <button class="btn btn-outline btn-warning create-alert-btn"
                                                data-log-id="{{ $log['log_id'] }}">
                                                <i class="ki-filled ki-notification mr-2"></i>
                                                {{ translate('Create Alert') }}
                                            </button>
                                        @endif

                                        @if ($log['status'] == 'failed' && $log['failure_reason'])
                                            <div class="mt-2 p-2 bg-red-50 rounded border border-red-200">
                                                <p class="text-xs text-red-600 font-medium">Failure:</p>
                                                <p class="text-xs text-red-500 truncate"
                                                    title="{{ $log['failure_reason'] }}">
                                                    {{ Str::limit($log['failure_reason'], 50) }}
                                                </p>
                                            </div>
                                        @endif

                                        <!-- Timestamp Box -->
                                        <div
                                            class="mt-2 p-2 bg-gray-100 rounded border border-gray-200 text-xs text-gray-700">
                                            <p class="font-medium">{{ translate('Timestamp') }}:</p>
                                            <p>{{ $log['timestamp'] }}</p>
                                            <span class="text-gray-500 text-xs">({{ $log['timestamp_diff'] }})</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                <i class="ki-filled ki-document text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ translate('No logs found') }}</h3>
                            <p class="text-gray-500 max-w-md mx-auto mb-6">
                                {{ translate('No security events match your current filters. Try adjusting your search criteria.') }}
                            </p>
                            @if (!empty(array_filter($filters)))
                                <a href="{{ route('audit.logs') }}" class="btn btn-primary">
                                    <i class="ki-filled ki-cross-circle mr-2"></i>
                                    {{ translate('Clear All Filters') }}
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @include('layouts.includes.table-pagination', ['paginator' => $auditLogs])

            </div>
        </div>
    </main>

    <!-- Log Details Modal -->
    <div class="modal" id="logDetailsModal">
        <div class="modal-content">
            <div class="modal-header py-4 px-5 border-b flex items-center justify-between">
                <h5 class="modal-title text-lg font-semibold">{{ translate('Audit Log Details') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true"
                    aria-label="Close">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>

            <div class="modal-body p-0" id="logDetailsBody">
                <div class="loading-spinner p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    <p class="mt-2 text-gray-600">{{ translate('Loading details...') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // ==================== MODAL HELPER FUNCTIONS ====================
        // Same as in audit trail page
        (function() {
            // Keep track of open modals via class 'open'
            // function updateBodyScrollLock() {
            //     const openModals = document.querySelectorAll('.modal.open');
            //     if (openModals.length > 0) {
            //         document.body.classList.add('overflow-hidden');
            //     } else {
            //         document.body.classList.remove('overflow-hidden');
            //     }
            // }

            function openModal(modalEl) {
                if (!modalEl) return;
                modalEl.classList.add('open');
                modalEl.style.display = 'flex';
                // updateBodyScrollLock();
            }

            function closeModal(modalEl) {
                if (!modalEl) return;
                modalEl.classList.remove('open');
                modalEl.style.display = 'none';
                // updateBodyScrollLock();
            }

            // wire dismiss buttons
            document.querySelectorAll('[data-modal-dismiss]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    let el = btn.closest('.modal');
                    if (!el) {
                        const selector = btn.getAttribute('data-modal-dismiss');
                        el = document.querySelector(selector);
                    }
                    closeModal(el);
                });
            });

            // close on backdrop click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            // close on ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
                }
            });

            // expose helpers for direct calls in other functions
            window.__openModal = openModal;
            window.__closeModal = closeModal;
        })();

        // ==================== FLATPICKR INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr for date range
            try {
                flatpickr("#dateRange", {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            document.getElementById('dateFrom').value =
                                instance.formatDate(selectedDates[0], "Y-m-d");
                            document.getElementById('dateTo').value =
                                instance.formatDate(selectedDates[1], "Y-m-d");
                            document.getElementById('filterForm').submit();
                        }
                    }
                });
            } catch (e) {
                console.warn('flatpickr init failed', e);
            }

            // Live search functionality
            const liveSearch = document.getElementById('liveSearch');
            if (liveSearch) {
                liveSearch.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const logCards = document.querySelectorAll('.log-card');

                    logCards.forEach(card => {
                        const text = card.textContent.toLowerCase();
                        card.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

            // Create alert buttons
            document.querySelectorAll('.create-alert-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const logId = this.dataset.logId;
                    createAlert(logId);
                });
            });

            // Export single log buttons
            document.querySelectorAll('.export-log-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const logId = this.dataset.logId;
                    exportSingleLog(logId);
                });
            });

            // Export all logs
            document.getElementById('exportAllBtn')?.addEventListener('click', function() {
                exportAllLogs();
            });
        });

        // ==================== LOG DETAILS FUNCTIONS ====================
        function loadLogDetails(logId) {
            const modal = document.getElementById('logDetailsModal');
            const body = document.getElementById('logDetailsBody');

            // Show loading spinner
            body.innerHTML = `
                <div class="loading-spinner p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    <p class="mt-2 text-gray-600">{{ translate('Loading details...') }}</p>
                </div>
            `;

            // Open modal
            window.__openModal(modal);

            // Fetch log details
            fetch(`{{ url('admin/audit/logs') }}/${logId}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderLogDetails(data.data);
                    } else {
                        showError('Failed to load log details');
                        window.__closeModal(modal);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load log details');
                    window.__closeModal(modal);
                });
        }

        function renderLogDetails(log) {
            const body = document.getElementById('logDetailsBody');

            // Format PII fields if available
            const piiFieldsHtml = log.pii_fields_array && log.pii_fields_array.length > 0 ? `
                <div class="border rounded-md p-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ki-filled ki-lock text-purple-600"></i>
                        {{ translate('PII Fields') }}
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        ${log.pii_fields_array.map(field => 
                            `<span class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">${escapeHtml(field)}</span>`
                        ).join('')}
                    </div>
                </div>
            ` : '';

            // Format failure reason if available
            const failureReasonHtml = log.failure_reason ? `
                <div class="border rounded-md p-4 border-red-200 bg-red-50">
                    <h4 class="font-medium text-red-900 mb-2 flex items-center gap-2">
                        <i class="ki-filled ki-cross-circle text-red-600"></i>
                        {{ translate('Failure Reason') }}
                    </h4>
                    <p class="text-sm text-red-700">${escapeHtml(log.failure_reason)}</p>
                </div>
            ` : '';

            // Build the HTML
            const html = `
                <div class="space-y-6 p-5">
                    <!-- Header Section -->
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">${escapeHtml(log.event_type)}</h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="badge badge-sm ${log.severity_color}">
                                        ${log.severity}
                                    </span>
                                    <span class="badge badge-sm ${log.log_category_color}">
                                        ${log.log_category}
                                    </span>
                                    <span class="status-pill status-${log.status}">
                                        ${log.status === 'success' ? '✅' : log.status === 'failed' ? '❌' : '⏳'} ${log.status.charAt(0).toUpperCase() + log.status.slice(1)}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">${escapeHtml(log.timestamp)}</div>
                                <div class="text-xs text-gray-400 mt-1">${escapeHtml(log.environment)}</div>
                            </div>
                        </div>
                        ${log.description ? `<p class="text-gray-600 mt-3">${escapeHtml(log.description)}</p>` : ''}
                    </div>

                    <!-- Grid Layout -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- User Information -->
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-user text-blue-600"></i>
                                {{ translate('User Information') }}
                            </h4>
                            <dl class="kv-list">
                                <dt>{{ translate('User') }}</dt>
                                <dd>${escapeHtml(log.subject_name.first_name) } ${escapeHtml(log.subject_name.last_name)}</dd>
                                <dt>{{ translate('Type') }}</dt>
                                <dd>${escapeHtml(log.subject_type)}</dd>
                            </dl>
                        </div>

                        <!-- Resource Information -->
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-artificial-intelligence text-green-600"></i>
                                {{ translate('Resource Information') }}
                            </h4>
                            <dl class="kv-list">
                                <dt>{{ translate('Resource') }}</dt>
                                <dd>${escapeHtml(log.resource)}</dd>
                                <dt>{{ translate('Endpoint') }}</dt>
                                <dd class="font-mono text-xs">${escapeHtml(log.endpoint)}</dd>
                                <dt>{{ translate('Method') }}</dt>
                                <dd><span class="px-2 py-0.5 rounded text-xs ${log.method_color}">${escapeHtml(log.method)}</span></dd>
                            </dl>
                        </div>

                        <!-- Authentication -->
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-shield-tick text-orange-600"></i>
                                {{ translate('Authentication') }}
                            </h4>
                            <dl class="kv-list">
                                <dt>{{ translate('Provider') }}</dt>
                                <dd>${escapeHtml(log.idp_provider || 'Local System')}</dd>
                                <dt>{{ translate('Request ID') }}</dt>
                                <dd class="font-mono text-xs break-all">${escapeHtml(log.request_id)}</dd>
                            </dl>
                        </div>

                        <!-- Network Information -->
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-wifi text-purple-600"></i>
                                {{ translate('Network Information') }}
                            </h4>
                            <dl class="kv-list">
                                <dt>{{ translate('IP Address') }}</dt>
                                <dd class="font-mono">${escapeHtml(log.ip_address)}</dd>
                                <dt>{{ translate('Device Fingerprint') }}</dt>
                                <dd class="font-mono text-xs break-all">${escapeHtml(log.device_fingerprint)}</dd>
                                ${log.geo_location ? `<dt>{{ translate('Location') }}</dt><dd>${escapeHtml(log.geo_location)}</dd>` : ''}
                            </dl>
                        </div>

                        <!-- Compliance Information -->
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-shield text-red-600"></i>
                                {{ translate('Compliance') }}
                            </h4>
                            <dl class="kv-list">
                                <dt>{{ translate('PDPL Category') }}</dt>
                                <dd><span class="badge badge-sm ${log.pdpl_color}">${escapeHtml(log.pdpl_category)}</span></dd>
                                <dt>{{ translate('Masking State') }}</dt>
                                <dd><span class="badge badge-sm ${log.masking_color}">${escapeHtml(log.masking_state)}</span></dd>
                            </dl>
                        </div>

                        <!-- Status Information -->
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ${log.status_icon} ${log.status === 'success' ? 'text-green-600' : log.status === 'failed' ? 'text-red-600' : 'text-yellow-600'}"></i>
                                {{ translate('Status Information') }}
                            </h4>
                            <dl class="kv-list">
                                <dt>{{ translate('Status') }}</dt>
                                <dd><span class="text-${log.status_color}">${escapeHtml(log.status.charAt(0).toUpperCase() + log.status.slice(1))}</span></dd>
                                <dt>{{ translate('Log ID') }}</dt>
                                <dd class="font-mono">${escapeHtml(log.log_id)}</dd>
                                <dt>{{ translate('Timestamp') }}</dt>
                                <dd>${escapeHtml(log.timestamp)} (${escapeHtml(log.timestamp_diff)})</dd>
                            </dl>
                        </div>
                    </div>

                    <!-- Additional Sections -->
                    ${piiFieldsHtml}
                    ${failureReasonHtml}

                    <!-- Actions Footer -->
                    <div class="border-t pt-4 flex justify-between items-center">
                        <div class="text-sm text-gray-500">
                            {{ translate('Showing details for log') }} #${escapeHtml(log.log_id)}
                        </div>
                    </div>
                </div>
            `;

            body.innerHTML = html;
        }

        // ==================== ALERT FUNCTIONS ====================
        function createAlert(logId) {
            Swal.fire({
                title: 'Create Security Alert',
                text: 'Do you want to create a security alert for this log entry?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, create alert!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Creating Alert...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`{{ url('admin/audit/logs') }}/${logId}/alert`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                log_id: logId,
                                action: 'create_alert'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Alert Created',
                                    text: data.message || 'Security alert created successfully',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to create alert',
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to create alert',
                            });
                        });
                }
            });
        }

        // ==================== EXPORT FUNCTIONS ====================
        function exportSingleLog(logId) {
            window.location.href = `{{ url('admin/audit/logs') }}/${logId}/export`;
        }

        function exportAllLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = `{{ url('admin/audit/logs/export') }}?${params.toString()}`;
        }

        // ==================== UTILITY FUNCTIONS ====================
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    </script>

    <script>
        // ==================== EXPORT FUNCTIONS ====================
        async function exportToCSV() {
            try {
                // Show progress dialog
                const progressSwal = Swal.fire({
                    title: '{{ translate('Preparing Export') }}',
                    html: `
                <div class="text-left">
                    <div class="mb-2">{{ translate('Collecting and formatting log data...') }}</div>
                    <div class="progress-bar-container">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                            <div id="exportProgressBar" class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600">
                            <span id="exportProgressText">0%</span>
                            <span id="exportStatusText">{{ translate('Initializing') }}</span>
                        </div>
                    </div>
                </div>
            `,
                    allowOutsideClick: false,
                    showCancelButton: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Update progress bar
                const updateProgress = (percent, status) => {
                    const progressBar = document.getElementById('exportProgressBar');
                    const progressText = document.getElementById('exportProgressText');
                    const statusText = document.getElementById('exportStatusText');

                    if (progressBar) progressBar.style.width = `${percent}%`;
                    if (progressText) progressText.textContent = `${percent}%`;
                    if (statusText) statusText.textContent = status;
                };

                // Build export URL with current filters
                const currentParams = new URLSearchParams(window.location.search);

                // Get the export URL (CSV version)
                const exportUrl = `{{ route('audit.logs.export') }}?${currentParams.toString()}`;

                // Simple redirect approach (works for CSV)
                updateProgress(50, '{{ translate('Generating CSV file') }}');

                // Create a hidden iframe for download
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                document.body.appendChild(iframe);

                // Set the iframe source to trigger download
                iframe.src = exportUrl;

                updateProgress(90, '{{ translate('Starting download') }}');

                // Wait a bit for download to start
                setTimeout(() => {
                    Swal.close();

                    Swal.fire({
                        title: '{{ translate('Export Complete') }}',
                        text: '{{ translate('Your CSV file should start downloading shortly.') }}',
                        icon: 'success',
                        showCancelButton: false,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: '{{ translate('OK') }}',
                        timer: 3000,
                        timerProgressBar: true,
                        didClose: () => {
                            // Remove iframe
                            if (iframe.parentNode) {
                                iframe.parentNode.removeChild(iframe);
                            }
                        }
                    });
                }, 1000);

            } catch (error) {
                console.error('Export error:', error);

                Swal.close();

                Swal.fire({
                    title: '{{ translate('Export Failed') }}',
                    text: '{{ translate('An error occurred while exporting. Please try again.') }}',
                    icon: 'error',
                    confirmButtonText: '{{ translate('OK') }}'
                });
            }
        }

        // Add event listener to export button (update button ID in HTML)
        document.getElementById('exportExcelBtn')?.addEventListener('click', exportToCSV);
    </script>
@endpush
