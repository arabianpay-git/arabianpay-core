
<div data-datatable="true" data-datatable-state-save="false" id="schedule_payments_table">
    <div class="scrollable-x-auto">
        <table class="table table-auto table-border" data-datatable-table="true">
            <thead>
                <tr>
                    <th class="w-[60px] text-center">
                        <input class="checkbox" type="checkbox" data-datatable-check="true">
                    </th>
                    <th class="min-w-[100px]">Payment #</th>
                    <th class="min-w-[120px]">Due Date</th>
                    <th class="min-w-[120px]">Amount</th>
                    <th class="min-w-[100px]">Status</th>
                    <th class="min-w-[120px]">Days Status</th>
                    <th class="min-w-[120px]">Claims</th>
                    <th class="min-w-[120px]">Payment Date</th>
                    <th class="text-center min-w-[120px]">Actions</th>
                </tr>
            </thead>
            <tbody id="schedulePaymentsBody">
                @forelse($checkout->schedulePayments ?? [] as $index => $payment)
                    @php
                        $daysFromDue = now()->diffInDays($payment->due_date, false);
                        $isOverdue = $payment->due_date < now() && $payment->payment_status !== 'paid';
                        $isDueToday = $payment->due_date->isToday() && $payment->payment_status !== 'paid';
                        $isDueThisWeek = $payment->due_date->between(now()->startOfWeek(), now()->endOfWeek()) && $payment->payment_status !== 'paid';
                    @endphp
                    <tr class="schedule-payment-row" 
                        data-status="{{ $payment->payment_status }}"
                        data-due-date="{{ $payment->due_date->format('Y-m-d') }}"
                        data-amount="{{ $payment->instalment_amount }}"
                        data-days="{{ $daysFromDue }}">
                        
                        <td class="text-center">
                            <input class="checkbox" type="checkbox" value="{{ $payment->id }}">
                        </td>
                        
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 {{ $payment->payment_status === 'paid' ? 'bg-success-light' : ($isOverdue ? 'bg-danger-light' : 'bg-warning-light') }} rounded-full">
                                    <i class="ki-filled {{ $payment->payment_status === 'paid' ? 'ki-check text-success' : ($isOverdue ? 'ki-cross-circle text-danger' : 'ki-time text-warning') }} text-base"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900">
                                        #{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="text-xs text-gray-600">Installment {{ $index + 1 }}</span>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium {{ $isOverdue ? 'text-danger' : ($isDueToday ? 'text-warning' : 'text-gray-900') }}">
                                    {{ $payment->due_date->format('M d, Y') }}
                                </span>
                                <span class="text-xs text-gray-600">{{ $payment->due_date->format('l') }}</span>
                            </div>
                        </td>
                        
                        <td>
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ number_format($payment->instalment_amount, 2) }} SAR
                                </span>
                                @if($payment->late_fee ?? 0 > 0)
                                <span class="text-xs text-danger">
                                    +{{ number_format($payment->late_fee, 2) }} late fee
                                </span>
                                @endif
                            </div>
                        </td>
                        
                        <td>
                            @php
                                $statusConfig = match($payment->payment_status) {
                                    'paid' => ['class' => 'badge-success', 'text' => 'Paid', 'icon' => 'ki-check'],
                                    'pending' => ['class' => 'badge-warning', 'text' => 'Pending', 'icon' => 'ki-time'],
                                    'overdue' => ['class' => 'badge-danger', 'text' => 'Overdue', 'icon' => 'ki-cross-circle'],
                                    'partial' => ['class' => 'badge-info', 'text' => 'Partial', 'icon' => 'ki-percentage'],
                                    default => ['class' => 'badge-light', 'text' => 'Unknown', 'icon' => 'ki-question']
                                };
                            @endphp
                            <span class="badge badge-sm badge-outline {{ $statusConfig['class'] }}">
                                <i class="ki-filled {{ $statusConfig['icon'] }} text-xs me-1"></i>
                                {{ $statusConfig['text'] }}
                            </span>
                        </td>
                        
                        <td>
                            <div class="flex flex-col">
                                @if($payment->payment_status === 'paid')
                                    <span class="text-sm text-success font-medium">Completed</span>
                                @elseif($isOverdue)
                                    <span class="text-sm text-danger font-medium">{{ abs(round($daysFromDue)) }} days overdue</span>
                                @elseif($isDueToday)
                                    <span class="text-sm text-warning font-medium">Due today</span>
                                @elseif($daysFromDue > 0)
                                    <span class="text-sm text-info font-medium">{{ round($daysFromDue) }} days left</span>
                                @else
                                    <span class="text-sm text-gray-600">Future payment</span>
                                @endif
                                
                                @if($payment->payment_status !== 'paid' && $isOverdue)
                                    <span class="text-xs text-danger">Action required</span>
                                @endif
                            </div>
                        </td>
                        
                        <td>
                            @php
                                $claimsCount = $payment->claims->count();
                                $lastClaim = $payment->claims->sortByDesc('created_at')->first();
                            @endphp
                            @if($claimsCount > 0)
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-sm badge-outline">{{ $claimsCount }}</span>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-600">{{ $claimsCount }} claim{{ $claimsCount > 1 ? 's' : '' }}</span>
                                        @if($lastClaim)
                                            <span class="text-xs text-gray-500">Last: {{ $lastClaim->created_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-500">No claims</span>
                            @endif
                        </td>
                        
                        <td>
                            @if($payment->payment && $payment->payment->created_at)
                                <div class="flex flex-col">
                                    <span class="text-sm text-success">{{ $payment->payment->created_at->format('M d, Y') }}</span>
                                    <span class="text-xs text-gray-600">{{ $payment->payment->created_at->format('h:i A') }}</span>
                                </div>
                            @else
                                <span class="text-sm text-gray-500">Not paid</span>
                            @endif
                        </td>
                        
                        <td class="text-center">
                            <div class="dropdown" data-dropdown="true">
                                <button class="dropdown-toggle btn btn-sm btn-icon btn-light btn-clear" data-dropdown-trigger="click">
                                    <i class="ki-filled ki-dots-vertical"></i>
                                </button>
                                <div class="dropdown-content menu-default w-full max-w-[200px]">
                                    <div class="menu-item">
                                        <a class="menu-link" href="#" onclick="showPaymentDetails({{ $payment->id }})">
                                            <span class="menu-icon"><i class="ki-filled ki-eye"></i></span>
                                            <span class="menu-title">View Details</span>
                                        </a>
                                    </div>
                                    
                                    @if($payment->payment_status !== 'paid')
                                        <div class="menu-item">
                                            <a class="menu-link" href="#" onclick="markAsPaid({{ $payment->id }})">
                                                <span class="menu-icon"><i class="ki-filled ki-check text-success"></i></span>
                                                <span class="menu-title">Mark as Paid</span>
                                            </a>
                                        </div>
                                        
                                        <div class="menu-separator"></div>
                                        
                                        <div class="menu-item">
                                            <a class="menu-link" href="#" onclick="createClaim({{ $payment->id }}, 'call')">
                                                <span class="menu-icon"><i class="ki-filled ki-phone text-primary"></i></span>
                                                <span class="menu-title">Create Call Claim</span>
                                            </a>
                                        </div>
                                        
                                        <div class="menu-item">
                                            <a class="menu-link" href="#" onclick="createClaim({{ $payment->id }}, 'sms')">
                                                <span class="menu-icon"><i class="ki-filled ki-sms text-info"></i></span>
                                                <span class="menu-title">Create SMS Claim</span>
                                            </a>
                                        </div>
                                        
                                        @if($isOverdue)
                                        <div class="menu-item">
                                            <a class="menu-link" href="#" onclick="sendReminder({{ $payment->id }})">
                                                <span class="menu-icon"><i class="ki-filled ki-notification text-warning"></i></span>
                                                <span class="menu-title">Send Reminder</span>
                                            </a>
                                        </div>
                                        @endif
                                    @endif
                                    
                                    @if($claimsCount > 0)
                                        <div class="menu-separator"></div>
                                        <div class="menu-item">
                                            <a class="menu-link" href="#" onclick="viewClaims({{ $payment->id }})">
                                                <span class="menu-icon"><i class="ki-filled ki-document text-secondary"></i></span>
                                                <span class="menu-title">View Claims ({{ $claimsCount }})</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                <tr id="no-payments-row">
                    <td colspan="9" class="text-center py-10">
                        <div class="flex flex-col items-center">
                            <i class="ki-filled ki-calendar text-4xl text-gray-400 mb-3"></i>
                            <div class="text-gray-600 text-base font-medium mb-1">No schedule payments found</div>
                            <div class="text-gray-500 text-sm">This checkout doesn't have any scheduled payments yet</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Summary Footer -->
<div class="flex flex-wrap items-center justify-between gap-5 py-5">
    <div class="flex items-center gap-6 text-sm">
        <div>
            <span class="text-gray-600">Total Payments:</span>
            <span class="font-semibold text-gray-900" id="totalCount">{{ $checkout->schedulePayments->count() }}</span>
        </div>
        <div>
            <span class="text-gray-600">Total Amount:</span>
            <span class="font-semibold text-gray-900" id="totalAmount">{{ number_format($checkout->schedulePayments->sum('instalment_amount'), 2) }} SAR</span>
        </div>
        <div>
            <span class="text-gray-600">Paid:</span>
            <span class="font-semibold text-success" id="collectedAmount">{{ number_format($checkout->schedulePayments->where('payment_status', 'paid')->sum('instalment_amount'), 2) }} SAR</span>
        </div>
        <div>
            <span class="text-gray-600">Outstanding:</span>
            <span class="font-semibold text-warning" id="outstandingAmount">{{ number_format($checkout->schedulePayments->where('payment_status', '!=', 'paid')->sum('instalment_amount'), 2) }} SAR</span>
        </div>
    </div>
    <div class="text-sm text-gray-500">
        Showing <span id="visibleCount">{{ $checkout->schedulePayments->count() }}</span> of {{ $checkout->schedulePayments->count() }} payments
    </div>
</div>
<!--end::Schedule Payments Section-->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Schedule Payments Table Functionality
    let currentFilter = 'all';
    let currentSort = 'due_date-asc';
    let searchTerm = '';

    // Quick Filter Buttons
    document.querySelectorAll('.schedule-filter').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.schedule-filter').forEach(btn => {
                btn.classList.remove('active', 'btn-primary');
                btn.classList.add('btn-light');
            });
            
            // Add active class to clicked button
            this.classList.remove('btn-light');
            this.classList.add('active', 'btn-primary');
            
            currentFilter = this.dataset.filter;
            filterAndSortScheduleTable();
        });
    });

    // Search Input
    const searchInput = document.getElementById('scheduleSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchTerm = this.value.toLowerCase();
            filterAndSortScheduleTable();
        });
    }

    // Sort Select
    const sortSelect = document.getElementById('scheduleSort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = this.value;
            filterAndSortScheduleTable();
        });
    }

    function filterAndSortScheduleTable() {
        const rows = Array.from(document.querySelectorAll('.schedule-payment-row'));
        const today = new Date().toISOString().split('T')[0];
        const weekStart = getWeekStart(new Date());
        const weekEnd = getWeekEnd(new Date());
        const monthStart = getMonthStart(new Date());
        const monthEnd = getMonthEnd(new Date());

        // Filter rows
        let visibleRows = rows.filter(row => {
            const status = row.dataset.status;
            const dueDate = row.dataset.dueDate;
            const amount = row.dataset.amount;
            
            // Search filter
            if (searchTerm && !amount.includes(searchTerm) && !row.textContent.toLowerCase().includes(searchTerm)) {
                return false;
            }

            // Status filter
            switch (currentFilter) {
                case 'all':
                    return true;
                case 'paid':
                    return status === 'paid';
                case 'due-today':
                    return dueDate === today && status !== 'paid';
                case 'due-week':
                    return dueDate >= weekStart && dueDate <= weekEnd && status !== 'paid';
                case 'due-month':
                    return dueDate >= monthStart && dueDate <= monthEnd && status !== 'paid';
                case 'late':
                    return dueDate < today && status !== 'paid';
                default:
                    return true;
            }
        });

        // Sort rows
        visibleRows.sort((a, b) => {
            const [field, direction] = currentSort.split('-');
            let aVal, bVal;

            switch (field) {
                case 'due_date':
                    aVal = new Date(a.dataset.dueDate);
                    bVal = new Date(b.dataset.dueDate);
                    break;
                case 'amount':
                    aVal = parseFloat(a.dataset.amount);
                    bVal = parseFloat(b.dataset.amount);
                    break;
                case 'status':
                    aVal = a.dataset.status;
                    bVal = b.dataset.status;
                    break;
                default:
                    return 0;
            }

            if (direction === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        // Hide all rows
        rows.forEach(row => row.style.display = 'none');

        // Show filtered and sorted rows
        const tbody = document.getElementById('schedulePaymentsBody');
        visibleRows.forEach(row => {
            row.style.display = '';
            tbody.appendChild(row); // Reorder in DOM
        });

        // Update counters
        updateScheduleCounters(visibleRows);

        // Show/hide no results message
        if (visibleRows.length === 0 && rows.length > 0) {
            let noResultsRow = document.getElementById('no-results-row');
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="9" class="text-center py-8">
                        <i class="ki-filled ki-magnifier text-4xl text-gray-300 mb-4"></i>
                        <h4 class="text-gray-600 mb-2">No Matching Payments</h4>
                        <p class="text-gray-500">Try adjusting your filters or search terms.</p>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
        } else {
            const noResultsRow = document.getElementById('no-results-row');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }

    function updateScheduleCounters(visibleRows) {
        const totalCount = visibleRows.length;
        let totalAmount = 0;
        let collectedAmount = 0;

        visibleRows.forEach(row => {
            const amount = parseFloat(row.dataset.amount);
            const status = row.dataset.status;
            
            totalAmount += amount;
            if (status === 'paid') {
                collectedAmount += amount;
            }
        });

        const outstandingAmount = totalAmount - collectedAmount;

        // Update counters
        const visibleCountEl = document.getElementById('visibleCount');
        const totalCountEl = document.getElementById('totalCount');
        const totalAmountEl = document.getElementById('totalAmount');
        const collectedAmountEl = document.getElementById('collectedAmount');
        const outstandingAmountEl = document.getElementById('outstandingAmount');

        if (visibleCountEl) visibleCountEl.textContent = totalCount;
        if (totalCountEl) totalCountEl.textContent = totalCount;
        if (totalAmountEl) totalAmountEl.textContent = totalAmount.toFixed(2) + ' SAR';
        if (collectedAmountEl) collectedAmountEl.textContent = collectedAmount.toFixed(2) + ' SAR';
        if (outstandingAmountEl) outstandingAmountEl.textContent = outstandingAmount.toFixed(2) + ' SAR';
    }

    // Helper functions for date calculations
    function getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff)).toISOString().split('T')[0];
    }

    function getWeekEnd(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? 0 : 7);
        return new Date(d.setDate(diff)).toISOString().split('T')[0];
    }

    function getMonthStart(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0];
    }

    function getMonthEnd(date) {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
    }
});

// Action Functions (same as in the investment pool example)
function markAsPaid(paymentId) {
    if (confirm('Mark this payment as paid?')) {
        fetch(`/admin/schedule-payments/${paymentId}/mark-paid`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to mark payment as paid');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while marking payment as paid');
        });
    }
}

function showPaymentDetails(paymentId) {
    // Overlay modal with JSON-driven content to avoid injecting full layout/sidebars
    console.log('Loading payment details for:', paymentId);

    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex; align-items: center; justify-content: center; z-index: 9999;
    `;

    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white; border-radius: 12px; max-width: 800px; width: 90vw;
        max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        position: relative;
    `;

    modalContent.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:20px;background-color:#f8f9fa;border-bottom:1px solid #e9ecef;border-radius:12px 12px 0 0;">
            <h5 style="margin:0;color:#212529;font-weight:600;">Payment Schedule Details</h5>
            <button onclick="this.closest('div[style*=\"fixed\"]').remove()" style="background:none;border:none;font-size:24px;color:#6c757d;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">&times;</button>
        </div>
        <div style="padding: 40px; text-align: center;">
            <div style="display:inline-block;width:32px;height:32px;border:3px solid #f3f3f3;border-top:3px solid #3498db;border-radius:50%;animation: spin 1s linear infinite;"></div>
            <p style="margin-top:16px;color:#6b7280;">Loading payment details...</p>
        </div>
        <style>@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>
    `;

    modal.appendChild(modalContent);
    modal.onclick = function(e){ if(e.target === modal){ modal.remove(); } };
    document.body.appendChild(modal);

    const paymentJsonTemplate = "{{ route('schedulePayments.payment.json', ['schedulePayment' => '__ID__']) }}";
    const jsonUrl = paymentJsonTemplate.replace('__ID__', paymentId);

    fetch(jsonUrl)
        .then(r => { if(!r.ok) throw new Error('Network'); return r.json(); })
        .then(({success, data}) => {
            if(!success || !data) throw new Error('Bad payload');
            const { schedule, payment, user, checkout } = data;
            const currency = (v) => (v == null ? '-' : Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })) + ' SAR';
            const dt = (v) => v ? new Date(v).toLocaleString() : '-';
            const d = (v) => v || '-';

            const badge = (status) => {
                const map = { paid:'#16a34a', pending:'#f59e0b', overdue:'#dc2626', partial:'#06b6d4' };
                const bg = map[status] || '#6b7280';
                return `<span style=\"display:inline-block;padding:4px 8px;border-radius:9999px;color:white;background:${bg};font-size:12px;text-transform:capitalize;\">${status || 'unknown'}</span>`;
            };

            const late = schedule.is_late ? `<span style=\"color:#dc2626;font-size:12px;margin-left:8px;\">${schedule.late_days ?? 0} days late</span>` : '';

            modalContent.innerHTML = `
                <div style=\"display:flex;justify-content:space-between;align-items:center;padding:20px;background-color:#f8f9fa;border-bottom:1px solid #e9ecef;border-radius:12px 12px 0 0;\">
                    <h5 style=\"margin:0;color:#212529;font-weight:600;\">Payment Schedule Details</h5>
                    <button onclick=\"this.closest('div[style*\\"fixed\\"]').remove()\" style=\"background:none;border:none;font-size:24px;color:#6c757d;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;\">&times;</button>
                </div>
                <div style=\"padding:20px;max-height:72vh;overflow:auto;\">
                    <div style=\"display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;\">
                        <div style=\"border:1px solid #e5e7eb;border-radius:8px;padding:16px;\">
                            <div style=\"font-weight:600;color:#374151;margin-bottom:8px;\">Schedule</div>
                            <div style=\"display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;\">
                                <div style=\"color:#6b7280;\">ID</div><div>#${String(schedule.id).padStart(4,'0')}</div>
                                <div style=\"color:#6b7280;\">Installment</div><div>${d(schedule.instalment_number)}</div>
                                <div style=\"color:#6b7280;\">Due date</div><div>${d(schedule.due_date)}</div>
                                <div style=\"color:#6b7280;\">Amount</div><div>${currency(schedule.instalment_amount)}${schedule.late_fee ? ` <span style=\\\"color:#dc2626;font-size:12px;\\\">(+${currency(schedule.late_fee)} late fee)</span>` : ''}</div>
                                <div style=\"color:#6b7280;\">Status</div><div>${badge(schedule.status)}${late}</div>
                            </div>
                        </div>
                        <div style=\"border:1px solid #e5e7eb;border-radius:8px;padding:16px;\">
                            <div style=\"font-weight:600;color:#374151;margin-bottom:8px;\">Payment</div>
                            <div style=\"display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;\">
                                <div style=\"color:#6b7280;\">Status</div><div>${payment ? (payment.status || 'paid') : '-'}</div>
                                <div style=\"color:#6b7280;\">Amount</div><div>${payment ? currency(payment.amount) : '-'}</div>
                                <div style=\"color:#6b7280;\">Method</div><div>${payment?.method || '-'}</div>
                                <div style=\"color:#6b7280;\">Reference</div><div>${payment?.reference || '-'}</div>
                                <div style=\"color:#6b7280;\">Paid at</div><div>${payment ? dt(payment.created_at) : '-'}</div>
                            </div>
                        </div>
                        <div style=\"border:1px solid #e5e7eb;border-radius:8px;padding:16px;\">
                            <div style=\"font-weight:600;color:#374151;margin-bottom:8px;\">Customer</div>
                            <div style=\"display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;\">
                                <div style=\"color:#6b7280;\">Name</div><div>${user?.name || '-'}</div>
                                <div style=\"color:#6b7280;\">Email</div><div>${user?.email || '-'}</div>
                            </div>
                        </div>
                        <div style=\"border:1px solid #e5e7eb;border-radius:8px;padding:16px;\">
                            <div style=\"font-weight:600;color:#374151;margin-bottom:8px;\">Checkout</div>
                            <div style=\"display:grid;grid-template-columns:140px 1fr;row-gap:6px;column-gap:8px;color:#374151;\">
                                <div style=\"color:#6b7280;\">ID</div><div>${checkout?.id ?? '-'}</div>
                                <div style=\"color:#6b7280;\">Total</div><div>${checkout ? currency(checkout.total_amount) : '-'}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style=\"padding:12px 16px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:8px;\">
                    <button type=\"button\" onclick=\"this.closest('div[style*\\\"fixed\\\"]').remove()\" style=\"padding:8px 12px;border:1px solid #d1d5db;background:white;color:#374151;border-radius:6px;cursor:pointer;\">Close</button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error loading payment details:', error);
            modalContent.innerHTML = `
                <div style="padding: 40px; text-align: center; color: #dc2626;">
                    <p style="margin: 0;">Failed to load payment details. Please try again.</p>
                </div>
            `;
        });
}

// Include the same createClaim, sendReminder, and viewClaims functions from the investment pool
function createClaim(paymentId, claimType) {
    // This is the same comprehensive function from the investment pool
    console.log('Creating claim modal for payment:', paymentId, 'type:', claimType);
    // [Include the full createClaim function from the investment pool file]
}

function sendReminder(paymentId) {
    if (confirm('Send payment reminder to customer?')) {
        fetch(`/admin/schedule-payments/${paymentId}/send-reminder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully');
            } else {
                alert('Failed to send reminder');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending reminder');
        });
    }
}

function viewClaims(paymentId) {
    fetch(`/admin/claims/schedule-payment/${paymentId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showClaimsModal(data.claims);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading claims');
    });
}

function showClaimsModal(claims) {
    // Implementation for showing claims modal
    console.log('Showing claims modal for:', claims.length, 'claims');
}
</script>