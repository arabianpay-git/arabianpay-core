@extends('layouts.base')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        /* minimal helper preserved for fallback only */
        .json-viewer {
            max-height: 260px;
            overflow-y: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            background: #f8fafc;
            border-radius: 6px;
            padding: 12px;
        }

        .input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .modal {
            display: none;
            /* toggled by JS */
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(15, 23, 42, 0.5);
            align-items: flex-start;
            /* appear from top (adjustable) */
            justify-content: center;
            padding: 2.5rem 1rem;
            overflow: auto;
        }

        .modal.open {
            display: flex;
        }

        /* modal content limits height and allows body scroll */
        .modal-content {
            width: 100%;
            max-width: 900px;
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
        }

        .modal-body {
            flex: 1 1 auto;
            overflow: auto;
            padding: 1.25rem;
        }

        /* subtle left accent for card categories */
        .accent-high {
            border-left-width: 4px;
            border-left-color: #ef4444;
        }

        /* red */
        .accent-personal {
            border-left-width: 4px;
            border-left-color: #f59e0b;
        }

        /* yellow */
        .accent-default {
            border-left-width: 4px;
            border-left-color: #3b82f6;
        }

        /* blue */

        /* small utility for nested value lists */
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
    <main class="grow content pt-6 pb-12" id="content" role="content">
        <div class="container-fixed mx-auto px-4">

            <!-- Header -->
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6">
                <div class="flex-1">
                    <h1 class="text-2xl lg:text-3xl font-semibold text-gray-900">{{ translate('Audit Trail Monitor') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ translate('Complete audit history with PDPL & SAMA compliance tracking') }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('audit.logs') }}" class="btn btn-outline btn-secondary">
                        <i class="ki-filled ki-briefcase mr-2"></i>
                        {{ translate('Audit Logs') }}
                    </a>
                </div>
            </div>


            <!-- Stat cards -->
            <div class="flex flex-row gap-4 mb-6">
                <div class="bg-white border rounded-lg shadow-sm p-4 w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">{{ translate('Total Records') }}</p>
                            <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['total_records']) }}</h3>
                        </div>
                        <div class="p-3 bg-blue-50 rounded-md">
                            <i class="ki-filled ki-document text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">{{ translate('All time audit entries') }}</div>
                </div>

                <div class="bg-white border rounded-lg shadow-sm p-4 w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">{{ translate('Unique Users') }}</p>
                            <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['unique_users']) }}</h3>
                        </div>
                        <div class="p-3 bg-green-50 rounded-md">
                            <i class="ki-filled ki-user text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">{{ translate('Distinct actors in system') }}</div>
                </div>

                <div class="bg-white border rounded-lg shadow-sm p-4 w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">{{ translate("Today's Activity") }}</p>
                            <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['today_count']) }}</h3>
                        </div>
                        <div class="p-3 bg-purple-50 rounded-md">
                            <i class="ki-filled ki-watch text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">{{ translate('Audit events today') }}</div>
                </div>

                <div class="bg-white border rounded-lg shadow-sm p-4 w-full">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500">{{ translate('Highly Sensitive') }}</p>
                            <h3 class="text-xl font-bold text-gray-900">{{ number_format($stats['high_sensitive_count']) }}
                            </h3>
                        </div>
                        <div class="p-3 bg-red-50 rounded-md">
                            <i class="ki-filled ki-shield-cross text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">{{ translate('PDPL critical events') }}</div>
                </div>
            </div>

            <!-- Filters (flatpickr date inputs) -->
            <div class="bg-white border rounded-lg shadow-sm mb-6 overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h3 class="text-md font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ki-filled ki-filter text-gray-600"></i>
                        {{ translate('Filter Audit Trails') }}
                    </h3>
                </div>

                <form id="filterForm" method="GET" action="{{ route('audit.trails') }}" class="p-5">
                    <div class="flex flex-row gap-4">
                        <div class="w-full">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2">{{ translate('Event Category') }}</label>
                            <select name="event_category" class="w-full input px-3 py-2 border rounded-md bg-white">
                                <option value="">{{ translate('All Categories') }}</option>
                                @foreach ($filterOptions['event_categories'] as $category)
                                    <option value="{{ $category }}"
                                        {{ $filters['event_category'] == $category ? 'selected' : '' }}>
                                        {{ $category }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2">{{ translate('Entity Type') }}</label>
                            <select name="entity_type" class="w-full input px-3 py-2 border rounded-md bg-white">
                                <option value="">{{ translate('All Entities') }}</option>
                                @foreach ($filterOptions['entity_types'] as $entity)
                                    <option value="{{ $entity }}"
                                        {{ $filters['entity_type'] == $entity ? 'selected' : '' }}>{{ $entity }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2">{{ translate('PDPL Category') }}</label>
                            <select name="pdpl_category" class="w-full input px-3 py-2 border rounded-md bg-white">
                                <option value="">{{ translate('All Categories') }}</option>
                                @foreach ($filterOptions['pdpl_categories'] as $category)
                                    <option value="{{ $category }}"
                                        {{ $filters['pdpl_category'] == $category ? 'selected' : '' }}>{{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2">{{ translate('Date Range') }}</label>
                            <div class="flex gap-2">
                                <input type="text" id="date_from" name="date_from" value="{{ $filters['date_from'] }}"
                                    class="flex-1 input px-3 py-2 border rounded-md bg-white" placeholder="From"
                                    autocomplete="off" />
                                <input type="text" id="date_to" name="date_to" value="{{ $filters['date_to'] }}"
                                    class="flex-1 input px-3 py-2 border rounded-md bg-white" placeholder="To"
                                    autocomplete="off" />
                            </div>
                        </div>


                    </div>

                    <div class="w-full mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ translate('Search') }}</label>
                        <div class="flex gap-2">
                            <input type="text" name="search" value="{{ $filters['search'] }}"
                                class="flex-1 input px-3 py-2 border rounded-md"
                                placeholder="{{ translate('Search action summary, event type, or justification...') }}">
                            <button type="submit" class="btn  btn-outline btn-secondary">
                                <i class="ki-filled ki-magnifier mr-2"></i>
                                {{ translate('Search') }}
                            </button>
                            <a href="{{ route('audit.trails') }}" class="btn  btn-outline btn-primary">
                                <i class="ki-filled ki-arrows-circle mr-2"></i>
                                {{ translate('Clear') }}</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Audit Trail List -->
            <div class="space-y-4">
                @forelse($auditTrails as $trail)
                    @php
                        $accentClass =
                            $trail['pdpl_category'] == 'Highly Sensitive'
                                ? 'accent-high'
                                : ($trail['pdpl_category'] == 'Personal'
                                    ? 'accent-personal'
                                    : 'accent-default');
                    @endphp

                    <div class="bg-white border rounded-lg shadow-sm p-5 hover:shadow-md transition {{ $accentClass }}">
                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-start gap-3 mb-3">
                                    <div
                                        class="p-2 rounded-lg {{ $trail['pdpl_category'] == 'Highly Sensitive' ? 'bg-red-50' : 'bg-blue-50' }}">
                                        <i
                                            class="ki-filled {{ $trail['event_icon'] }} {{ $trail['pdpl_category'] == 'Highly Sensitive' ? 'text-red-600' : 'text-blue-600' }} text-lg"></i>
                                    </div>

                                    <div class="flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="font-semibold text-gray-900 mb-1">
                                                    {{ $trail['action_summary_short'] }}</h4>

                                                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trail['pdpl_badge_color'] }}">
                                                        <span
                                                            class="w-2 h-2 rounded-full mr-2 {{ $trail['pdpl_category'] == 'Highly Sensitive' ? 'bg-red-500' : ($trail['pdpl_category'] == 'Personal' ? 'bg-yellow-500' : 'bg-blue-500') }}"></span>
                                                        {{ $trail['pdpl_category'] }}
                                                    </span>

                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $trail['masking_badge_color'] }}">{{ $trail['masking_state'] }}</span>

                                                    <span class="text-gray-300">•</span>
                                                    <span>{{ $trail['entity_type'] }}</span>
                                                    @if ($trail['entity_id'])
                                                        <span class="text-gray-400">#{{ $trail['entity_id'] }}</span>
                                                    @endif

                                                    @if (!empty($trail['viewed_by']))
                                                        <span
                                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">{{ translate('Viewed') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <p class="text-sm text-gray-700 mt-2">
                                            <strong>{{ translate('Event') }}:</strong>
                                            <span class="font-medium">{{ $trail['event_type'] }}</span>
                                            • {{ $trail['event_category'] }}
                                        </p>

                                        <div class="flex items-center gap-3 mt-3">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-9 h-9 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <i class="ki-filled ki-user text-gray-500 text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">
                                                        {{ $trail['actor_name'] }}</p>
                                                    <p class="text-xs text-gray-500">{{ $trail['actor_email'] }} •
                                                        {{ $trail['actor_role'] }}</p>
                                                </div>
                                            </div>

                                            <div class="h-6 w-px bg-gray-200"></div>

                                            <div class="text-sm text-gray-600">
                                                <i class="ki-filled ki-wifi mr-1"></i>
                                                {{ $trail['ip_address'] }}
                                            </div>
                                        </div>

                                        @if ($trail['justification'])
                                            <div class="mt-3 rounded-md bg-yellow-50 border-l-4 border-yellow-400 p-3">
                                                <p class="text-sm font-medium text-yellow-800">
                                                    {{ translate('Justification') }}</p>
                                                <p class="text-sm text-yellow-700 mt-1">{{ $trail['justification'] }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="lg:w-72 flex-shrink-0">
                                <div class="flex flex-col gap-3">
                                    <div class="bg-gray-50 p-3 rounded-md text-xs text-gray-700">
                                        <div class="font-medium text-gray-900">{{ $trail['timestamp'] }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $trail['timestamp_diff'] }}</div>
                                    </div>

                                    @if (!empty($trail['pii_fields_array']))
                                        <div class="bg-red-50 p-3 rounded-md text-sm">
                                            <div class="text-xs text-red-600 mb-1">{{ translate('PII Fields') }}</div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach (array_slice($trail['pii_fields_array'], 0, 3) as $field)
                                                    <span
                                                        class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ $field }}</span>
                                                @endforeach
                                                @if (count($trail['pii_fields_array']) > 3)
                                                    <span
                                                        class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">+{{ count($trail['pii_fields_array']) - 3 }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex gap-2">
                                        <button data-modal-toggle="#audit_details_modal"
                                            onclick="loadAuditDetails({{ $trail['record_id'] }})"
                                            class="btn btn-sm btn-outline btn-secondary">
                                            <i class="ki-filled ki-eye mr-2"></i> {{ translate('Details') }}
                                        </button>

                                        @if ($trail['has_changes'])
                                            <button data-modal-toggle="#changes_modal"
                                                onclick="loadChanges({{ $trail['record_id'] }})"
                                                class="btn btn-sm btn-outline btn-info">
                                                <i class="ki-filled ki-notepad mr-2"></i> {{ translate('Changes') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white border rounded-lg shadow-sm p-8 text-center">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="ki-filled ki-magnifier text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ translate('No audit trails found') }}</h3>
                        <p class="text-gray-500 mb-4">
                            {{ translate('Try adjusting your filters or create some activity.') }}</p>
                        <a href="{{ route('audit.trails') }}"
                            class="btn btn-sm btn-outline btn-primary">{{ translate('Clear Filters') }}</a>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @include('layouts.includes.table-pagination', ['paginator' => $auditTrails])
        </div>

        <!-- Details Modal (uses your modal markup) -->
        <div class="modal" data-modal="true" id="audit_details_modal">
            <div class="modal-content">
                <div class="modal-header py-4 px-5 border-b flex items-center justify-between">
                    <h5 class="modal-title text-lg font-semibold">{{ translate('Audit Trail Details') }}</h5>
                    <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                        data-modal-dismiss="true" aria-label="Close">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>

                <div class="modal-body" id="audit_details_body">
                    <div class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Changes Modal -->
        <div class="modal" data-modal="true" id="changes_modal">
            <div class="modal-content">
                <div class="modal-header py-4 px-5 border-b flex items-center justify-between">
                    <h5 class="modal-title text-lg font-semibold">{{ translate('Data Changes') }}</h5>
                    <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                        data-modal-dismiss="true" aria-label="Close">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>

                <div class="modal-body" id="changes_body">
                    <div class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <!-- flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        // Basic modal helper that respects your data-modal-toggle & data-modal-dismiss pattern
        (function() {
            // Keep track of open modals via class 'open' (more reliable than inline style checks)
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
                modalEl.style.display = 'flex'; // ensure flex for alignment/padding
                // updateBodyScrollLock();
            }

            function closeModal(modalEl) {
                if (!modalEl) return;
                modalEl.classList.remove('open');
                // hide after removing the class
                modalEl.style.display = 'none';
                // update body lock only after closing
                // updateBodyScrollLock();
            }

            // wire toggle buttons
            document.querySelectorAll('[data-modal-toggle]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const selector = btn.getAttribute('data-modal-toggle');
                    const modal = document.querySelector(selector);
                    if (modal && !modal.classList.contains('open')) {
                        openModal(modal);
                    } else {
                        closeModal(modal);
                    }
                });
            });

            // wire dismiss buttons
            document.querySelectorAll('[data-modal-dismiss]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    // closest modal
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
                    // close all open modals
                    document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
                }
            });

            // expose helpers for direct calls in other functions
            window.__openModal = openModal;
            window.__closeModal = closeModal;
            window.__updateBodyScrollLock = updateBodyScrollLock;
        })();

        /* ---------------------------
           flatpickr init for date filters
        ----------------------------*/
        (function initFlatpickr() {
            // single-date pickers (format Y-m-d)
            try {
                flatpickr("#date_from", {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    altInput: true,
                    altFormat: "F j, Y",
                });
                flatpickr("#date_to", {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    altInput: true,
                    altFormat: "F j, Y",
                });
            } catch (e) {
                // flatpickr not loaded or error - ignore
                console.warn('flatpickr init failed', e);
            }
        })();

        /* ---------------------------
           Helpers to robustly parse strings that may contain JSON
           (handles single- or double-encoded JSON strings)
        ----------------------------*/
        function tryParsePossiblyEncodedJson(value) {
            // If not a string, return as-is
            if (typeof value !== 'string') return {
                parsed: value,
                isParsed: false
            };

            // quick heuristic: if it starts with { or [ or "{" then attempt to parse
            let s = value.trim();

            // attempt up to 3 parse passes (handles nested double-encoded strings)
            for (let i = 0; i < 3; i++) {
                // only try JSON.parse when value looks JSON-like
                if (!s.length) break;
                if (!s.startsWith('{') && !s.startsWith('[') && !s.startsWith('"')) break;
                try {
                    const p = JSON.parse(s);
                    // if parsing yields a string, continue to attempt parsing deeper
                    if (typeof p === 'string') {
                        s = p.trim();
                        continue;
                    }
                    // parsed into object/array/primitive
                    return {
                        parsed: p,
                        isParsed: true
                    };
                } catch (e) {
                    // attempt a small cleanup: replace escaped quotes common when doubly encoded
                    const cleaned = s.replace(/\\+"/g, '"').replace(/\\"/g, '"');
                    if (cleaned === s) break;
                    s = cleaned;
                }
            }

            // nothing parsed - return original string
            return {
                parsed: value,
                isParsed: false
            };
        }

        // Format nested objects/arrays to a compact human-friendly string
        function formatObjectSummary(obj) {
            if (obj === null || obj === undefined) return '-';
            if (typeof obj === 'string') {
                // try parse JSON string further
                const {
                    parsed,
                    isParsed
                } = tryParsePossiblyEncodedJson(obj);
                if (isParsed) return formatObjectSummary(parsed);
                return escapeHtml(obj);
            }
            if (Array.isArray(obj)) {
                if (obj.length === 0) return '[]';
                return obj.map(x => escapeHtml(String(x))).join(', ');
            }
            if (typeof obj === 'object') {
                // show up to 6 top-level entries, and indicate nested objects
                const entries = Object.entries(obj).slice(0, 6).map(([k, v]) => {
                    if (v === null || v === undefined) return `${escapeHtml(k)}: -`;
                    if (typeof v === 'object') {
                        if (Array.isArray(v))
                            return `${escapeHtml(k)}: [${v.map(x => escapeHtml(String(x))).join(', ')}]`;
                        return `${escapeHtml(k)}: {…}`;
                    }
                    // if v is a string which itself is JSON, try to parse and print small summary
                    if (typeof v === 'string') {
                        const {
                            parsed,
                            isParsed
                        } = tryParsePossiblyEncodedJson(v);
                        if (isParsed) {
                            if (typeof parsed === 'object')
                                return `${escapeHtml(k)}: ${formatObjectSummary(parsed)}`;
                            return `${escapeHtml(k)}: ${escapeHtml(String(parsed))}`;
                        }
                    }
                    return `${escapeHtml(k)}: ${escapeHtml(String(v))}`;
                });
                return entries.join(' • ') + (Object.keys(obj).length > 6 ? ' • …' : '');
            }
            return escapeHtml(String(obj));
        }

        /* ---------------------------
           Details modal rendering
        ----------------------------*/
        function loadAuditDetails(auditId) {
            const body = document.getElementById('audit_details_body');
            body.innerHTML = loadingSpinnerHtml();
            fetch(`/admin/audit/${auditId}/details`)
                .then(r => r.json())
                .then(resp => {
                    if (!resp.success) {
                        body.innerHTML =
                            `<div class="text-center py-8 text-red-600">{{ translate('Failed to load details') }}</div>`;
                        return;
                    }
                    renderAuditDetailsIntoModal(resp.data);
                    const modal = document.getElementById('audit_details_modal');
                    if (modal) window.__openModal(modal);
                })
                .catch(() => {
                    body.innerHTML =
                        `<div class="text-center py-8 text-red-600">{{ translate('Failed to load details') }}</div>`;
                });
        }

        function renderAuditDetailsIntoModal(data) {
            const body = document.getElementById('audit_details_body');
            const propertiesHtml = renderPropertiesSection(data.properties || {});
            body.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h4 class="font-medium text-gray-900 mb-2">Event Summary</h4>
                        <p class="text-gray-700">${escapeHtml(data.action.summary || '')}</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-user text-blue-600"></i>
                                Actor Information
                            </h4>
                            <dl class="kv-list">
                                <dt>Name</dt><dd>${escapeHtml(data.actor.name || '-')}</dd>
                                <dt>Email</dt><dd class="font-mono">${escapeHtml(data.actor.email || '-')}</dd>
                                <dt>Role & Type</dt><dd>${escapeHtml((data.actor.role || '-') + ' • ' + (data.actor.type || '-'))}</dd>
                            </dl>
                        </div>

                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-setting-2 text-purple-600"></i>
                                Technical Details
                            </h4>
                            <dl class="kv-list">
                                <dt>IP Address</dt><dd class="font-mono">${escapeHtml(data.technical.ip_address || '-')}</dd>
                                <dt>Device Fingerprint</dt><dd class="font-mono">${escapeHtml(data.technical.device_fingerprint || '-')}</dd>
                                <dt>Request ID</dt><dd class="font-mono break-all">${escapeHtml(data.request_id || '-')}</dd>
                            </dl>
                        </div>

                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ${escapeHtml(data.event.icon || 'ki-file')} text-green-600"></i>
                                Event Information
                            </h4>
                            <dl class="kv-list">
                                <dt>Category</dt><dd>${escapeHtml(data.event.category || '-')}</dd>
                                <dt>Type</dt><dd>${escapeHtml(data.event.type || '-')}</dd>
                                <dt>Entity</dt><dd>${escapeHtml(data.entity.type || '-')}${data.entity.id ? ' #' + escapeHtml(data.entity.id) : ''}</dd>
                            </dl>
                        </div>

                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-shield-tick text-red-600"></i>
                                Compliance Information
                            </h4>
                            <dl class="kv-list">
                                <dt>PDPL Category</dt><dd>${escapeHtml(data.compliance.pdpl_category || '-')}</dd>
                                <dt>Masking State</dt><dd>${escapeHtml(data.compliance.masking_state || '-')}</dd>
                                ${data.compliance.justification ? `<dt>Justification</dt><dd>${escapeHtml(data.compliance.justification)}</dd>` : ''}
                                ${data.compliance.pii_fields && data.compliance.pii_fields.length ? `<dt>PII Fields</dt><dd>${data.compliance.pii_fields.map(x => `<span class="px-2 py-0.5 rounded bg-red-100 text-xs mr-1">${escapeHtml(x)}</span>`).join('')}</dd>` : ''}
                            </dl>
                        </div>

                        <div class="border rounded-md p-4">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-watch text-gray-600"></i>
                                System Information
                            </h4>
                            <dl class="kv-list">
                                <dt>Environment</dt><dd>${escapeHtml(data.environment || '-')}</dd>
                                <dt>Created At</dt><dd>${escapeHtml(data.timestamps.created_at || '-')}</dd>
                                <dt>Updated At</dt><dd>${escapeHtml(data.timestamps.updated_at || '-')}</dd>
                            </dl>
                        </div>

                        <div class="border rounded-md p-4 lg:col-span-2">
                            <h4 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                                <i class="ki-filled ki-code text-gray-600"></i>
                                Additional Properties
                            </h4>
                            ${propertiesHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        // Friendly properties renderer (no raw JSON)
        function renderPropertiesSection(properties) {
            if (!properties || Object.keys(properties).length === 0) {
                return '<p class="text-gray-500 text-sm">No additional properties</p>';
            }

            // Clone to avoid mutating original
            const props = JSON.parse(JSON.stringify(properties));
            const parts = [];

            // Device
            if (props.device) {
                const d = props.device;
                // attempt to normalize nested JSON strings inside device fields
                const uaInfo = tryParsePossiblyEncodedJson(d.user_agent ?? '');
                parts.push(`
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div class="p-3 bg-white border rounded">
                            <div class="text-xs text-gray-500 mb-1">Device Type</div>
                            <div class="font-medium">${escapeHtml(d.mobile ?? '-')}</div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <div class="text-xs text-gray-500 mb-1">Browser</div>
                            <div class="font-medium">${escapeHtml(d.browser ?? '-')}</div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <div class="text-xs text-gray-500 mb-1">Platform</div>
                            <div class="font-medium">${escapeHtml(d.platform ?? '-')}</div>
                        </div>
                        <div class="p-3 bg-white border rounded">
                            <div class="text-xs text-gray-500 mb-1">User Agent</div>
                            <div class="font-mono text-xs break-words">${escapeHtml(d.user_agent ?? '-')}</div>
                            <div class="mt-2"><button onclick="copyToClipboard('${escapeJsString(d.user_agent ?? '')}')" class="px-2 py-1 text-xs border rounded bg-gray-50">Copy UA</button></div>
                        </div>
                    </div>
                `);
                delete props.device;
            }

            // Headers
            if (props.headers) {
                const h = props.headers;
                parts.push(`
                    <div class="mb-3">
                        <div class="text-xs text-gray-500 mb-2">Request Headers</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Accept</div><div class="font-mono text-xs break-words">${escapeHtml(h.accept ?? '-')}</div></div>
                            <div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Language</div><div class="font-mono text-xs">${escapeHtml(h.language ?? '-')}</div></div>
                            <div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Content-Type</div><div class="font-mono text-xs">${escapeHtml(h.content_type ?? '-')}</div></div>
                        </div>
                    </div>
                `);
                delete props.headers;
            }

            // Request
            if (props.request) {
                const r = props.request;
                const queryHtml = r.query ? Object.entries(r.query).map(([k, v]) =>
                    `<div class="text-xs text-gray-500">${escapeHtml(k)}</div><div class="font-mono text-xs mb-1">${escapeHtml(String(v))}</div>`
                ).join('') : '<div class="text-xs text-gray-500">-</div>';
                parts.push(`
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs text-gray-500">Request</div>
                            <div class="text-xs text-gray-400">Method: <span class="font-medium">${escapeHtml(r.method ?? '-')}</span></div>
                        </div>

                        <div class="p-3 bg-white border rounded">
                            <div class="text-xs text-gray-500 mb-1">URL</div>
                            <div class="font-mono text-xs break-words">${escapeHtml(r.url ?? '-')}</div>
                            <div class="mt-2 flex gap-2">
                                <button onclick="copyToClipboard('${escapeJsString(r.url ?? '')}')" class="px-2 py-1 text-xs border rounded bg-gray-50">Copy URL</button>
                                <a href="${escapeHtml(r.url ?? '#')}" target="_blank" class="px-2 py-1 text-xs border rounded bg-gray-50">Open</a>
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Path</div><div class="font-mono text-xs">${escapeHtml(r.path ?? '-')}</div></div>
                            <div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Query</div><div class="font-mono text-xs">${queryHtml}</div></div>
                        </div>
                    </div>
                `);
                delete props.request;
            }

            // Common meta block
            const metaKeys = ['order_by', 'per_page', 'viewed_by', 'current_page', 'search_query', 'skipped_count',
                'total_results', 'filtered_count', 'user_types_filtered'
            ];
            const meta = {};
            metaKeys.forEach(k => {
                if (props[k] !== undefined) {
                    meta[k] = props[k];
                    delete props[k];
                }
            });

            if (Object.keys(meta).length) {
                parts.push(`
                    <div class="mb-3">
                        <div class="text-xs text-gray-500 mb-2">Query & View</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            ${meta.order_by !== undefined ? `<div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Order</div><div class="font-medium text-sm">${escapeHtml(String(meta.order_by))}</div></div>` : ''}
                            ${meta.per_page !== undefined ? `<div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Per Page</div><div class="font-medium text-sm">${escapeHtml(String(meta.per_page))}</div></div>` : ''}
                            ${meta.current_page !== undefined ? `<div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Page</div><div class="font-medium text-sm">${escapeHtml(String(meta.current_page))}</div></div>` : ''}
                            ${meta.total_results !== undefined ? `<div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Total</div><div class="font-medium text-sm">${escapeHtml(String(meta.total_results))}</div></div>` : ''}
                            ${meta.filtered_count !== undefined ? `<div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Filtered</div><div class="font-medium text-sm">${escapeHtml(String(meta.filtered_count))}</div></div>` : ''}
                            ${meta.viewed_by !== undefined ? `<div class="p-3 bg-white border rounded"><div class="text-xs text-gray-500">Viewed By</div><div class="font-medium text-sm">${escapeHtml(String(meta.viewed_by))}</div></div>` : ''}
                        </div>
                        ${meta.search_query ? `<div class="mt-2 text-xs text-gray-500">Search Query</div><div class="p-2 bg-white border rounded font-mono text-xs">${escapeHtml(String(meta.search_query))}</div>` : ''}
                        ${meta.user_types_filtered ? `<div class="mt-2 text-xs text-gray-500">Filtered User Types</div><div class="flex gap-2 mt-1">${meta.user_types_filtered.map(x => `<span class="px-2 py-0.5 rounded bg-gray-100 text-xs">${escapeHtml(String(x))}</span>`).join('')}</div>` : ''}
                    </div>
                `);
            }

            // Leftover keys as simple key/value cards
            const leftoverKeys = Object.keys(props);
            if (leftoverKeys.length) {
                const rows = leftoverKeys.map(k => {
                    const v = props[k];
                    let displayed;
                    // attempt to parse possible JSON strings
                    const {
                        parsed,
                        isParsed
                    } = tryParsePossiblyEncodedJson((v === undefined) ? '' : v);
                    if (isParsed) {
                        displayed = formatObjectSummary(parsed);
                    } else {
                        displayed = (v === null || v === undefined) ? '-' : escapeHtml(String(v));
                    }
                    return `<div class="p-3 bg-white border rounded mb-2"><div class="text-xs text-gray-500">${escapeHtml(k)}</div><div class="font-mono text-xs break-words">${displayed}</div></div>`;
                }).join('');
                parts.push(`<div><div class="text-xs text-gray-500 mb-2">Other Properties</div><div>${rows}</div></div>`);
            }

            return parts.join('');
        }


        /* ---------------------------
           Changes modal (friendly view, no raw JSON)
           Robustly handles values that are JSON-encoded strings (e.g. "details": "{\"min\":...}")
        ----------------------------*/
        function loadChanges(auditId) {
            const body = document.getElementById('changes_body');
            body.innerHTML = loadingSpinnerHtml();
            fetch(`/admin/audit/${auditId}/details`)
                .then(r => r.json())
                .then(resp => {
                    if (!resp.success) {
                        body.innerHTML =
                            `<div class="text-center py-8 text-red-600">{{ translate('Failed to load changes') }}</div>`;
                        return;
                    }

                    const before = resp.data.data_changes.before_state || {};
                    const after = resp.data.data_changes.after_state || {};

                    // Render friendly before/after
                    const beforeHtml = renderKeyValueCards(before);
                    const afterHtml = renderKeyValueCards(after);
                    const analysisHtml = generateChangeAnalysisHtml(before, after);

                    body.innerHTML = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-3 text-center bg-gray-100 p-2 rounded">Before</h4>
                                    ${beforeHtml}
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-3 text-center bg-gray-100 p-2 rounded">After</h4>
                                    ${afterHtml}
                                </div>
                            </div>

                            ${analysisHtml ? `<div class="border-t pt-6"><h4 class="font-medium text-gray-900 mb-3">Change Analysis</h4><div class="space-y-2">${analysisHtml}</div></div>` : ''}
                        </div>
                    `;
                    // open modal
                    const modal = document.getElementById('changes_modal');
                    if (modal) window.__openModal(modal);
                })
                .catch(() => {
                    body.innerHTML =
                        `<div class="text-center py-8 text-red-600">{{ translate('Failed to load changes') }}</div>`;
                });
        }

        // Render object as stacked key/value cards (friendly)
        function renderKeyValueCards(obj) {
            if (!obj || Object.keys(obj).length === 0) {
                return `<div class="p-3 bg-white border rounded text-sm text-gray-500">No data</div>`;
            }
            // Create cards for top-level keys
            return Object.keys(obj).map(k => {
                const raw = obj[k];
                // try parse possible JSON string
                const {
                    parsed,
                    isParsed
                } = tryParsePossiblyEncodedJson(raw);
                if (isParsed) {
                    // parsed is object/array/primitive
                    if (typeof parsed === 'object') {
                        // arrays vs objects
                        if (Array.isArray(parsed)) {
                            const badges = parsed.map(x =>
                                `<span class="px-2 py-0.5 rounded bg-gray-100 text-xs mr-1">${escapeHtml(String(x))}</span>`
                            ).join('');
                            return `<div class="p-3 bg-white border rounded mb-2"><div class="text-xs text-gray-500 mb-1">${escapeHtml(k)}</div><div>${badges}</div></div>`;
                        } else {
                            // nested object -> show nested key/value lines (limit to 8 entries)
                            const nested = Object.entries(parsed).slice(0, 20).map(([nk, nv]) => {
                                // attempt to parse nested JSON strings too
                                const {
                                    parsed: nvParsed,
                                    isParsed: nvIsParsed
                                } = tryParsePossiblyEncodedJson(nv);
                                const nvDisplay = nvIsParsed ? formatObjectSummary(nvParsed) : escapeHtml(
                                    String(nv));
                                return `<div class="text-xs text-gray-500">${escapeHtml(nk)}</div><div class="font-mono text-xs mb-1">${nvDisplay}</div>`;
                            }).join('');
                            return `<div class="p-3 bg-white border rounded mb-2"><div class="text-xs text-gray-500 mb-1">${escapeHtml(k)}</div><div>${nested}</div></div>`;
                        }
                    } else {
                        // primitive parsed (number/boolean)
                        return `<div class="p-3 bg-white border rounded mb-2"><div class="text-xs text-gray-500">${escapeHtml(k)}</div><div class="font-mono text-xs">${escapeHtml(String(parsed))}</div></div>`;
                    }
                } else {
                    // not JSON - primitive
                    if (raw === null || raw === undefined || raw === '') {
                        return `<div class="p-3 bg-white border rounded mb-2"><div class="text-xs text-gray-500">${escapeHtml(k)}</div><div class="font-mono text-xs">-</div></div>`;
                    }
                    return `<div class="p-3 bg-white border rounded mb-2"><div class="text-xs text-gray-500">${escapeHtml(k)}</div><div class="font-mono text-xs">${escapeHtml(String(raw))}</div></div>`;
                }
            }).join('');
        }

        function generateChangeAnalysisHtml(before, after) {
            const keys = Array.from(new Set([...Object.keys(before || {}), ...Object.keys(after || {})]));
            if (keys.length === 0) return '';

            const rows = keys.map(key => {
                const rawB = before[key];
                const rawA = after[key];

                // normalize both sides (attempt parsing)
                const {
                    parsed: normB
                } = tryParsePossiblyEncodedJson(rawB);
                const {
                    parsed: normA
                } = tryParsePossiblyEncodedJson(rawA);

                // compare using JSON.stringify for object/array, or string compare for primitives
                const bStr = (typeof normB === 'object') ? JSON.stringify(normB) : String(normB ?? '');
                const aStr = (typeof normA === 'object') ? JSON.stringify(normA) : String(normA ?? '');

                if (bStr === aStr) return '';

                return `
                    <div class="border-l-4 border-blue-400 bg-blue-50 p-3 rounded-r">
                        <div class="font-medium text-sm text-gray-900 mb-1">${escapeHtml(key)}</div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Before</div>
                                <div class="font-mono bg-white p-2 rounded text-red-600">${escapeHtml(formatObjectSummary(normB))}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 mb-1">After</div>
                                <div class="font-mono bg-white p-2 rounded text-green-600">${escapeHtml(formatObjectSummary(normA))}</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            return rows || '';
        }

        /* ---------------------------
           Utilities
        ----------------------------*/
        function loadingSpinnerHtml() {
            return `<div class="flex justify-center items-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>`;
        }

        // Export and refresh
        function exportAuditLogs() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `/admin/audit/export?${params.toString()}`;
        }

        function refreshAuditLogs() {
            window.location.reload();
        }

        // small helper to prevent XSS when injecting JSON/strings
        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        // escape string for inclusion inside single-quoted JS literals (for copy buttons)
        function escapeJsString(str) {
            if (str === null || str === undefined) return '';
            return String(str).replaceAll("\\", "\\\\").replaceAll("'", "\\'").replaceAll("\n", "\\n").replaceAll("\r", "");
        }

        function copyToClipboard(text) {
            if (!text) return;
            navigator.clipboard?.writeText(text).then(() => {
                const el = document.createElement('div');
                el.innerText = '{{ translate('Copied') }}';
                el.className = 'fixed bottom-6 right-6 bg-gray-900 text-white px-3 py-2 rounded shadow';
                document.body.appendChild(el);
                setTimeout(() => el.remove(), 1500);
            }).catch(() => {
                alert('{{ translate('Copy failed') }}');
            });
        }
    </script>
@endpush
