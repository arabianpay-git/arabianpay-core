<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PartialPayment;
use App\Models\Product;
use App\Models\Promise;
use App\Models\RefundRequest;
use App\Models\SchedulePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    /**
     * Display the main collections dashboard.
     */
    public function index(Request $request)
    {
        // Read optional date filters (these are global date filters from header)
        $startDate = $request->query('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
        $endDate   = $request->query('date_to')   ? Carbon::parse($request->query('date_to'))->endOfDay()   : null;

        // Chart-specific params
        $collectionRange = $request->query('collection_range'); // expected '7','30','90'
        $dpdPeriod       = $request->query('dpd_period'); // expected 'current','previous','quarter'

        // Prepare start/end for collection performance:
        // Priority: if global date filter provided, use it; otherwise, if collection_range provided, derive last X days.
        $collectionStart = $collectionEnd = null;
        if ($startDate && $endDate) {
            $collectionStart = $startDate;
            $collectionEnd = $endDate;
        } elseif ($collectionRange) {
            // last N days relative to now
            $days = (int) $collectionRange;
            $collectionStart = Carbon::now()->subDays(max(1, $days))->startOfDay();
            $collectionEnd = Carbon::now()->endOfDay();
        }

        // Prepare start/end for DPD distribution:
        // Priority: if global date filter provided, use it; otherwise derive by dpd_period.
        $dpdStart = $dpdEnd = null;
        if ($startDate && $endDate) {
            $dpdStart = $startDate;
            $dpdEnd   = $endDate;
        } else {
            switch ($dpdPeriod) {
                case 'previous':
                    $dpdStart = Carbon::now()->subMonth()->startOfMonth();
                    $dpdEnd   = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'quarter':
                    $dpdStart = Carbon::now()->subMonths(3)->startOfDay();
                    $dpdEnd   = Carbon::now()->endOfDay();
                    break;
                case 'current':
                default:
                    $dpdStart = Carbon::now()->startOfMonth();
                    $dpdEnd   = Carbon::now()->endOfDay();
                    break;
            }
        }

        $monthsRange = 6; // fallback months param if needed by buildCollectionPerformance when start/end not provided

        // Pass computed date windows into helpers:
        $metrics = $this->buildMetrics($startDate, $endDate); // unchanged, optional global filters
        $collectionPerformance = $this->buildCollectionPerformance($monthsRange, $collectionStart, $collectionEnd);
        $dpdDistribution = $this->buildDpdDistribution($dpdStart, $dpdEnd);

        $alerts = $this->buildAlerts($startDate, $endDate);
        $flags = $this->buildFlags($startDate, $endDate);
        $quickActions = $this->buildQuickActions($startDate, $endDate);

        // include installment filters (status, dpd, channel, q) as before
        $status = $request->query('status');
        $dpdFilter = $request->query('dpd'); // this is installments DPD filter (0-30 etc.)
        $channel = $request->query('channel');

        $installments = $this->getRecentInstallments(25, $startDate, $endDate, $status, $dpdFilter, $channel);

        return view('admin.collections.index', compact(
            'metrics',
            'collectionPerformance',
            'dpdDistribution',
            'alerts',
            'flags',
            'quickActions',
            'installments'
        ));
    }

    /**
     * Build top-level metrics (optionally filtered by date range)
     */
    private function buildMetrics(?Carbon $start, ?Carbon $end)
    {
        $today = Carbon::today();

        $baseQuery = SchedulePayment::query();
        if ($start && $end) {
            $baseQuery->whereBetween('due_date', [$start, $end]);
        }

        $totalOutstanding = (clone $baseQuery)
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('SUM(instalment_amount - COALESCE(deducted_amount, 0)) as total_outstanding')
            ->value('total_outstanding');

        $overdueQuery = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereDate('due_date', '<', $today);

        if ($start && $end) {
            $overdueQuery->whereBetween('due_date', [$start, $end]);
        }

        $overdueAmount = $overdueQuery
            ->selectRaw('SUM(instalment_amount - COALESCE(deducted_amount, 0)) as overdue_total')
            ->value('overdue_total');

        // Collection rate = (sum of deducted_amount / sum of instalment_amount) * 100
        $collectionQuery = SchedulePayment::query();
        if ($start && $end) {
            $collectionQuery->whereBetween('due_date', [$start, $end]);
        }

        $totalInstalmentAmount = $collectionQuery->sum('instalment_amount');
        $totalDeductedAmount   = $collectionQuery->sum('deducted_amount');
        $collectionRate = $totalInstalmentAmount > 0
            ? round(($totalDeductedAmount / $totalInstalmentAmount) * 100, 1)
            : 0.0;

        // Active promises = where promise_date >= today (respect range if provided)
        $promisesQuery = Promise::query();

        if ($start && $end) {
            $promisesQuery->whereBetween('promise_date', [$start->toDateString(), $end->toDateString()]);
        } else {
            $promisesQuery->whereDate('promise_date', '>=', $today);
        }

        $activePromises = $promisesQuery->count();

        return [
            'total_collection' => [
                'value' => number_format($totalOutstanding ?? 0, 2),
                'change' => $this->pctChangePlaceholder(),
                'trend' => $totalOutstanding > 0 ? 'up' : 'down',
                'icon' => 'icon-saudi_riyal',
                'color' => 'primary'
            ],
            'overdue_amount' => [
                'value' => number_format($overdueAmount ?? 0, 2),
                'change' => $this->pctChangePlaceholder(),
                'trend' => $overdueAmount > 0 ? 'up' : 'down',
                'icon' => 'ki-filled ki-watch',
                'color' => 'danger'
            ],
            'collection_rate' => [
                'value' => $collectionRate . '%',
                'change' => $this->pctChangePlaceholder(),
                'trend' => $collectionRate >= 50 ? 'up' : 'down',
                'icon' => 'ki-filled ki-chart-line',
                'color' => 'success'
            ],
            'active_promises' => [
                'value' => (string) $activePromises,
                'change' => $this->pctChangePlaceholder(),
                'trend' => $activePromises > 0 ? 'down' : 'up',
                'icon' => 'ki-filled ki-calendar-8',
                'color' => 'warning'
            ],
        ];
    }

    /**
     * Collection performance (SchedulePayment based)
     *
     */
    private function buildCollectionPerformance($months = 6, ?Carbon $start = null, ?Carbon $end = null)
    {
        $today = Carbon::today();

        // helper to build labels from range
        $buildLabels = function (Carbon $s, Carbon $e) {
            $labels = [];
            $periodStart = $s->copy()->startOfMonth();
            $periodEnd = $e->copy()->endOfMonth();
            $cursor = $periodStart->copy();
            while ($cursor <= $periodEnd) {
                $labels[] = $cursor->format('M Y');
                $cursor->addMonth();
            }
            return $labels;
        };

        // If explicit date range provided
        if ($start && $end) {
            $labels = $buildLabels($start, $end);

            // Main aggregations (collected, target, remaining) grouped by due_date month
            $mainRaw = DB::table('schedule_payments')
                ->select(
                    DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"),
                    DB::raw('SUM(COALESCE(deducted_amount,0)) as collected'),
                    DB::raw('SUM(instalment_amount) as target'),
                    DB::raw('SUM(instalment_amount - COALESCE(deducted_amount,0)) as remaining')
                )
                ->whereBetween('due_date', [$start, $end])
                ->groupBy('month')
                ->pluck('collected', 'month');

            // fetch target and remaining similarly (we'll run the same query again but pluck different columns)
            $targetRaw = DB::table('schedule_payments')
                ->select(DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"), DB::raw('SUM(instalment_amount) as target'))
                ->whereBetween('due_date', [$start, $end])
                ->groupBy('month')
                ->pluck('target', 'month')
                ->toArray();

            $remainingRaw = DB::table('schedule_payments')
                ->select(DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"), DB::raw('SUM(instalment_amount - COALESCE(deducted_amount,0)) as remaining'))
                ->whereBetween('due_date', [$start, $end])
                ->groupBy('month')
                ->pluck('remaining', 'month')
                ->toArray();

            // Overdue: only those in overdue states and due_date has passed (due_date < today)
            $overdueRaw = DB::table('schedule_payments')
                ->select(DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"), DB::raw('SUM(instalment_amount - COALESCE(deducted_amount,0)) as overdue'))
                ->whereIn('payment_status', ['due', 'late'])
                ->whereBetween('due_date', [$start, $end])
                ->whereDate('due_date', '<', $today)
                ->groupBy('month')
                ->pluck('overdue', 'month')
                ->toArray();

            // build arrays aligned with $labels
            $collected = [];
            $target = [];
            $remaining = [];
            $overdueSeries = [];

            foreach ($labels as $m) {
                $collected[] = (float) ($mainRaw->get($m) ?? 0);
                $target[] = isset($targetRaw[$m]) ? (float)$targetRaw[$m] : 0;
                $remaining[] = isset($remainingRaw[$m]) ? (float)$remainingRaw[$m] : 0;
                $overdueSeries[] = isset($overdueRaw[$m]) ? (float)$overdueRaw[$m] : 0;
            }

            return [
                'labels' => $labels,
                'collected' => $collected,   // SUM(deducted_amount)
                'target' => $target,         // SUM(instalment_amount)
                'remaining' => $remaining,   // SUM(instalment_amount - deducted_amount)
                'overdue' => $overdueSeries, // SUM(instalment_amount - deducted_amount) where due_date < today and status due|late
            ];
        }

        // default: last $months months window
        $end = Carbon::now();
        $start = $end->copy()->subMonths($months - 1)->startOfMonth();
        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        // aggregated main metrics for default window
        $mainRaw = DB::table('schedule_payments')
            ->select(
                DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"),
                DB::raw('SUM(COALESCE(deducted_amount,0)) as collected'),
                DB::raw('SUM(instalment_amount) as target'),
                DB::raw('SUM(instalment_amount - COALESCE(deducted_amount,0)) as remaining')
            )
            ->whereBetween('due_date', [$start, $end])
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // overdue series in default window (only due/late and due_date < today)
        $overdueRaw = DB::table('schedule_payments')
            ->select(DB::raw("DATE_FORMAT(due_date, '%b %Y') as month"), DB::raw('SUM(instalment_amount - COALESCE(deducted_amount,0)) as overdue'))
            ->whereIn('payment_status', ['due', 'late'])
            ->whereBetween('due_date', [$start, $end])
            ->whereDate('due_date', '<', $today)
            ->groupBy('month')
            ->pluck('overdue', 'month')
            ->toArray();

        $collected = [];
        $target = [];
        $remaining = [];
        $overdueSeries = [];

        foreach ($labels as $m) {
            $row = $mainRaw->get($m);
            $collected[] = $row ? (float)$row->collected : 0;
            $target[] = $row ? (float)$row->target : 0;
            $remaining[] = $row ? (float)$row->remaining : 0;
            $overdueSeries[] = isset($overdueRaw[$m]) ? (float)$overdueRaw[$m] : 0;
        }

        return [
            'labels' => $labels,
            'collected' => $collected,
            'target' => $target,
            'remaining' => $remaining,
            'overdue' => $overdueSeries,
        ];
    }

    /**
     * DPD distribution (optionally limited to schedule payments due between start & end)
     */
    private function buildDpdDistribution(?Carbon $start = null, ?Carbon $end = null)
    {
        $today = Carbon::today();

        $baseQuery = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereDate('due_date', '<', $today);

        if ($start && $end) {
            $baseQuery->whereBetween('due_date', [$start, $end]);
        }

        $bucket1 = (clone $baseQuery)->whereRaw("DATEDIFF(?, due_date) BETWEEN 0 AND 30", [$today])->count();
        $bucket2 = (clone $baseQuery)->whereRaw("DATEDIFF(?, due_date) BETWEEN 31 AND 60", [$today])->count();
        $bucket3 = (clone $baseQuery)->whereRaw("DATEDIFF(?, due_date) BETWEEN 61 AND 90", [$today])->count();
        $bucket4 = (clone $baseQuery)->whereRaw("DATEDIFF(?, due_date) > 90", [$today])->count();

        return [
            'labels' => ['0-30 DPD', '31-60 DPD', '61-90 DPD', '90+ DPD'],
            'data' => [(int)$bucket1, (int)$bucket2, (int)$bucket3, (int)$bucket4],
            'colors' => ['#50CD89', '#FFC700', '#F1416C', '#7239EA']
        ];
    }

    /**
     * View all alerts (list)
     */
    public function viewAlerts(Request $request)
    {
        $start = $request->query('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
        $end   = $request->query('date_to')   ? Carbon::parse($request->query('date_to'))->endOfDay()   : null;

        // Reuse your helper which returns an array of alerts
        $alerts = $this->buildAlerts($start, $end);

        // Pass date filters back to view so filters are prefilled
        return view('admin.collections.alerts', [
            'alerts' => $alerts,
            'date_from' => $start ? $start->toDateString() : null,
            'date_to' => $end ? $end->toDateString() : null,
        ]);
    }

    /**
     * View all flags (list)
     */
    public function viewFlags(Request $request)
    {
        $start = $request->query('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
        $end   = $request->query('date_to')   ? Carbon::parse($request->query('date_to'))->endOfDay()   : null;

        // Reuse your helper which returns an array of flags
        $flags = $this->buildFlags($start, $end);

        return view('admin.collections.flags', [
            'flags' => $flags,
            'date_from' => $start ? $start->toDateString() : null,
            'date_to' => $end ? $end->toDateString() : null,
        ]);
    }

    /**
     * Enhanced Alerts with more information
     */
    private function buildAlerts(?Carbon $start = null, ?Carbon $end = null)
    {
        $today = Carbon::today();

        // Thresholds (tweak as needed)
        $THRESHOLD_CRITICAL_DPD = 60;       // days
        $THRESHOLD_LARGE_OVERDUE = 10000;  // currency units (SAR)
        $THRESHOLD_FAILED_PAYMENTS = 5;    // count
        $THRESHOLD_PENDING_ALLOCATIONS = 20; // count
        $alerts = [];

        // --- 1) Critical: accounts exceeded X DPD (only 'due'|'late' considered overdue)
        $critQuery = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereRaw("DATEDIFF(?, due_date) > ?", [$today, $THRESHOLD_CRITICAL_DPD])
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $critQuery->whereBetween('due_date', [$start, $end]);
        }

        $criticalAccounts = $critQuery->get();
        $critCount = $criticalAccounts->count();

        if ($critCount > 0) {
            $userNames = $criticalAccounts->take(3)->map(function ($account) {
                $user = $account->order->user ?? $account->order->merchant;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e(trim($name)) . "</a>";
            })->implode(', ');

            $remainingCount = $critCount - 3;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : "";

            $alerts[] = [
                'type' => 'critical',
                'title' => "{$critCount} accounts exceeded {$THRESHOLD_CRITICAL_DPD} DPD",
                'description' => "Requires immediate attention — high risk of default. Affected users: {$userNames}{$additionalText}",
                'time' => 'Just now',
                'icon' => 'ki-filled ki-information-2',
                'additional_info' => [
                    'affected_users_count' => $critCount,
                    'dpd_threshold' => $THRESHOLD_CRITICAL_DPD,
                    'top_affected_users' => $criticalAccounts->take(3)->map(function ($account) use ($today) {
                        $user = $account->order->user ?? $account->order->merchant;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'link' => $user ? route('customerProfile', ['id' => $user->id]) : '#',
                            'dpd' => abs($today->diffInDays($account->due_date, false)),
                            'amount' => $account->instalment_amount,
                        ];
                    })->toArray()
                ]
            ];
        }

        // --- 2) Broken promises: promises where promise_date has passed AND linked schedule payment is not paid
        $brokenPromisesQuery = Promise::whereDate('promise_date', '<', $today)
            ->whereHas('schedulePayment', function ($q) {
                $q->whereIn('payment_status', ['pending', 'due', 'late', 'failed']);
            })
            ->with(['schedulePayment.order.customer', 'schedulePayment.order.seller']);

        if ($start && $end) {
            $brokenPromisesQuery->whereBetween('promise_date', [$start, $end]);
        }

        $brokenPromisesList = $brokenPromisesQuery->get();
        $brokenPromisesCount = $brokenPromisesList->count();

        if ($brokenPromisesCount > 0) {
            $userNames = $brokenPromisesList->take(3)->map(function ($promise) {
                $user = $promise->schedulePayment->order->user ?? $promise->schedulePayment->order->merchant;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e(trim($name)) . "</a>";
            })->implode(', ');

            $remainingCount = $brokenPromisesCount - 3;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : "";

            $alerts[] = [
                'type' => 'high',
                'title' => "Promise-to-pay broken for {$brokenPromisesCount} accounts",
                'description' => "Follow-up actions required. Users with broken promises: {$userNames}{$additionalText}",
                'time' => 'Recently',
                'icon' => 'ki-filled ki-cross-circle',
                'additional_info' => [
                    'broken_promises_count' => $brokenPromisesCount,
                    'promise_date_range' => $start && $end ? $start->format('M d') . ' - ' . $end->format('M d') : 'Recent',
                    'affected_users' => $brokenPromisesList->take(3)->map(function ($promise) use ($today) {
                        $user = $promise->schedulePayment->order->user ?? $promise->schedulePayment->order->merchant;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'link' => $user ? route('customerProfile', ['id' => $user->id]) : '#',
                            'promise_date' => Carbon::parse($promise->promise_date)->format('M d, Y'),
                            'days_overdue' => abs($today->diffInDays($promise->promise_date, false))
                        ];
                    })->toArray()
                ]
            ];
        }

        // --- 3) Dunning run summary: number of accounts evaluated for reminders
        $dunningStatuses = ['pending', 'due', 'late', 'failed'];
        $dunningSentCountQuery = SchedulePayment::whereIn('payment_status', $dunningStatuses)
            ->whereDate('due_date', '<=', $today)
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $dunningSentCountQuery->whereBetween('due_date', [$start, $end]);
        }

        $dunningAccounts = $dunningSentCountQuery->get();
        $dunningSentCount = $dunningAccounts->count();

        $alerts[] = [
            'type' => 'info',
            'title' => 'Dunning run summary',
            'description' => "{$dunningSentCount} accounts evaluated for reminders. " .
                "Status breakdown: " .
                $dunningAccounts->groupBy('payment_status')->map(function ($group, $status) {
                    return ucfirst($status) . " (" . $group->count() . ")";
                })->implode(', '),
            'time' => $today->format('M d, Y H:i'),
            'icon' => 'ki-filled ki-check-circle',
            'additional_info' => [
                'total_evaluated' => $dunningSentCount,
                'status_breakdown' => $dunningAccounts->groupBy('payment_status')->map->count()->toArray(),
                'date_range' => $start && $end ? $start->format('M d, Y') . ' to ' . $end->format('M d, Y') : 'All time'
            ]
        ];

        // --- 4) Large overdue amount: sum of remaining unpaid portions for overdue rows (due|late & due_date < today)
        $overdueQuery = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereDate('due_date', '<', $today)
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $overdueQuery->whereBetween('due_date', [$start, $end]);
        }

        $overdueAccounts = $overdueQuery->get();
        $overdueAmount = $overdueAccounts->sum(function ($payment) {
            return max(0, $payment->instalment_amount - ($payment->deducted_amount ?? 0));
        });

        if ($overdueAmount >= $THRESHOLD_LARGE_OVERDUE) {
            $topOverdueUsers = $overdueAccounts->sortByDesc(function ($payment) {
                return $payment->instalment_amount - ($payment->deducted_amount ?? 0);
            })->take(3);

            $userList = $topOverdueUsers->map(function ($payment) {
                $user = $payment->order->user ?? $payment->order->merchant;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                $amount = $payment->instalment_amount - ($payment->deducted_amount ?? 0);
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e(trim($name)) . "</a> (SAR " . number_format($amount, 2) . ")";
            })->implode(', ');

            $remainingCount = $overdueAccounts->count() - 3;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more accounts" : "";

            $alerts[] = [
                'type' => 'high',
                'title' => 'Large overdue balance',
                'description' => "Total overdue amount is SAR " . number_format($overdueAmount, 2) . ". Top overdue accounts: {$userList}{$additionalText}",
                'time' => 'Just now',
                'icon' => 'ki-filled ki-exclamation',
                'additional_info' => [
                    'total_overdue_amount' => $overdueAmount,
                    'affected_accounts_count' => $overdueAccounts->count(),
                    'top_overdue_accounts' => $topOverdueUsers->map(function ($payment) {
                        $user = $payment->order->user ?? $payment->order->merchant;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'overdue_amount' => $payment->instalment_amount - ($payment->deducted_amount ?? 0),
                            'due_date' => $payment->due_date->format('M d, Y'),
                            'days_overdue' => Carbon::today()->diffInDays($payment->due_date)
                        ];
                    })->toArray()
                ]
            ];
        }

        // --- 5) Failed payments spike
        $failedPaymentsCountQuery = SchedulePayment::where('payment_status', 'failed')
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $failedPaymentsCountQuery->whereBetween('due_date', [$start, $end]);
        }

        $failedPayments = $failedPaymentsCountQuery->get();
        $failedPaymentsCount = $failedPayments->count();

        if ($failedPaymentsCount >= $THRESHOLD_FAILED_PAYMENTS) {
            $recentFailedUsers = $failedPayments->take(3);

            $userList = $recentFailedUsers->map(function ($payment) {
                $user = $payment->order->user ?? $payment->order->merchant;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e(trim($name)) . "</a>";
            })->implode(', ');

            $remainingCount = $failedPaymentsCount - 3;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : "";

            $alerts[] = [
                'type' => 'warning',
                'title' => "{$failedPaymentsCount} failed payment attempts detected",
                'description' => "Investigate payment gateway or customer card issues. Recent failed payments from: {$userList}{$additionalText}",
                'time' => 'Recently',
                'icon' => 'ki-filled ki-information-3',
                'additional_info' => [
                    'failed_payments_count' => $failedPaymentsCount,
                    'recent_failed_users' => $recentFailedUsers->map(function ($payment) {
                        $user = $payment->order->user ?? $payment->order->merchant;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'amount' => $payment->instalment_amount,
                            'failed_date' => $payment->due_date->format('M d, Y')
                        ];
                    })->toArray(),
                    'total_failed_amount' => $failedPayments->sum('instalment_amount')
                ]
            ];
        }

        // --- 6) Pending allocations backlog (many 'pending' statuses)
        $pendingAllocQuery = SchedulePayment::where('payment_status', 'pending')
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $pendingAllocQuery->whereBetween('due_date', [$start, $end]);
        }

        $pendingAllocations = $pendingAllocQuery->get();
        $pendingAllocCount = $pendingAllocations->count();

        if ($pendingAllocCount >= $THRESHOLD_PENDING_ALLOCATIONS) {
            $pendingUsers = $pendingAllocations->take(3);

            $userList = $pendingUsers->map(function ($payment) {
                $user = $payment->order->user ?? $payment->order->merchant;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='nderline'>" . e(trim($name)) . "</a>";
            })->implode(', ');

            $remainingCount = $pendingAllocCount - 3;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : "";

            $alerts[] = [
                'type' => 'warning',
                'title' => "Allocation backlog: {$pendingAllocCount} items pending",
                'description' => "Payments require allocation/approval. Users awaiting allocation: {$userList}{$additionalText}",
                'time' => 'Recently',
                'icon' => 'ki-filled ki-clock',
                'additional_info' => [
                    'pending_count' => $pendingAllocCount,
                    'total_pending_amount' => $pendingAllocations->sum('instalment_amount'),
                    'users_awaiting_allocation' => $pendingUsers->map(function ($payment) {
                        $user = $payment->order->user ?? $payment->order->merchant;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'amount' => $payment->instalment_amount,
                            'due_date' => $payment->due_date->format('M d, Y')
                        ];
                    })->toArray()
                ]
            ];
        }

        // --- 7) Top promise-breakers (users with multiple broken promises)
        $topBreakers = Promise::whereDate('promise_date', '<', $today)
            ->whereHas('schedulePayment', function ($q) {
                $q->whereIn('payment_status', ['pending', 'due', 'late', 'failed']);
            })
            ->with(['user'])
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->limit(3)
            ->get();

        if ($topBreakers->isNotEmpty()) {
            $items = $topBreakers->map(function ($b) {
                $user = $b->user;
                $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'User #' . $b->user_id;
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e($name) . "</a> ({$b->cnt} broken promises)";
            })->implode(', ');

            $alerts[] = [
                'type' => 'info',
                'title' => 'Top promise-breakers',
                'description' => $items,
                'time' => 'Recently',
                'icon' => 'ki-filled ki-shield-cross',
                'additional_info' => [
                    'top_breakers' => $topBreakers->map(function ($breaker) {
                        $user = $breaker->user;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'User #' . $breaker->user_id,
                            'broken_promises_count' => $breaker->cnt,
                            'user_id' => $breaker->user_id
                        ];
                    })->toArray()
                ]
            ];
        }

        // optional: ensure there's always at least one info alert
        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'No critical alerts',
                'description' => 'All monitored KPIs are within configured thresholds',
                'time' => $today->format('M d, Y H:i'),
                'icon' => 'ki-filled ki-check-circle',
                'additional_info' => [
                    'monitoring_status' => 'All systems normal',
                    'last_checked' => $today->format('Y-m-d H:i:s')
                ]
            ];
        }

        return $alerts;
    }

    /**
     * Enhanced Priority flags with more information
     */
    private function buildFlags(?Carbon $start = null, ?Carbon $end = null)
    {
        $today = Carbon::today();
        $flags = [];

        // --- 1) High-value account at risk (largest remaining overdue amount)
        $highValueQuery = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereDate('due_date', '<', $today)
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $highValueQuery->whereBetween('due_date', [$start, $end]);
        }

        $highValue = (clone $highValueQuery)
            ->selectRaw('*, (instalment_amount - COALESCE(deducted_amount,0)) as remaining_amount')
            ->orderByDesc('remaining_amount')
            ->with('order')
            ->first();

        if ($highValue) {
            $remaining = max(0, ($highValue->instalment_amount - ($highValue->deducted_amount ?? 0)));
            $dpd = $highValue->due_date ? abs($today->diffInDays($highValue->due_date, false)) : 0;

            $user = $highValue->order->user ?? $highValue->order->merchant;
            $merchantName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : ('User #' . ($highValue->user_id ?? 'N/A'));
            $userEmail = $user->email ?? 'N/A';
            $userPhone = $user->phone ?? 'N/A';

            $flags[] = [
                'type' => 'high_risk',
                'title' => 'High-value account at risk',
                'description' => "Account <a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e($merchantName) . "</a> - SAR " . number_format($remaining, 2) . " overdue ({$dpd} DPD)",
                'icon' => 'ki-filled ki-security-user',
                'tags' => ['Overdue', 'VIP', $dpd . ' DPD'],
                'additional_info' => [
                    'user_name' => $merchantName,
                    'user_email' => $userEmail,
                    'user_phone' => $userPhone,
                    'overdue_amount' => $remaining,
                    'days_past_due' => $dpd . ' ' . ($dpd === 1 ? 'Day' : 'Days'),
                    'due_date' => $highValue->due_date->format('M d, Y'),
                    'order_id' => isset($highValue->order->id)
                        ? "<a href='" . route('orders.details', ['id' => $highValue->order->id]) . "' class='underline'>"
                        . e($highValue->order->tracking) .
                        "</a>"
                        : 'N/A',
                    'instalment_amount' => $highValue->instalment_amount,
                    'deducted_amount' => $highValue->deducted_amount ?? 0
                ]
            ];
        }

        // --- 2) Promise-to-pay due today (or within provided range)
        if ($start && $end) {
            $promisesDueQuery = Promise::whereBetween('promise_date', [$start, $end])
                ->with(['user', 'schedulePayment.order']);
        } else {
            $promisesDueQuery = Promise::whereDate('promise_date', '=', $today)
                ->with(['user', 'schedulePayment.order']);
        }

        $promisesDueList = $promisesDueQuery->get();
        $promisesDueToday = $promisesDueList->count();

        if ($promisesDueToday) {
            $userNames = $promisesDueList->take(3)->map(function ($promise) {
                $user = $promise->user;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e(trim($name)) . "</a>";
            })->implode(', ');

            $remainingCount = $promisesDueToday - 3;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : "";

            $flags[] = [
                'type' => 'promise_due',
                'title' => 'Promise-to-pay due',
                'description' => "{$promisesDueToday} accounts with promises due. Including: {$userNames}{$additionalText}",
                'icon' => 'ki-filled ki-calendar-8',
                'tags' => ['Today', 'Promise', $promisesDueToday . ' Accounts'],
                'additional_info' => [
                    'total_promises_due' => $promisesDueToday,
                    'promise_date' => $today->format('M d, Y'),
                    'users_with_promises' => $promisesDueList->take(5)->map(function ($promise) {
                        $user = $promise->user;
                        $schedule = $promise->schedulePayment;
                        return [
                            'name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'user_id' => $user->id ?? null,
                            'promise_amount' => $promise->amount ?? ($schedule->instalment_amount ?? 0),
                            'promise_date' => Carbon::parse($promise->promise_date)->format('M d, Y')
                        ];
                    })->toArray()
                ]
            ];
        }

        // --- 3) Allocation pending approvals (payments in 'pending')
        $allocationPendingQuery = SchedulePayment::where('payment_status', 'pending')
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $allocationPendingQuery->whereBetween('due_date', [$start, $end]);
        }

        $pendingAllocations = $allocationPendingQuery->get();
        $allocationPendingCount = $pendingAllocations->count();
        $totalPendingAmount = $pendingAllocations->sum('instalment_amount');

        if ($allocationPendingCount > 0) {
            $pendingUsers = $pendingAllocations->take(2)->map(function ($payment) {
                $user = $payment->order->user ?? $payment->order->merchant;
                $name = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
                return "<a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e(trim($name)) . "</a>";
            })->implode(', ');

            $remainingCount = $allocationPendingCount - 2;
            $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : "";

            $flags[] = [
                'type' => 'allocation_pending',
                'title' => 'Allocation pending approval',
                'description' => "{$allocationPendingCount} payments (SAR " . number_format($totalPendingAmount, 2) . ") require allocation approval. Including users: {$pendingUsers}{$additionalText}",
                'icon' => 'ki-filled ki-dollar',
                'tags' => ['Pending', $allocationPendingCount . ' Items', 'SAR ' . number_format($totalPendingAmount, 0)],
                'additional_info' => [
                    'pending_count' => $allocationPendingCount,
                    'total_amount' => $totalPendingAmount,
                    'average_amount' => $allocationPendingCount > 0 ? $totalPendingAmount / $allocationPendingCount : 0,
                    'users_affected' => $pendingAllocations->groupBy('user_id')->count(),
                    'top_pending_items' => $pendingAllocations->take(3)->map(function ($payment) {
                        $user = $payment->order->user ?? $payment->order->merchant;
                        return [
                            'user_name' => $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User',
                            'amount' => $payment->instalment_amount,
                            'due_date' => $payment->due_date->format('M d, Y'),
                            'order_id' => isset($payment->order->id)
                                ? "<a href='" . route('orders.details', ['id' => $payment->order->id]) . "' class='underline'>"
                                . e($payment->order->tracking) .
                                "</a>"
                                : 'N/A',
                        ];
                    })->toArray()
                ]
            ];
        }

        // --- 4) Recently failed payments (flag top failed payment)
        $recentFailedQuery = SchedulePayment::where('payment_status', 'failed')
            ->with(['order.customer', 'order.seller']);

        if ($start && $end) {
            $recentFailedQuery->whereBetween('due_date', [$start, $end]);
        }

        $recentFailedList = $recentFailedQuery->get();
        $recentFailedTop = $recentFailedList->sortByDesc('instalment_amount')->first();

        if ($recentFailedTop) {
            $user = $recentFailedTop->order->user ?? $recentFailedTop->order->merchant;
            $userName = $user ? ($user->first_name . ' ' . ($user->last_name ?? '')) : 'Unknown User';
            $userEmail = $user->email ?? 'N/A';

            $failedCount = $recentFailedList->count();
            $totalFailedAmount = $recentFailedList->sum('instalment_amount');

            $flags[] = [
                'type' => 'failed_payment',
                'title' => 'Large failed payment',
                'description' => "Order #" . optional($recentFailedTop->order)->id . " for <a href='" . route('customerProfile', ['id' => $user->id]) . "' class='underline'>" . e($userName) . "</a> — SAR " . number_format($recentFailedTop->instalment_amount, 2),
                'icon' => 'ki-filled ki-information-3',
                'tags' => ['Failed', 'Large Amount', $failedCount . ' Total'],
                'additional_info' => [
                    'user_name' => $userName,
                    'user_email' => $userEmail,
                    'order_id' => isset($recentFailedTop->order->id)
                        ? "<a href='" . route('orders.details', ['id' => $recentFailedTop->order->id]) . "' class='underline'>"
                        . e($recentFailedTop->order->tracking) .
                        "</a>"
                        : 'N/A',
                    'failed_amount' => $recentFailedTop->instalment_amount,
                    'failed_date' => $recentFailedTop->due_date->format('M d, Y'),
                    'total_failed_count' => $failedCount,
                    'total_failed_amount' => $totalFailedAmount,
                    'failure_reason' => $recentFailedTop->failure_reason ?? 'Unknown'
                ]
            ];
        }

        return $flags;
    }

    /**
     * Quick actions
     */
    private function buildQuickActions(?Carbon $start = null, ?Carbon $end = null)
    {
        $promiseCount = Promise::count();
        $dunningCount = SchedulePayment::whereIn('payment_status', ['due', 'late'])->whereDate('due_date', '<', Carbon::today())->count();
        $allocationsCount = SchedulePayment::where('payment_status', 'pending')->count();
        $penaltiesCount = SchedulePayment::where('late_fee', '>', 0)->whereIn('payment_status', ['due', 'late'])->count();

        return [
            [
                'title' => 'Promise-to-Pay',
                'description' => 'Create payment promises',
                'icon' => 'ki-filled ki-calendar-8',
                'route' => route('collections.promisetopay'),
                'count' => $promiseCount
            ],
            [
                'title' => 'Dunning',
                'description' => 'Send reminders',
                'icon' => 'ki-filled ki-sms',
                'route' => route('dunning.index'),
                'count' => $dunningCount
            ],
            [
                'title' => 'Allocations',
                'description' => 'Process payments',
                'icon' => 'ki-filled ki-badge',
                'route' => route('collections.allocations'),
                'count' => $allocationsCount
            ],
            [
                'title' => 'Penalties & Fees',
                'description' => 'Manage charges',
                'icon' => 'ki-filled ki-calculator',
                'route' => route('collections.penalties'),
                'count' => $penaltiesCount
            ]
        ];
    }

    /**
     * Recent installments (optionally filter by due_date range)
     */
    /**
     * Recent installments (optionally filter by due_date range + status/dpd/channel/search)
     *
     * @param int $limit
     * @param Carbon|null $start
     * @param Carbon|null $end
     * @param string|null $status
     * @param string|null $dpd
     * @param string|null $channel
     * @param string|null $q
     * @return array
     */
    private function getRecentInstallments($limit = 10, ?Carbon $start = null, ?Carbon $end = null, ?string $status = null, ?string $dpd = null, ?string $channel = null)
    {
        $qbuilder = SchedulePayment::with(['order.user', 'order.seller', 'user']);

        if ($start && $end) {
            $qbuilder->whereBetween('due_date', [$start, $end]);
        }

        // status filter (payment_status is on schedule_payments)
        if ($status) {
            $qbuilder->where('payment_status', $status);
        }

        // channel filter (payment_type lives on orders)
        if ($channel) {
            $qbuilder->whereHas('order', function ($oq) use ($channel) {
                $oq->where('payment_type', $channel);
            });
        }

        // dpd filter: convert token to numeric ranges
        if ($dpd) {
            $today = Carbon::today();
            if ($dpd === '0-30') {
                $qbuilder->whereRaw("DATEDIFF(?, due_date) BETWEEN 0 AND 30", [$today]);
            } elseif ($dpd === '31-60') {
                $qbuilder->whereRaw("DATEDIFF(?, due_date) BETWEEN 31 AND 60", [$today]);
            } elseif ($dpd === '61-90') {
                $qbuilder->whereRaw("DATEDIFF(?, due_date) BETWEEN 61 AND 90", [$today]);
            } elseif ($dpd === '90+') {
                $qbuilder->whereRaw("DATEDIFF(?, due_date) > 90", [$today]);
            }
        }

        $rows = $qbuilder->orderByDesc('due_date')->limit($limit)->get();
        $today = Carbon::today();

        return $rows->map(function ($r) use ($today) {
            // merchant = order.user first + last (safe)
            $merchantFirst = optional($r->order->user)->first_name ?? '';
            $merchantLast  = optional($r->order->user)->last_name ?? '';
            $merchantName  = trim($merchantFirst . ' ' . $merchantLast);
            $merchant = $merchantName ?: 'N/A';

            $merchantBusiness = optional($r->order->user)->business_name ?? 'N/A';

            $orderCode = optional($r->order)->tracking ?? optional($r->order)->invoice_number
                ?? ('ORD-' . ($r->order_id ?? 'N/A'));

            // order_id should be the actual order->id (per your request)
            $orderId = optional($r->order)->id ?? 'N/A';

            $dueDate = $r->due_date ? Carbon::parse($r->due_date)->format('d M Y') : 'N/A';
            $amount = '<span class="icon-saudi_riyal"></span>' . number_format($r->instalment_amount, 2);
            $status = $r->payment_status ?? 'unknown';
            $dpd = ($r->due_date && $r->due_date->lessThan($today)) ? $today->diffInDays($r->due_date) : 0;
            $channel = optional($r->order)->payment_type ?? 'N/A';

            return [
                'merchant' => $merchant,
                'merchant_business' => $merchantBusiness,
                'order' => $orderCode,
                'order_id' => $orderId,
                'due_date' => $dueDate,
                'amount' => $amount,
                'status' => $status,
                'dpd' => $dpd,
                'channel' => $channel
            ];
        })->toArray();
    }

    private function pctChangePlaceholder()
    {
        return '+0.0%';
    }

    /**
     * Installments page.
     */
    public function installments(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $from   = $request->query('from');
        $to     = $request->query('to');

        $ordersQuery = Order::with(['schedulePayments', 'user'])
            ->whereHas('schedulePayments');

        // Status filter
        if ($status) {
            $ordersQuery->whereHas('schedulePayments', fn($q) => $q->where('payment_status', $status));
        }

        // Date filter for schedule payments
        if ($from || $to) {
            $ordersQuery->whereHas('schedulePayments', function ($q) use ($from, $to) {
                if ($from) $q->whereDate('due_date', '>=', $from);
                if ($to)   $q->whereDate('due_date', '<=', $to);
            });
        }

        // Search filter
        if ($search) {
            $ordersQuery = $this->applySearch($ordersQuery, $search);
        }

        $orders = $ordersQuery->orderBy('created_at', 'desc')->paginate(10);

        if ($request->ajax()) {
            return view('admin.collections.partials.installments_table', compact('orders'))->render();
        }

        return view('admin.collections.installments', compact('orders'));
    }

    /**
     * Apply search filters on orders.
     */
    private function applySearch($query, $search)
    {
        $search = trim($search);
        $terms  = explode(' ', strtolower($search));

        return $query->where(function ($q) use ($terms, $search) {
            // Search by decrypted tracking (if possible)
            $q->where('tracking', 'like', "%{$search}%"); // simple SQL search if tracking is searchable

            // Search by user fields
            $q->orWhereHas('user', function ($q2) use ($terms, $search) {
                $q2->where(function ($u) use ($terms, $search) {
                    $u->whereRaw('LOWER(business_name) like ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(first_name) like ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(last_name) like ?', ["%{$search}%"]);

                    // Also check individual terms
                    foreach ($terms as $term) {
                        $u->orWhereRaw('LOWER(business_name) like ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(first_name) like ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(last_name) like ?', ["%{$term}%"]);
                    }
                });
            });
        });
    }

    public function installmentsCalander(Request $request)
    {
        $status = $request->query('status'); // can be null or 'all'
        $search = $request->query('search');
        $from = $request->query('from');
        $to = $request->query('to');

        $ordersQuery = Order::with(['schedulePayments', 'user'])
            ->whereHas('schedulePayments');

        // Status filter
        if ($status && $status !== 'all') {
            $ordersQuery->whereHas('schedulePayments', fn($q) => $q->where('payment_status', $status));
        }

        // Search filter
        if ($search) {
            $search = strtolower(trim($search));
            $ordersQuery->whereHas('user', function ($q) use ($search) {
                $q->whereRaw('LOWER(business_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"]);
            });
        }

        // Date filters
        if ($from) {
            $ordersQuery->whereHas('schedulePayments', fn($q) => $q->whereDate('due_date', '>=', $from));
        }
        if ($to) {
            $ordersQuery->whereHas('schedulePayments', fn($q) => $q->whereDate('due_date', '<=', $to));
        }

        $orders = $ordersQuery->orderBy('created_at', 'desc')->get();

        $calendarEvents = $orders->map(function ($order) {
            return $order->schedulePayments->map(function ($p) use ($order) {
                // Map status to color class
                $statusClass = [
                    'pending' => 'event-upcoming',
                    'due' => 'event-upcoming',
                    'late' => 'event-overdue',
                    'overdue' => 'event-overdue',
                    'paid' => 'event-paid',
                    'failed' => 'event-failed',
                    'promise' => 'event-promise'
                ][$p->payment_status] ?? 'event-default';

                // DPD: Days Past Due
                $dpd = max(now()->diffInDays($p->due_date, false), 0);

                return [
                    'title' => 'SAR ' . number_format($p->instalment_amount, 2) . ' - ' . ($order->user->business_name ?? $order->user->first_name),
                    'start' => $p->due_date->format('Y-m-d'),
                    'className' => $statusClass,
                    'extendedProps' => [
                        'status' => ucfirst($p->payment_status),
                        'dpd' => $dpd,
                        'merchant' => $order->user->business_name ?? $order->user->first_name,
                        'orderId' => $order->id,
                        'amount' => $p->instalment_amount,
                        'channel' => $order->payment_type ?? 'N/A'
                    ]
                ];
            });
        })->flatten(1)->values();

        // Return JSON if AJAX
        if ($request->ajax()) {
            return response()->json($calendarEvents);
        }

        return view('admin.collections.installment-calander');
    }


    public function installmentDetails($id)
    {
        $user = Auth::user();
        $order = Order::with([
            'user',
            'seller',
            'pickupPoint',
            'assigned',
            'refund',
            'transactions'
        ])
            ->when($user->user_type !== 'admin', fn($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($id);

        $productDetails = $this->mapProductDetails($order);
        $refundRequest = RefundRequest::where('order_id', $id)->first();
        $subTotal = $productDetails->sum('total');
        $totalQuantity = $productDetails->sum('quantity');
        $totalShippingFee = $order->shipping_cost;

        $schedulePayments = SchedulePayment::where('seller_id', $order->seller_id)
            ->where('order_id', $order->id)
            ->orderBy('instalment_number', 'asc')
            ->get();

        return view('admin.collections.installment-details', compact(
            'order',
            'productDetails',
            'refundRequest',
            'subTotal',
            'totalQuantity',
            'totalShippingFee',
            'schedulePayments'
        ));
    }

    /**
     * Dunning page.
     */
    public function dunning()
    {
        return view('admin.collections.dunning');
    }

    /**
     * Promise to pay page.
     */
    public function promisetopay()
    {
        $query = Promise::with(['user', 'schedulePayment']);

        // Filter by schedule payment status
        if ($status = request('status')) {
            $query->whereHas('schedulePayment', function ($q) use ($status) {
                $q->where('payment_status', $status);
            });
        }

        // Filter by method
        if ($method = request('method')) {
            $query->where('method', $method);
        }

        // Search by user name
        if ($search = request('search')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%$search%"));
        }

        $items = $query->latest()->paginate(10);

        return view('admin.collections.promisetopay', compact('items'));
    }

    public function getUnpaidInstallments(User $user)
    {
        $unpaidPayments = SchedulePayment::where('user_id', $user->id)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('due_date', 'asc')
            ->get(['id', 'instalment_amount', 'due_date']);

        if ($unpaidPayments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No unpaid installments found for this client.'
            ]);
        }

        $formatted = $unpaidPayments->map(fn($p) => [
            'id' => $p->id,
            'instalment_amount' => $p->instalment_amount,
            'due_date' => $p->due_date->format('Y-m-d')
        ]);

        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }

    /**
     * Allocations page.
     */
    public function allocations(Request $request)
    {
        $query = $request->get('q');

        $schedulePayments = SchedulePayment::with(['order.customer'])
            ->orderBy('order_id')
            ->paginate(10);

        // Filter if search term exists
        if ($query) {
            $filtered = $this->filterByTracking($schedulePayments->getCollection(), $query);
            $schedulePayments->setCollection($filtered);
        }

        $groupedPayments = $schedulePayments->getCollection()->groupBy('order_id');

        if ($request->ajax()) {
            $html = view('admin.collections.partials.allocations_table', compact('groupedPayments'))->render();
            return response()->json(['html' => $html]);
        }

        return view('admin.collections.allocations', compact('groupedPayments', 'schedulePayments'));
    }

    /**
     * Filter payments only by order tracking number
     */
    private function filterByTracking($payments, $search)
    {
        $search = strtolower(trim($search));

        return $payments->filter(function ($payment) use ($search) {
            $order = $payment->order;

            return str_contains(strtolower($order->tracking ?? ''), $search);
        });
    }

    /**
     * Penalties & Fees page.
     */
    public function penalties()
    {
        return view('admin.collections.penalties');
    }

    /** Map product details */
    private function mapProductDetails(Order $order)
    {
        return collect(json_decode($order->product_details, true))
            ->map(fn($item) => $this->mapSingleProduct($item))
            ->filter();
    }

    private function mapSingleProduct(array $item)
    {
        $product = Product::find($item['product_id']);
        if (!$product) return null;
        $price = data_get($item, 'attributes.0.price', $product->unit_price);
        return [
            'product' => $product,
            'quantity' => $item['quantity'],
            'price' => $price,
            'attributes' => $item['attributes'] ?? [],
            'total' => $price * $item['quantity'],
        ];
    }

    public function partialPayments(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        // Base query (prioritize pending)
        $query = PartialPayment::with(['user', 'employee', 'schedulePayment.order'])
            ->orderByRaw("CASE WHEN approval_status = 'pending' THEN 0 ELSE 1 END")
            ->latest();

        // Apply status filter if present
        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('approval_status', $status);
        }

        // Get collection (we will apply the custom search filter on collection level)
        $paymentsCollection = $query->get();

        // If search provided, filter using the custom function
        if (!empty($search)) {
            $paymentsCollection = $this->filterMerchants($paymentsCollection, $search);
        }

        // Manual pagination (preserve query string)
        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $paymentsCollection->count();

        // Slice items for current page
        $results = $paymentsCollection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        // Preserve query params for pagination links
        $paginated->appends($request->only(['search', 'status']));

        return view('admin.collections.partial-payments', [
            'partialPayments' => $paginated,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Use your provided filtering logic (adapted to PartialPayment collection).
     * Keeps the same name you gave: filterMerchants
     *
     * @param \Illuminate\Support\Collection $payments
     * @param string $search
     * @return \Illuminate\Support\Collection
     */
    private function filterMerchants($payments, $search)
    {
        $search = strtolower(trim($search));
        $searchTerms = explode(' ', $search);

        return $payments->filter(function ($payment) use ($search, $searchTerms) {
            $user = $payment->user;

            if (! $user) {
                return false;
            }

            // Check full email and business_name
            if (
                str_contains(strtolower($user->email ?? ''), $search) ||
                str_contains(strtolower($user->business_name ?? ''), $search)
            ) {
                return true;
            }

            // Check first_name and last_name for each search term
            foreach ($searchTerms as $term) {
                if (
                    str_contains(strtolower($user->first_name ?? ''), $term) ||
                    str_contains(strtolower($user->last_name ?? ''), $term)
                ) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    public function updatePartialPaymentStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:partial_payments,id',
            'action' => 'required|in:approve,reject',
        ]);

        try {
            $payment = PartialPayment::findOrFail($request->id);

            if ($payment->approval_status !== 'pending') {
                return response()->json(['status' => 'error', 'message' => 'This payment is already processed.']);
            }

            $payment->approval_status = $request->action === 'approve' ? 'approved' : 'rejected';
            $payment->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Partial payment ' . $payment->approval_status . ' successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}
