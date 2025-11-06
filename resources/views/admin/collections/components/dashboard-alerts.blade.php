@push('styles')
    <style>
        /* ---------- Light palette ---------- */
        :root {
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

        /* ---------- Card header badge (right side) ---------- */
        .card-header {
            position: relative;
        }

        .header-badge {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            padding: 0.35rem 0.6rem;
            border-radius: 0.325rem;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: 0 4px 10px rgba(2, 6, 23, 0.06);
        }

        /* light badge variants */
        .badge-light-danger {
            background: var(--light-danger-bg);
            color: var(--light-danger-text);
        }

        .badge-light-warning {
            background: var(--light-warning-bg);
            color: var(--light-warning-text);
        }

        .badge-light-info {
            background: var(--light-info-bg);
            color: var(--light-info-text);
        }

        /* ---------- Item row / small badges ---------- */
        .alerts-row {
            padding: 1rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: background .18s ease;
        }

        .alerts-row:hover {
            background: #fbfcfd;
        }

        .alerts-icon {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            flex-shrink: 0;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert-inside-icon {
            font-size: 1rem;
            line-height: 1;
        }


        .alerts-content {
            flex: 1 1 auto;
            min-width: 0;
        }

        .alerts-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* badge will be at far right */
            gap: 0.75rem;
        }

        .item-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1px 0.55rem;
            border-radius: 0.325rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(2, 6, 23, 0.04);
        }

        /* make tag/mini badges (under title) */
        .flag-tag {
            margin-top: 0.725rem;
            display: inline-flex;
            align-items: center;
            padding: 1px 0.5rem;
            border-radius: 5px;
            font-size: 0.625rem;
            font-weight: 600;
        }

        /* ---------- Light icon container for flags ---------- */
        .flag-icon-wrap {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light-info-bg);
            transition: transform .18s ease, background .18s ease;
        }

        .flag-icon-wrap:hover {
            transform: scale(1.06);
        }

        /* text colors helpers (used inline in blade via class) */
        .text-light-danger {
            color: var(--light-danger-text) !important;
        }

        .text-light-warning {
            color: var(--light-warning-text) !important;
        }

        .text-light-info {
            color: var(--light-info-text) !important;
        }

        /* bg helpers for small indicators */
        .bg-light-danger {
            background: var(--light-danger-bg) !important;
        }

        .bg-light-warning {
            background: var(--light-warning-bg) !important;
        }

        .bg-light-info {
            background: var(--light-info-bg) !important;
        }

        /* ensure header badge doesn't overlap on very small screens */
        @media (max-width: 480px) {
            .card-header {
                padding-right: 1rem;
            }

            .header-badge {
                position: static;
                transform: none;
                margin-top: 0.5rem;
                display: inline-block;
            }
        }
    </style>
@endpush

<div class="container-fixed mb-7.5">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Real-time Alerts --}}
        <div class="card">
            <div class="card-header border-b border-gray-200 pb-4">
                <div>
                    <h3 class="card-title font-semibold text-base text-gray-900">
                        {{ translate('Real-time Alerts') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ translate('System notifications and warnings') }}</p>
                </div>

                {{-- Header badge placed on the right edge --}}
                <span class="header-badge badge-light-danger">
                    {{ count($alerts) }} {{ count($alerts) === 1 ? 'New' : 'New Alerts' }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="divide-y divide-gray-100">
                    @foreach ($alerts as $alert)
                        @php
                            $c =
                                $alert['type'] === 'critical'
                                    ? 'danger'
                                    : ($alert['type'] === 'high'
                                        ? 'warning'
                                        : 'info');
                        @endphp

                        <div class="alerts-row group">
                            {{-- small round indicator --}}
                            <div class="alerts-icon bg-light-{{ $c }}">
                                <i
                                    class="{{ $alert['icon'] }} text-light-{{ $c }} mr-2 alert-inside-icon"></i>
                            </div>

                            {{-- content --}}
                            <div class="alerts-content">
                                <div class="alerts-title-row">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $alert['title'] }}
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1 truncate">
                                            {!! $alert['description'] !!}
                                        </p>
                                    </div>

                                    {{-- item badge at right of row --}}
                                    <div>
                                        <span class="item-badge badge-light-{{ $c }}">
                                            {{ ucfirst($alert['type']) }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-400 mt-2">
                                    <i class="ki-filled ki-clock text-xs mr-1"></i>
                                    {{ $alert['time'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <a href="{{ route('collections.alerts') }}"
                        class="text-sm font-semibold text-primary hover:text-primary-dark flex items-center justify-center group">
                        {{ translate('View All Alerts') }}
                        <i
                            class="ki-filled ki-arrow-right text-xs ml-1 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Priority Flags --}}
        <div class="card">
            <div class="card-header border-b border-gray-200 pb-4">
                <div>
                    <h3 class="card-title font-semibold text-base text-gray-900">
                        {{ translate('Priority Flags') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ translate('Accounts requiring attention') }}</p>
                </div>

                {{-- Header badge placed on the right edge --}}
                <span class="header-badge badge-light-warning">
                    {{ count($flags) }} {{ count($flags) === 1 ? 'Active' : 'Active Flags' }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="divide-y divide-gray-100">
                    @foreach ($flags as $flag)
                        @php
                            $c =
                                $flag['type'] === 'high_risk'
                                    ? 'danger'
                                    : ($flag['type'] === 'promise_due'
                                        ? 'warning'
                                        : 'info');
                        @endphp

                        <div class="alerts-row group">
                            {{-- icon box --}}
                            <div>
                                <div class="flag-icon-wrap bg-light-{{ $c }}">
                                    <i class="{{ $flag['icon'] }} text-light-{{ $c }}"></i>
                                </div>
                            </div>

                            <div class="alerts-content">
                                <div class="alerts-title-row">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $flag['title'] }}
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1 truncate">
                                            {!! $flag['description'] !!}
                                        </p>
                                    </div>

                                    {{-- item badge at right of row --}}
                                    <div>
                                        <span class="item-badge badge-light-{{ $c }}">
                                            {{ ucfirst(str_replace('_', ' ', $flag['type'])) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mt-3 flex-wrap">
                                    @foreach ($flag['tags'] as $tag)
                                        <span
                                            class="flag-tag bg-light-{{ $c }} text-light-{{ $c }}">
                                            <i
                                                class="ki-filled ki-{{ $tag === 'Overdue' ? 'watch' : ($tag === 'VIP' ? 'star' : ($tag === 'Today' ? 'calendar' : 'time')) }} text-xs mr-1"></i>
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <a href="{{ route('collections.flags') }}"
                        class="text-sm font-semibold text-primary hover:text-primary-dark flex items-center justify-center group">
                        {{ translate('View All Flags') }}
                        <i
                            class="ki-filled ki-arrow-right text-xs ml-1 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
