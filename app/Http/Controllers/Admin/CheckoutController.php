<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Display a listing of checkouts.
     */
    public function index(Request $request)
    {
        $query = Checkout::with([
            'user',
            'investmentPool',
            'schedulePayments',
            'orders',
        ]);

        // Filter by customer
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by investment pool
        if ($request->filled('pool_id')) {
            $query->where('pool_id', $request->pool_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            switch ($request->payment_status) {
                case 'paid':
                    $query->whereHas('schedulePayments', function ($q) {
                        $q->whereHas('payment');
                    });
                    break;
                case 'pending':
                    $query->whereHas('schedulePayments', function ($q) {
                        $q->whereDoesntHave('payment')
                            ->where('due_date', '>', Carbon::now());
                    });
                    break;
                case 'overdue':
                    $query->whereHas('schedulePayments', function ($q) {
                        $q->whereDoesntHave('payment')
                            ->where('due_date', '<', Carbon::now());
                    });
                    break;
            }
        }

        // Search by customer name or checkout UUID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $checkouts = $query->latest()->paginate(20);

        return view('admin.checkouts.index', compact('checkouts'));
    }

    /**
     * Show the form for creating a new checkout.
     */
    public function create()
    {
        // This might not be needed for admin panel, but keeping for completeness
        return view('admin.checkouts.create');
    }

    /**
     * Store a newly created checkout in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'device_id' => 'nullable|string',
            'pool_id' => 'required|exists:investment_pools,id',
        ]);

        $checkout = Checkout::create([
            'uuid' => Str::uuid(),
            'user_id' => $request->user_id,
            'device_id' => $request->device_id,
            'pool_id' => $request->pool_id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Checkout created successfully',
                'checkout' => $checkout->load(['user', 'investmentPool']),
            ]);
        }

        return redirect()->route('admin.checkouts.show', $checkout)
            ->with('success', 'Checkout created successfully');
    }

    /**
     * Display the specified checkout with all related data.
     */
    public function show(Checkout $checkout)
    {
        // Load all relationships with optimized queries
        $checkout->load([
            'user',
            'investmentPool',
            'orders.seller',
            'schedulePayments',
            'schedulePayments.payment',
            'schedulePayments.claims',

        ]);

        // Calculate checkout metrics
        $metrics = $this->calculateCheckoutMetrics($checkout);
        // Load financial journal entries (if any) tied to this checkout via FTransaction
        $fTransaction = \App\Models\FTransaction::where('checkout_id', $checkout->id)->first();
        if ($fTransaction) {
            $entries = $fTransaction->entries()
                ->with(['account', 'user'])
                ->orderBy('entry_date', 'desc')
                ->paginate(50);
        } else {
            $entries = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        return view('admin.checkouts.show', compact('checkout', 'metrics', 'entries'));
    }

    /**
     * Show the form for editing the specified checkout.
     */
    public function edit(Checkout $checkout)
    {
        $checkout->load(['user', 'investmentPool']);

        return view('admin.checkouts.edit', compact('checkout'));
    }

    /**
     * Update the specified checkout in storage.
     */
    public function update(Request $request, Checkout $checkout)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'device_id' => 'nullable|string',
            'pool_id' => 'required|exists:investment_pools,id',
        ]);

        $checkout->update($request->only(['user_id', 'device_id', 'pool_id']));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Checkout updated successfully',
                'checkout' => $checkout->load(['user', 'investmentPool']),
            ]);
        }

        return redirect()->route('admin.checkouts.show', $checkout)
            ->with('success', 'Checkout updated successfully');
    }

    /**
     * Remove the specified checkout from storage.
     */
    public function destroy(Checkout $checkout)
    {
        $checkout->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checkout deleted successfully',
        ]);
    }

    /**
     * Get checkout summary data for AJAX requests
     */
    public function summary(Checkout $checkout): JsonResponse
    {
        $checkout->load([
            'schedulePayments.payment',
            'schedulePayments.claims',
        ]);

        $metrics = $this->calculateCheckoutMetrics($checkout);

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Get checkout payment timeline
     */
    public function timeline(Checkout $checkout): JsonResponse
    {
        $schedulePayments = $checkout->schedulePayments()
            ->with(['payment', 'claims'])
            ->orderBy('due_date')
            ->get();

        $timeline = $schedulePayments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'due_date' => Carbon::parse($payment->due_date)->format(dateFormat()),
                'amount' => $payment->amount,
                'status' => $payment->payment ? 'paid' : (Carbon::parse($payment->due_date)->lt(Carbon::now()) ? 'overdue' : 'pending'),
                'payment_date' => $payment->payment?->created_at?->format(dateFormat()),
                'claims_count' => $payment->claims->count(),
                'last_claim' => $payment->claims->first()?->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
        ]);
    }

    /**
     * Calculate comprehensive checkout metrics
     */
    private function calculateCheckoutMetrics(Checkout $checkout): array
    {
        $schedulePayments = $checkout->schedulePayments;

        $totalAmount = $schedulePayments->sum('amount');
        $paidAmount = $schedulePayments->whereNotNull('payment')->sum('amount');
        $pendingAmount = $totalAmount - $paidAmount;

        $overduePayments = $schedulePayments->filter(function ($payment) {
            return ! $payment->payment && Carbon::parse($payment->due_date)->lt(Carbon::now());
        });

        $overdueAmount = $overduePayments->sum('amount');

        $totalClaims = $schedulePayments->sum(function ($payment) {
            return $payment->claims->count();
        });

        $resolvedClaims = $schedulePayments->sum(function ($payment) {
            return $payment->claims->where('claim_status', 'resolved')->count();
        });

        return [
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'overdue_amount' => $overdueAmount,
            'payment_completion_rate' => $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0,
            'overdue_rate' => $totalAmount > 0 ? ($overdueAmount / $totalAmount) * 100 : 0,
            'total_payments' => $schedulePayments->count(),
            'paid_payments' => $schedulePayments->whereNotNull('payment')->count(),
            'overdue_payments' => $overduePayments->count(),
            'total_claims' => $totalClaims,
            'resolved_claims' => $resolvedClaims,
            'claim_resolution_rate' => $totalClaims > 0 ? ($resolvedClaims / $totalClaims) * 100 : 0,
            'customer_payment_history' => [
                'on_time_payments' => $schedulePayments->filter(function ($payment) {
                    return $payment->payment && $payment->payment->created_at->lte(Carbon::parse($payment->due_date));
                })->count(),
                'late_payments' => $schedulePayments->filter(function ($payment) {
                    return $payment->payment && $payment->payment->created_at->gt(Carbon::parse($payment->due_date));
                })->count(),
            ],
        ];
    }
}
