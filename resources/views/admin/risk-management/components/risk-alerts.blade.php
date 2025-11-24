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

        .additional-badges {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .additional-badges span {
            font-size: 0.65rem !important;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 0.25rem;
        }

        .flag-tag {
            margin-top: 0.725rem;
            display: inline-flex;
            align-items: center;
            padding: 1px 0.5rem;
            border-radius: 5px;
            font-size: 0.625rem;
            font-weight: 600;
        }

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

        .text-light-danger {
            color: var(--light-danger-text) !important;
        }

        .text-light-warning {
            color: var(--light-warning-text) !important;
        }

        .text-light-info {
            color: var(--light-info-text) !important;
        }

        .bg-light-danger {
            background: var(--light-danger-bg) !important;
        }

        .bg-light-warning {
            background: var(--light-warning-bg) !important;
        }

        .bg-light-info {
            background: var(--light-info-bg) !important;
        }

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

<div class="container-fixed mt-4">
    {{-- Merged Active Alerts --}}
    @php
        $allAlerts = array_merge($activeAlerts ?? [], $riskFlags ?? []);
    @endphp

    <div class="card">
        <div class="card-header border-b border-gray-200 pb-4">
            <div>
                <h3 class="card-title font-semibold text-base text-gray-900">
                    {{ translate('Active Alerts') }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ translate('Critical risk indicators requiring immediate attention') }}
                </p>
            </div>

            <span class="header-badge badge-light-danger">
                {{ count($allAlerts) }} {{ count($allAlerts) === 1 ? 'Alert' : 'Alerts' }}
            </span>
        </div>

        <div class="card-body p-0">
            <div class="divide-y divide-gray-100">
                @forelse($allAlerts as $alert)
                    @php
                        $typeClass = $alert['type'] ?? 'info';
                        $c = match ($typeClass) {
                            'critical', 'highest_risk', 'high_risk' => 'danger',
                            'high', 'concentration_risk', 'promise_due' => 'warning',
                            default => 'info',
                        };

                        // Extract number from title
                        preg_match('/^\d+/', $alert['title'], $matches);
                        $number = $matches[0] ?? null;
                        $titleText = trim(str_replace($number, '', $alert['title']));
                    @endphp

                    <div class="alerts-row group">
                        <div class="alerts-icon bg-light-{{ $c }}">
                            <i
                                class="{{ $alert['icon'] ?? 'ki-filled ki-information-2' }} text-light-{{ $c }} alert-inside-icon"></i>
                        </div>

                        <div class="alerts-content">
                            <div class="alerts-title-row">
                                <div class="min-w-0 flex items-center gap-2">
                                    @if ($number)
                                        <span
                                            class="item-badge badge-light-{{ $c }}">{{ $number }}</span>
                                    @endif
                                    <p class="text-sm font-semibold text-gray-900 truncate">
                                        {{ $titleText }}
                                    </p>
                                </div>
                                <div>
                                    <span class="item-badge badge-light-{{ $c }}">
                                        {{ ucfirst(str_replace('_', ' ', $typeClass)) }}
                                    </span>
                                </div>
                            </div>

                            <p class="text-sm text-gray-600 mt-1 truncate">
                                {!! $alert['description'] ?? '' !!}
                            </p>

                            <p class="text-xs text-gray-400 mt-2">
                                <i class="ki-filled ki-clock text-xs mr-1"></i>
                                {{ $alert['time'] ?? '' }}
                            </p>

                            {{-- Additional Info Badges --}}
                            @if (!empty($alert['additional_info']))
                                <div class="additional-badges">
                                    @foreach ($alert['additional_info'] as $key => $value)
                                        @if (in_array($key, ['low_capital_count', 'capital_threshold', 'affected_accounts_count', 'dpd_threshold']))
                                            <span class="bg-light-{{ $c }} text-light-{{ $c }}">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}: {!! $value !!}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Optional tags for flags --}}
                            @if (isset($alert['tags']) && is_array($alert['tags']))
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    @foreach ($alert['tags'] as $tag)
                                        <span
                                            class="flag-tag bg-light-{{ $c }} text-light-{{ $c }}">
                                            <i
                                                class="ki-filled ki-{{ $tag === 'Overdue' ? 'watch' : ($tag === 'VIP' ? 'star' : ($tag === 'Today' ? 'calendar' : 'time')) }} text-xs mr-1"></i>
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="ki-filled ki-check-circle text-green-500 text-4xl mb-3"></i>
                        <p class="text-gray-500">{{ translate('No active alerts at this time') }}</p>
                        <p class="text-sm text-gray-400 mt-1">
                            {{ translate('All risk indicators are within normal thresholds') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <a href="{{ route('risk.alerts') }}"
                    class="text-sm font-semibold text-primary hover:text-primary-dark flex items-center justify-center group">
                    {{ translate('View All Alerts') }}
                    <i class="ki-filled ki-arrow-right text-xs ml-1 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>
</div>
