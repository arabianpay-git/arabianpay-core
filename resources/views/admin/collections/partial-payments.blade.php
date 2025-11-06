@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Partial Payments') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Partial Payments') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex gap-2">
                                <select id="statusFilter" class="select select-sm select-bordered">
                                    <option value="">{{ translate('Filter by Status') }}</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                        {{ translate('Pending') }}</option>
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                        {{ translate('Approved') }}</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>
                                        {{ translate('Rejected') }}</option>
                                </select>

                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input id="paymentSearch" placeholder="{{ translate('Search by user') }}" type="text"
                                        value="{{ request('search') }}" />
                                </label>

                                <a href="{{ route('collections.partialPayments') }}" class="btn btn-light btn-sm">
                                    <i class="ki-filled ki-arrows-circle"> </i>{{ translate('Clear') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="partial_payments_table">
                            <div class="overflow-x-auto">
                                <table class="table table-auto table-border w-full">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('User') }}</th>
                                            <th>{{ translate('Employee') }}</th>
                                            <th>{{ translate('Partial Amount') }}</th>
                                            <th>{{ translate('Due Date') }}</th>
                                            <th>{{ translate('Approval Status') }}</th>
                                            <th>{{ translate('Payment Method') }}</th>
                                            <th>{{ translate('Paid At') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($partialPayments as $payment)
                                            @php
                                                $badgeClass = match ($payment->approval_status) {
                                                    'pending' => 'badge-warning',
                                                    'approved' => 'badge-success',
                                                    'rejected' => 'badge-danger',
                                                    default => 'badge-secondary',
                                                };
                                            @endphp
                                            <tr id="payment-row-{{ $payment->id }}">
                                                <td class="text-center">
                                                    {{ ($partialPayments->currentPage() - 1) * $partialPayments->perPage() + $loop->iteration }}
                                                </td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        <a href="{{ route('customerProfile', ['id' => $payment->user_id]) }}"
                                                            class="underline">{{ $payment->user?->business_name ?? $payment->user?->first_name }}</a>
                                                        <br>
                                                        <small class="text-gray-500">
                                                            {{ $payment->user?->email ?? '—' }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ $payment->employee?->first_name ?? '-' }}
                                                    {{ $payment->employee?->last_name ?? '-' }}
                                                </td>
                                                <td>
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format($payment->partial_amount, 2) }}
                                                </td>
                                                <td>{{ $payment->partial_due_date?->format('d M, Y') }}</td>
                                                <td>
                                                    <span class="badge badge-sm badge-outline {{ $badgeClass }}">
                                                        {{ ucfirst($payment->approval_status) }}
                                                    </span>
                                                </td>
                                                <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
                                                <td>{{ $payment->paid_at ? $payment->paid_at->format('d M, Y H:i') : '-' }}
                                                </td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary tooltip"
                                                            href="{{ route('collections.installmentDetails', ['id' => $payment->schedulePayment->order->id]) }}"
                                                            title="{{ translate('View Installment Details') }}">
                                                            <i class="ki-filled ki-eye"></i>
                                                        </a>

                                                        @if ($payment->approval_status === 'pending')
                                                            <button
                                                                class="btn btn-sm btn-icon btn-clear btn-success action-btn tooltip"
                                                                data-id="{{ $payment->id }}" data-action="approve"
                                                                title="{{ translate('Approve Partial Payment') }}">
                                                                <i class="ki-filled ki-check"></i>
                                                            </button>

                                                            <button
                                                                class="btn btn-sm btn-icon btn-clear btn-danger action-btn tooltip"
                                                                data-id="{{ $payment->id }}" data-action="reject"
                                                                title="{{ translate('Reject Partial Payment') }}">
                                                                <i class="ki-filled ki-cross-circle"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-gray-500">
                                                    {{ translate('No partial payments found') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination (links will have ?search=...&status=... because controller appended) --}}
                            <div class="mt-4">
                                {{ $partialPayments->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles')
    <style>
        .tooltip {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .tooltip::after {
            content: attr(title);
            position: absolute;
            bottom: 130%;
            transform: translateX(-50%);
            background: #111827;
            color: #fff;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.15s ease-in-out;
            z-index: 100;
        }

        .tooltip::before {
            content: "";
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: #111827 transparent transparent transparent;
            opacity: 0;
            transition: all 0.15s ease-in-out;
            z-index: 99;
        }

        .tooltip:hover::after,
        .tooltip:hover::before {
            opacity: 1;
            transform: translateX(-50%) translateY(-3px);
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            // Elements
            const tableContainer = document.getElementById('partial_payments_table');
            const searchInput = document.getElementById('paymentSearch');
            const statusFilter = document.getElementById('statusFilter');
            const updateStatusRoute = "{{ route('collections.partialPayments.updateStatus') }}";
            const csrfToken = '{{ csrf_token() }}';

            // Debounce helper
            function debounce(fn, wait = 400) {
                let t;
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            }

            /**
             * Builds URLSearchParams from current inputs.
             * @param {boolean} keepPage - Whether to preserve the 'page' parameter.
             * @returns {URLSearchParams}
             */
            function buildParams(keepPage = false) {
                const params = new URLSearchParams(window.location.search);
                const status = statusFilter.value;
                const search = searchInput.value.trim();

                if (status) params.set('status', status);
                else params.delete('status');

                if (search) params.set('search', search);
                else params.delete('search');

                if (!keepPage) params.delete('page');
                return params;
            }

            /**
             * Updates filter inputs to match the current URL parameters.
             */
            function syncFiltersFromUrl() {
                const params = new URLSearchParams(window.location.search);
                // Update search input only if the focused element is not the input itself 
                // to prevent cursor jumping, or if it's not focused at all.
                if (document.activeElement !== searchInput) {
                    searchInput.value = params.get('search') || '';
                }
                statusFilter.value = params.get('status') || '';
            }

            /**
             * Loads the table content via AJAX.
             * @param {string} url - The URL to fetch.
             * @param {boolean} pushHistory - Whether to update the browser history.
             */
            async function loadTable(url, pushHistory = true) {
                try {
                    // Show small loader
                    tableContainer.innerHTML =
                        `<div class="p-6 text-center text-gray-500">{{ translate('Loading...') }}</div>`;

                    const res = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

                    const text = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(text, 'text/html');
                    const newContainer = doc.querySelector('#partial_payments_table');

                    // Update DOM and history
                    tableContainer.innerHTML = (newContainer && newContainer.innerHTML) || text;
                    if (pushHistory) history.pushState({}, '', url);

                    // Re-attach handlers to newly loaded content
                    attachHandlers();
                } catch (err) {
                    console.error('Table load error:', err);
                    tableContainer.innerHTML =
                        `<div class="p-6 text-center text-red-600">{{ translate('Failed to load data') }}</div>`;
                    Swal.fire('{{ translate('Error') }}', err.message || '{{ translate('Failed to load data') }}',
                        'error');
                }
            }

            // Intercept pagination links inside the table container
            function interceptPaginationLinks() {
                // Use event delegation for potential future optimization/simplicity
                tableContainer.querySelectorAll('.pagination a').forEach(a => {
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        if (href) loadTable(href, true);
                    });
                });
            }

            // Handles the AJAX status update (Approve/Reject)
            async function handleStatusUpdate(id, action) {
                const confirmTitle = action === 'approve' ?
                    '{{ translate('Approve this partial payment?') }}' :
                    '{{ translate('Reject this partial payment?') }}';

                const confirmText = action === 'approve' ?
                    '{{ translate('This action will approve the customer’s partial payment request.') }}' :
                    '{{ translate('Rejecting will decline the customer’s partial payment request.') }}';

                const confirmResult = await Swal.fire({
                    title: confirmTitle,
                    text: confirmText,
                    icon: action === 'approve' ? 'question' : 'warning',
                    showCancelButton: true,
                    confirmButtonText: action === 'approve' ?
                        '{{ translate('Yes, Approve') }}' : '{{ translate('Yes, Reject') }}',
                    cancelButtonText: '{{ translate('Cancel') }}',
                    confirmButtonColor: action === 'approve' ? '#16a34a' : '#dc2626',
                });

                if (!confirmResult.isConfirmed) return;

                try {
                    const resp = await fetch(updateStatusRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            id,
                            action
                        })
                    });

                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Request failed');

                    if (data.status === 'success') {
                        await Swal.fire({
                            icon: 'success',
                            title: '{{ translate('Success!') }}',
                            text: data.message,
                            confirmButtonText: '{{ translate('OK') }}'
                        });
                        // Reload the table with current filters (resetting to page 1)
                        triggerFilterLoad(false);
                    } else {
                        Swal.fire('{{ translate('Error!') }}', data.message ||
                            '{{ translate('Unexpected error occurred.') }}', 'error');
                    }
                } catch (err) {
                    console.error('Status update error:', err);
                    Swal.fire('{{ translate('Error!') }}', err.message ||
                        '{{ translate('Something went wrong.') }}', 'error');
                }
            }

            /**
             * Attach approve/reject handlers and pagination interceptors.
             */
            function attachHandlers() {
                // IMPORTANT: Prevent double-binding by replacing buttons with fresh clones
                tableContainer.querySelectorAll('.action-btn').forEach(btn => {
                    const newBtn = btn.cloneNode(true);
                    btn.replaceWith(newBtn); // Optimized DOM update

                    newBtn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const action = this.dataset.action;
                        handleStatusUpdate(id, action);
                    });
                });

                // Pagination links
                interceptPaginationLinks();
            }

            /**
             * Builds the URL from current inputs and loads the table.
             * @param {boolean} keepPage - Whether to preserve the 'page' parameter.
             */
            function triggerFilterLoad(keepPage = false) {
                const params = buildParams(keepPage);
                const url = `${window.location.pathname}?${params.toString()}`;
                loadTable(url, true);
            }

            // --- Initialization and Event Listeners ---

            // Debounced function for search input
            const debouncedLoad = debounce(() => triggerFilterLoad(false), 450);

            searchInput.addEventListener('input', debouncedLoad);
            statusFilter.addEventListener('change', () => triggerFilterLoad(false));

            // Handle browser back/forward (popstate)
            window.addEventListener('popstate', function() {
                // 1. Sync filter inputs with URL parameters
                syncFiltersFromUrl();
                // 2. Load table for current URL without pushing a new history state
                loadTable(window.location.href, false);
            });

            // Attach initial handlers for the server-rendered content
            attachHandlers();
        })();
    </script>
@endpush
