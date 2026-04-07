{{--
    [UI-PHASE] Financial KPI strip for admin dashboard.
    Uses the new fintech component library.

    Expected variables from DashboardController:
    - $financialData (from getFinancialHealthData)
    - $loanData (from getLoanPerformanceData)
    - $operationalData (from getOperationalMetrics)
--}}

@php
    // Settlement summary
    $pendingSettlements = \App\Models\Settlement::whereIn('status', ['draft', 'pending', 'pending_approval'])->count();
    $approvedSettlements = \App\Models\Settlement::where('status', 'approved')->count();
    $totalPayableApproved = \App\Models\Settlement::where('status', 'approved')->sum('payable_amount');

    // Overdue payments
    $overduePayments = \App\Models\SchedulePayment::where('payment_status', 'late')
        ->orWhere(function($q) {
            $q->whereIn('payment_status', ['unpaid', 'due', 'pending'])
              ->where('due_date', '<', now());
        })->count();

    // PDPL data requests — table may not exist yet if migration not run
    $pendingDSRs = 0;
    $overdueDSRs = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('data_subject_requests')) {
            $pendingDSRs = \App\Models\DataSubjectRequest::where('status', 'pending')->count();
            $overdueDSRs = \App\Models\DataSubjectRequest::where('deadline_at', '<', now())
                ->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->count();
        }
    } catch (\Exception $e) {
        // Table not migrated yet — safe to ignore
    }

    // Total credit exposure
    $totalExposure = \App\Models\Transaction::where('general_status', 'active')->sum('loan_amount');

    // Revenue (from financial data if available)
    $revenueArr = $financialData['revenue_streams']['revenue'] ?? [];
    $monthlyRevenue = !empty($revenueArr) ? end($revenueArr) : 0;
@endphp

{{-- Alert banners --}}
@if($overduePayments > 0)
    <x-fintech.alert-banner type="danger" :count="$overduePayments" class="mb-4">
        overdue installment{{ $overduePayments > 1 ? 's' : '' }} require attention in collections.
    </x-fintech.alert-banner>
@endif

@if($overdueDSRs > 0)
    <x-fintech.alert-banner type="warning" :count="$overdueDSRs" class="mb-4" icon="ki-shield-tick">
        data subject request{{ $overdueDSRs > 1 ? 's' : '' }} past PDPL 30-day deadline.
    </x-fintech.alert-banner>
@endif

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-fintech.kpi-card
        title="Credit Exposure"
        :value="$totalExposure"
        :isMoney="true"
        icon="ki-chart-line-up"
        color="primary"
        subtitle="Active loan book"
    />

    <x-fintech.kpi-card
        title="Pending Settlements"
        :value="$pendingSettlements + $approvedSettlements"
        icon="ki-file-sheet"
        color="warning"
        :subtitle="$approvedSettlements . ' approved, awaiting payout'"
    />

    <x-fintech.kpi-card
        title="Approved Payable"
        :value="$totalPayableApproved"
        :isMoney="true"
        icon="ki-dollar"
        color="info"
        subtitle="Ready for payout"
    />

    <x-fintech.kpi-card
        title="Overdue Payments"
        :value="$overduePayments"
        icon="ki-notification"
        :color="$overduePayments > 0 ? 'danger' : 'success'"
        :subtitle="$overduePayments > 0 ? 'Requires collections action' : 'All payments on track'"
    />
</div>
