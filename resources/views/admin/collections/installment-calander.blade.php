@extends('layouts.base')

@push('styles')
    {{-- FullCalendar + Flatpickr --}}
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        #calendar {
            height: 700px;
            min-height: 400px;
            background: white;
            border-radius: 8px;
        }

        .fc-toolbar-title {
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            color: #111827;
        }

        .fc-event {
            border-radius: 6px !important;
            padding: 4px 6px !important;
            font-size: 12px !important;
            border: none !important;
            cursor: pointer;
            color: #ffffff !important;
        }

        .fc-daygrid-event-dot {
            display: none !important;
        }

        .fc-event-title {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- FullCalendar Event Classes (kept for compatibility) --- */
        .event-upcoming {
            background-color: #3B82F6 !important;
        }

        /* Blue */
        .event-paid {
            background-color: #10B981 !important;
        }

        /* Green */
        .event-overdue {
            background-color: #EF4444 !important;
        }

        /* Red */
        .event-promise {
            background-color: #F59E0B !important;
        }

        /* Amber/Orange */
        .event-default {
            background-color: #6b7280 !important;
        }

        /* Gray/Default */

        /* New explicit classes for late/failed (helps fallback) */
        .event-late {
            background-color: #ef4444 !important;
        }

        /* late -> red (same as overdue) */
        .event-failed {
            background-color: #ef4444 !important;
        }

        /* failed -> red */

        /* --- Modal Status Classes (Used in Swal.fire) --- */
        .swal2-status-upcoming {
            background-color: #3B82F6;
        }

        .swal2-status-paid {
            background-color: #10B981;
        }

        .swal2-status-overdue {
            background-color: #EF4444;
        }

        .swal2-status-promise {
            background-color: #F59E0B;
        }

        .swal2-status-late {
            background-color: #EF4444;
        }

        .swal2-status-failed {
            background-color: #EF4444;
        }

        /* Legend styles */
        .event-upcoming {
            box-shadow: 0 0 6px rgba(59, 130, 246, 0.5);
            padding: 2px 0.725rem;
            border-radius: 5px;
            color: #ffffff;
        }

        .event-paid {
            box-shadow: 0 0 6px rgba(34, 197, 94, 0.5);
            padding: 2px 0.725rem;
            border-radius: 5px;
            color: #ffffff;
        }

        .event-overdue {
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.5);
            padding: 2px 0.725rem;
            border-radius: 5px;
            color: #ffffff;
        }

        .event-promise {
            box-shadow: 0 0 6px rgba(234, 179, 8, 0.5);
            padding: 2px 0.725rem;
            border-radius: 5px;
            color: #111827;
        }

        .event-late {
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.5);
            padding: 2px 0.725rem;
            border-radius: 5px;
            color: #ffffff;
        }

        .event-failed {
            box-shadow: 0 0 6px rgba(239, 68, 68, 0.5);
            padding: 2px 0.725rem;
            border-radius: 5px;
            color: #ffffff;
        }

        .flex.items-center.gap-2 span {
            font-weight: 500;
            color: #111827;
        }
    </style>
@endpush

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">{{ translate('Installments Calendar View') }}</h1>
                    <p class="text-sm text-gray-500">{{ translate('Track payment due dates and status') }}</p>
                </div>

                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('collections.installments') ?? '#' }}">
                        <i class="ki-filled ki-tablet text-sm mr-2"></i>
                        {{ translate('View List') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="container-fixed mb-7.5">
            <div class="card mb-5">
                <div class="card-body py-4">
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full event-upcoming"></div><span
                                class="ml-2 text-sm text-gray-700">{{ translate('Upcoming') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full event-paid"></div><span
                                class="ml-2 text-sm text-gray-700">{{ translate('Paid') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full event-overdue"></div><span
                                class="ml-2 text-sm text-gray-700">{{ translate('Overdue') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full event-promise"></div><span
                                class="ml-2 text-sm text-gray-700">{{ translate('Promise') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full event-late"></div><span
                                class="ml-2 text-sm text-gray-700">{{ translate('Late') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full event-failed"></div><span
                                class="ml-2 text-sm text-gray-700">{{ translate('Failed') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="flex gap-2">
                        <select class="select select-sm select-bordered" id="filter-status">
                            <option value="">{{ translate('Filter by Status') }}</option>
                            <option value="pending">{{ translate('Pending') }}</option>
                            <option value="due">{{ translate('Due') }}</option>
                            <option value="late">{{ translate('Late') }}</option>
                            <option value="paid">{{ translate('Paid') }}</option>
                            <option value="failed">{{ translate('Failed') }}</option>
                        </select>

                        <input id="filter-from" placeholder="{{ translate('From Date') }}" type="text"
                            class="input input-sm" />
                        <input id="filter-to" placeholder="{{ translate('To Date') }}" type="text"
                            class="input input-sm" />

                        <input id="filter-search" placeholder="{{ translate('Search installments') }}" type="text"
                            class="input input-sm" />

                        <button id="filter-reset" class="btn btn-sm btn-light ml-2">{{ translate('Reset') }}</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fromInput = document.getElementById('filter-from');
            const toInput = document.getElementById('filter-to');
            flatpickr(fromInput, {
                dateFormat: 'Y-m-d'
            });
            flatpickr(toInput, {
                dateFormat: 'Y-m-d'
            });

            const statusSelect = document.getElementById('filter-status');
            const searchInput = document.getElementById('filter-search');
            const resetBtn = document.getElementById('filter-reset');

            let calendarEl = document.getElementById('calendar');
            let calendar = null;

            // Read initial filters from URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('status')) statusSelect.value = urlParams.get('status') || 'all';
            if (urlParams.has('search')) searchInput.value = urlParams.get('search') || '';
            if (urlParams.has('from')) fromInput.value = urlParams.get('from') || '';
            if (urlParams.has('to')) toInput.value = urlParams.get('to') || '';

            const statusColorMap = {
                pending: '#3B82F6',
                due: '#3B82F6',
                paid: '#10B981',
                late: '#EF4444',
                overdue: '#EF4444',
                failed: '#EF4444',
                promise: '#F59E0B'
            };

            function getStatusColorClass(status) {
                const map = {
                    pending: 'swal2-status-upcoming',
                    due: 'swal2-status-upcoming',
                    paid: 'swal2-status-paid',
                    late: 'swal2-status-overdue',
                    overdue: 'swal2-status-overdue',
                    failed: 'swal2-status-failed',
                    promise: 'swal2-status-promise'
                };
                return map[(status || '').toLowerCase()] || '';
            }

            function buildFetchUrl(customUrl = null) {
                if (customUrl) return customUrl;
                const base = `{{ route('collections.installmentsCalander') }}`;
                const status = encodeURIComponent(statusSelect.value || '');
                const search = encodeURIComponent(searchInput.value || '');
                const from = encodeURIComponent(fromInput.value || '');
                const to = encodeURIComponent(toInput.value || '');
                return `${base}?status=${status}&search=${search}&from=${from}&to=${to}`;
            }

            async function fetchEvents(url = null) {
                const fetchUrl = buildFetchUrl(url);
                try {
                    history.pushState({}, '', new URL(fetchUrl, window.location.origin));
                } catch (e) {}
                const res = await fetch(fetchUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return [];
                const events = await res.json();
                return Array.isArray(events) ? events : [];
            }

            function initCalendar(events) {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 700,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: events,
                    eventDisplay: 'block',
                    eventContent: function(arg) {
                        const title = arg.event.title || '';
                        const dpd = arg.event.extendedProps?.dpd || '';
                        const dpdText = ['late', 'overdue'].includes((arg.event.extendedProps?.status ||
                            '').toLowerCase()) && dpd ? ` (${dpd} DPD)` : '';
                        return {
                            html: `<div class="fc-event-title">${title}${dpdText}</div>`
                        };
                    },
                    eventDidMount: function(info) {
                        const color = statusColorMap[(info.event.extendedProps?.status || '')
                            .toLowerCase()] || '#6b7280';
                        info.el.style.backgroundColor = color;
                        info.el.style.borderColor = color;
                        info.el.style.color = '#ffffff';
                    },
                    eventClick: function(info) {
                        const props = info.event.extendedProps || {};
                        const statusClass = getStatusColorClass(props.status || '');
                        Swal.fire({
                            title: `{{ translate('Installment Status') }}: <span class="${statusClass}" style="padding:4px 8px;border-radius:4px;color:#fff">${props.status || ''}</span>`,
                            html: `
                        <div style="text-align:left;margin-top:10px">
                            <table style="width:100%;border-collapse:collapse">
                                <tbody>
                                    <tr><td style="padding:4px 0;color:#6b7280">{{ translate('Merchant') }}:</td><td style="padding:4px 0;font-weight:600">${props.merchant || '-'}</td></tr>
                                    <tr><td style="padding:4px 0;color:#6b7280">{{ translate('Order ID') }}:</td><td style="padding:4px 0;font-weight:600">${props.orderId || '-'}</td></tr>
                                    <tr><td style="padding:4px 0;color:#6b7280">{{ translate('Amount') }}:</td><td style="padding:4px 0;font-weight:600">SAR${parseFloat(props.amount || 0).toFixed(2)}</td></tr>
                                    <tr><td style="padding:4px 0;color:#6b7280">{{ translate('Due Date') }}:</td><td style="padding:4px 0;font-weight:600">${info.event.start?.toLocaleDateString() || '-'}</td></tr>
                                    <tr><td style="padding:4px 0;color:#6b7280">{{ translate('Channel') }}:</td><td style="padding:4px 0;font-weight:600">${props.channel || '-'}</td></tr>
                                    ${(props.status === 'Late' || props.status === 'Overdue') ? `<tr><td style="padding:4px 0;color:#6b7280">{{ translate('Days Past Due') }}:</td><td style="padding:4px 0;font-weight:700;color:#EF4444">${props.dpd || 0} DPD</td></tr>` : ''}
                                </tbody>
                            </table>
                        </div>
                    `,
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: "{{ translate('View Full Details') }}",
                            cancelButtonText: "{{ translate('Close') }}",
                            customClass: {
                                confirmButton: 'btn btn-primary',
                                cancelButton: 'btn btn-light'
                            },
                            buttonsStyling: false
                        });
                        info.jsEvent.preventDefault();
                    }
                });
                calendar.render();
            }

            async function reloadCalendar(url = null) {
                const events = await fetchEvents(url);
                if (!calendar) initCalendar(events);
                else {
                    calendar.removeAllEvents();
                    if (events.length) calendar.addEventSource(events);
                }
            }

            function debounce(fn, delay = 500) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            statusSelect.addEventListener('change', () => reloadCalendar());
            fromInput.addEventListener('change', () => reloadCalendar());
            toInput.addEventListener('change', () => reloadCalendar());
            searchInput.addEventListener('input', debounce(() => reloadCalendar(), 500));

            resetBtn.addEventListener('click', function() {
                statusSelect.value = 'all';
                searchInput.value = '';
                fromInput._flatpickr?.clear();
                toInput._flatpickr?.clear();
                history.pushState({}, '', `{{ route('collections.installmentsCalander') }}`);
                reloadCalendar();
            });

            window.addEventListener('popstate', function() {
                reloadCalendar();
            });

            reloadCalendar();
        });
    </script>
@endpush
