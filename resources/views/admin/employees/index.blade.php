@extends('layouts.base')

@push('styles')
    <style>
        .perm-tooltip-wrapper {
            position: relative;
            display: inline-block;
            outline: none;
        }

        .perm-tooltip {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            min-width: 220px;
            max-width: 420px;
            z-index: 2147483647;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
            border-radius: 0.5rem;
            padding: 0.6rem;
            max-height: 320px;
            overflow: auto;
            transform-origin: top left;
            will-change: transform, opacity;
            visibility: hidden;
            opacity: 0;
            transform: translateY(-6px) scale(0.995);
            transition: opacity 140ms ease, transform 140ms ease, visibility 0ms linear 140ms;
            pointer-events: none;
        }

        .perm-tooltip.visible {
            visibility: visible !important;
            opacity: 1 !important;
            transform: translateY(0) scale(1);
            transition-delay: 0s;
            pointer-events: auto;
        }

        .perm-chip {
            display: inline-block;
            margin: 0.2rem 0.35rem 0.35rem 0;
            padding: 0.35rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            line-height: 1;
            background: #0ea5a4;
            color: #fff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            white-space: nowrap;
        }

        .perm-chip.sensitive {
            background: #1e3a8a;
        }

        .perm-tooltip .title {
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
            color: #111827;
        }

        .perm-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.65rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: #f8fafc;
            color: #0f172a;
            cursor: pointer;
            user-select: none;
        }

        .perm-trigger .count {
            background: rgba(16, 185, 129, 0.12);
            color: #065f46;
            padding: 0.15rem 0.4rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .scrollable-x-auto {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            z-index: auto;
        }

        @media (max-width: 480px) {
            .perm-tooltip {
                left: 8px;
                right: 8px;
                width: calc(100% - 16px);
                max-width: calc(100% - 16px);
            }
        }

        .perm-trigger:focus {
            outline: 2px solid rgba(99, 102, 241, 0.25);
            outline-offset: 2px;
        }
    </style>
@endpush

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Employees') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('employees.create') }}">
                        {{ translate('Create New Employee') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ translate('Employees') }}</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#role_permission_table"
                                        placeholder="{{ translate('Search employees') }}" type="text" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="role_permission_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('Name') }}</th>
                                            <th>{{ translate('Is Manager') }}</th>
                                            <th>{{ translate('Email') }}</th>
                                            <th>{{ translate('Department') }}</th>
                                            <th>{{ translate('Role') }}</th>
                                            <th>{{ translate('Permissions') }}</th>
                                            <th>{{ translate('Sensitive Permissions') }}</th>
                                            <th class="text-center">{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employees as $index => $employee)
                                            @php
                                                // Permissions (human readable)
                                                $permNames = $employee->permissions
                                                    ->pluck('name')
                                                    ->map(function ($n) {
                                                        return \Illuminate\Support\Str::headline(
                                                            str_replace('.', ' ', $n),
                                                        );
                                                    })
                                                    ->toArray();

                                                // Sensitive permissions may be stored as JSON or array
                                                $sensitiveRaw = $employee->sensitive_permissions ?? [];
                                                if (is_string($sensitiveRaw)) {
                                                    $sensitiveList = json_decode($sensitiveRaw, true) ?: [];
                                                } elseif (is_array($sensitiveRaw)) {
                                                    $sensitiveList = $sensitiveRaw;
                                                } elseif ($sensitiveRaw instanceof \Illuminate\Support\Collection) {
                                                    $sensitiveList = $sensitiveRaw->toArray();
                                                } else {
                                                    $sensitiveList = [];
                                                }

                                                // Make labels nicer for sensitive (if value is slug)
                                                $sensitiveLabels = collect($sensitiveList)
                                                    ->map(function ($s) {
                                                        $map = [
                                                            'business_identity' =>
                                                                'Business Identity (CR/VAT/Activity)',
                                                            'authorized_person_name' => 'Authorized Person Name',
                                                            'national_id_iqama' => 'National ID / Iqama',
                                                            'phone_number' => 'Phone Number',
                                                            'email_address' => 'Email Address',
                                                            'full_address' => 'Address (Full)',
                                                            'iban_bank_account' => 'IBAN / Bank Account',
                                                            'documents_id_cr_contracts' =>
                                                                'Documents (ID/CR/Contracts)',
                                                            'credit_data_simah_bureau' =>
                                                                'Credit Data (SIMAH / Bureau)',
                                                            'credit_decision_output' =>
                                                                'Credit Decision Output (Limit/Grade)',
                                                            'risk_drivers_aggregated' => 'Risk Drivers (Aggregated)',
                                                            'transaction_references' =>
                                                                'Transaction References (Gateway/Bank)',
                                                            'risk_compliance_notes' =>
                                                                'Notes (Risk/Compliance/Collections)',
                                                            'export_dataset_row_level' => 'Export Dataset (Row-level)',
                                                            'api_keys_secrets' => 'API Keys / Secrets',
                                                        ];
                                                        if (is_string($s)) {
                                                            return $map[$s] ??
                                                                \Illuminate\Support\Str::headline(
                                                                    str_replace(['_', '-'], ' ', $s),
                                                                );
                                                        }
                                                        return (string) $s;
                                                    })
                                                    ->toArray();
                                            @endphp

                                            <tr>
                                                <td class="text-center">{{ $employee->id }}</td>
                                                <td>
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-gray-800">{{ $employee->first_name }}
                                                            {{ $employee->last_name }}</span>
                                                        <span
                                                            class="text-xs text-gray-500">{{ $employee->phone_number ?? '-' }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if ($employee->is_manager)
                                                        <span
                                                            class="badge badge-sm badge-outline badge-success">{{ translate('Yes') }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-outline badge-secondary">{{ translate('No') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $employee->email }}</td>
                                                <td>{{ $employee->department->name ?? '-' }}</td>
                                                <td>
                                                    @if ($employee && $employee->roles->count())
                                                        {{ $employee->roles->pluck('name')->join(', ') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>

                                                {{-- Permissions column with hover tooltip --}}
                                                <td>
                                                    @if (count($permNames) === 0)
                                                        <span class="text-sm text-gray-600">-</span>
                                                    @else
                                                        <div class="perm-tooltip-wrapper" aria-label="Permissions"
                                                            tabindex="0">
                                                            <div class="perm-trigger" role="button" aria-haspopup="true"
                                                                aria-expanded="false">
                                                                <span class="count">{{ count($permNames) }}</span>
                                                                <span class="text-xs">
                                                                    {{ translate('Permissions') }}</span>
                                                            </div>

                                                            {{-- Keep tooltip markup as fallback; JS moves to body --}}
                                                            <div class="perm-tooltip" role="dialog" aria-hidden="true">
                                                                <div class="title">{{ translate('Permissions') }}</div>

                                                                <div class="perm-list">
                                                                    @foreach ($permNames as $p)
                                                                        <span class="perm-chip">{{ $p }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>

                                                {{-- Sensitive Permissions column with hover tooltip --}}
                                                <td>
                                                    @if (count($sensitiveLabels) === 0)
                                                        <span class="text-sm text-gray-600">-</span>
                                                    @else
                                                        <div class="perm-tooltip-wrapper" aria-label="Sensitive Permissions"
                                                            tabindex="0">
                                                            <div class="perm-trigger" role="button" aria-haspopup="true"
                                                                aria-expanded="false">
                                                                <span class="count">{{ count($sensitiveLabels) }}</span>
                                                                <span class="text-xs"> {{ translate('Sensitive') }}</span>
                                                            </div>

                                                            {{-- Keep tooltip markup as fallback; JS moves to body --}}
                                                            <div class="perm-tooltip" role="dialog" aria-hidden="true">
                                                                <div class="title">
                                                                    {{ translate('Sensitive Permissions') }}</div>

                                                                <div class="perm-list">
                                                                    @foreach ($sensitiveLabels as $s)
                                                                        <span
                                                                            class="perm-chip sensitive">{{ $s }}</span>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    <div class="flex gap-1 justify-center">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('employees.edit', $employee->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('employees.destroy', $employee->id) }}">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @include('layouts.includes.table-pagination', ['paginator' => $employees])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        (function() {
            if (typeof document === 'undefined') return;

            function initTooltips() {
                var wrappers = Array.from(document.querySelectorAll('.perm-tooltip-wrapper'));
                wrappers.forEach(function(wrapper, idx) {
                    var tooltip = wrapper.querySelector('.perm-tooltip');
                    if (!tooltip) return;

                    if (!tooltip.id) tooltip.id = 'perm-tooltip-' + idx;

                    // Move tooltip to body (if not already)
                    if (tooltip.parentElement !== document.body) {
                        document.body.appendChild(tooltip);
                    }

                    tooltip.classList.remove('visible');
                    tooltip.setAttribute('aria-hidden', 'true');

                    // state tracking
                    var hideTimeout = null;
                    var isOverWrapper = false;
                    var isOverTooltip = false;

                    function clearHideTimeout() {
                        if (hideTimeout) {
                            clearTimeout(hideTimeout);
                            hideTimeout = null;
                        }
                    }

                    function scheduleHide() {
                        // small delay to allow moving between trigger and tooltip without flicker
                        clearHideTimeout();
                        hideTimeout = setTimeout(function() {
                            // only hide if neither over wrapper nor tooltip and not focused
                            var stillFocused = wrapper.matches(':focus-within') || document
                                .activeElement === tooltip || tooltip.contains(document.activeElement);
                            if (!isOverWrapper && !isOverTooltip && !stillFocused) {
                                hide();
                            }
                        }, 120); // 120ms delay (tweak if needed)
                    }

                    function show() {
                        clearHideTimeout();

                        // compute anchor rect
                        var rect = wrapper.getBoundingClientRect();

                        // temporarily make visible for measurement if needed
                        tooltip.style.visibility = 'hidden';
                        tooltip.style.display = 'block';
                        tooltip.style.left = '0px';
                        tooltip.style.top = '0px';
                        tooltip.style.maxWidth = Math.min(420, Math.max(220, rect.width * 2)) + 'px';

                        var tooltipRect = tooltip.getBoundingClientRect();
                        var ttW = tooltipRect.width;
                        var ttH = tooltipRect.height;

                        // available space
                        var spaceBelow = window.innerHeight - rect.bottom;
                        var spaceAbove = rect.top;

                        var left = rect.left + window.scrollX;
                        if (left + ttW > window.innerWidth - 8 + window.scrollX) {
                            left = Math.max(8 + window.scrollX, window.innerWidth - ttW - 8 + window.scrollX);
                        }
                        if (left < 8 + window.scrollX) left = 8 + window.scrollX;

                        var top;
                        var margin = 6;
                        if (spaceBelow >= ttH + margin || spaceBelow >= spaceAbove) {
                            top = rect.bottom + margin + window.scrollY;
                        } else {
                            top = rect.top - ttH - margin + window.scrollY;
                        }

                        tooltip.style.position = 'absolute';
                        tooltip.style.left = Math.round(left) + 'px';
                        tooltip.style.top = Math.round(top) + 'px';
                        tooltip.style.visibility = '';
                        tooltip.style.display = '';
                        requestAnimationFrame(function() {
                            tooltip.classList.add('visible');
                            tooltip.setAttribute('aria-hidden', 'false');
                            // update aria-expanded on trigger if exists
                            var trigger = wrapper.querySelector('.perm-trigger');
                            if (trigger) trigger.setAttribute('aria-expanded', 'true');
                        });
                    }

                    function hide() {
                        clearHideTimeout();
                        tooltip.classList.remove('visible');
                        tooltip.setAttribute('aria-hidden', 'true');
                        var trigger = wrapper.querySelector('.perm-trigger');
                        if (trigger) trigger.setAttribute('aria-expanded', 'false');
                    }

                    // wrapper events
                    wrapper.addEventListener('mouseenter', function() {
                        isOverWrapper = true;
                        show();
                    }, {
                        passive: true
                    });

                    wrapper.addEventListener('mouseleave', function() {
                        isOverWrapper = false;
                        scheduleHide();
                    }, {
                        passive: true
                    });

                    // keyboard accessibility
                    wrapper.addEventListener('focusin', function() {
                        isOverWrapper = true;
                        show();
                    });

                    wrapper.addEventListener('focusout', function(ev) {
                        // If focus moved into the tooltip, keep open
                        var toEl = ev.relatedTarget;
                        if (toEl && tooltip.contains(toEl)) {
                            // focus moved to tooltip, keep it open
                            return;
                        }
                        isOverWrapper = false;
                        scheduleHide();
                    });

                    // tooltip events so moving the mouse into tooltip keeps it open
                    tooltip.addEventListener('mouseenter', function() {
                        isOverTooltip = true;
                        clearHideTimeout();
                        show();
                    }, {
                        passive: true
                    });

                    tooltip.addEventListener('mouseleave', function() {
                        isOverTooltip = false;
                        scheduleHide();
                    }, {
                        passive: true
                    });

                    // also handle focus inside tooltip (for keyboard users)
                    tooltip.addEventListener('focusin', function() {
                        isOverTooltip = true;
                        clearHideTimeout();
                        show();
                    });

                    tooltip.addEventListener('focusout', function(ev) {
                        var toEl = ev.relatedTarget;
                        if (toEl && wrapper.contains(toEl)) {
                            return;
                        }
                        isOverTooltip = false;
                        scheduleHide();
                    });

                    // hide on Escape
                    wrapper.addEventListener('keydown', function(ev) {
                        if (ev.key === 'Escape' || ev.key === 'Esc') hide();
                    });

                    tooltip.addEventListener('keydown', function(ev) {
                        if (ev.key === 'Escape' || ev.key === 'Esc') hide();
                    });

                    // reposition if visible when scrolling/resizing
                    var reposition = function() {
                        if (tooltip.classList.contains('visible')) {
                            show();
                        }
                    };
                    window.addEventListener('resize', reposition, {
                        passive: true
                    });
                    window.addEventListener('scroll', reposition, {
                        passive: true
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTooltips);
            } else {
                initTooltips();
            }
        })();
    </script>
@endpush
