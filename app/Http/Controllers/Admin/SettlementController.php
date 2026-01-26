<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Finance\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettlementController extends Controller
{
    protected $settlementService;

    public function __construct(SettlementService $settlementService)
    {
        $this->settlementService = $settlementService;
    }

    /**
     * Display a listing of settlements.
     */
    public function index(Request $request)
    {
        $query = Settlement::with(['supplier', 'creator', 'approver', 'payer']);

        // Get current period data
        $currentPeriod = $this->settlementService->getCurrentSettlementPeriod();
        $currentOrders = \App\Models\Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$currentPeriod['start_date'], $currentPeriod['end_date']])
            ->whereNull('settlement_id')
            ->get();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_user_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->date_to));
        }

        // Add order count
        $settlements = $query->withCount('orders')->latest('settlement_date')->paginate(20)->withQueryString();

        // Get unsettled orders (legacy)
        $unsettledOrders = $this->settlementService->getAllEligibleUnsettledOrders();

        // Get finished period data
        $finishedPeriod = $this->settlementService->getPreviousSettlementPeriod();
        $finishedOrders = \App\Models\Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$finishedPeriod['start_date'], $finishedPeriod['end_date']])
            ->where('settlement_id', '!=', null)
            ->get();
        $finishedOrdersUnsettled = \App\Models\Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$finishedPeriod['start_date'], $finishedPeriod['end_date']])
            ->whereNull('settlement_id')
            ->get();

        $finishedPeriod['total_amount'] = $finishedOrders->sum(function ($order) {
            return $order->grand_total - ($order->commission_amount ?? 0);
        });
        $finishedPeriod['orders_count'] = $finishedOrders->count();

        $finishedPeriod['unsettled_amount'] = $finishedOrdersUnsettled->sum(function ($order) {
            return $order->grand_total - ($order->commission_amount ?? 0);
        });
        $finishedPeriod['unsettled_orders_count'] = $finishedOrdersUnsettled->count();

        $currentPeriod['total_amount'] = $currentOrders->sum(function ($order) {
            return $order->grand_total - ($order->commission_amount ?? 0);
        });
        $currentPeriod['orders_count'] = $currentOrders->count();
        $currentPeriod['next_settlement'] = $currentPeriod['end_date']->copy()->next(Carbon::TUESDAY);

        $merchants = User::where('user_type', 'merchant')->get();

        return view('admin.settlements.index', compact(
            'settlements',
            'unsettledOrders',
            'finishedPeriod',
            'finishedOrders',
            'finishedOrdersUnsettled',
            'currentPeriod',
            'merchants'
        ));
    }

    /**
     * Store a newly created settlement (Manual creation / Auto-generation trigger).
     */
    public function store(Request $request)
    {
        // ... Implementation for manual creation if needed
        // For now, simpler to use the generate route
        return redirect()->route('settlements.index');
    }

    /**
     * Display the specified settlement.
     */
    public function show(Settlement $settlement)
    {
        $settlement->load(['orders', 'payouts', 'supplier.supplierBanks', 'approver', 'payer', 'creator']);
        $settlement->loadCount('orders');

        if (request()->wantsJson()) {
            return response()->json($settlement);
        }

        return view('admin.settlements.show', compact('settlement'));
    }

    /**
     * Generate settlements for finished period.
     */
    public function generateForFinishedPeriod(Request $request)
    {
        // Default to finished period logic from service
        $period = $this->settlementService->getPreviousSettlementPeriod();

        // Allow override via request for testing/backdating
        $startDate = Carbon::parse($period['start_date']);
        $endDate = Carbon::parse($period['end_date']);

        $created = $this->settlementService->generateSettlementsForPeriod($startDate, $endDate, Auth::user());

        return redirect()->route('settlements.index')
            ->with('success', "Generated {$created->count()} settlements for period {$startDate->toDateString()} to {$endDate->toDateString()}");
    }

    /**
     * Approve a settlement.
     */
    public function approve(Settlement $settlement)
    {
        try {
            $this->settlementService->approveSettlement($settlement, Auth::user());
            return back()->with('success', 'Settlement approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Batch approve settlements.
     */
    public function batchApprove(Request $request)
    {
        $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $result = $this->settlementService->batchApprove($request->settlement_ids, Auth::user());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$result['processed']} settlements.",
                'data' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400); // 400 Bad Request
        }
    }

    /**
     * Mark settlement as paid (Individual).
     */
    public function markAsPaid(Settlement $settlement)
    {
        try {
            $this->settlementService->markSettlementAsPaid($settlement, Auth::user());
            return back()->with('success', 'Settlement marked as paid successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a settlement.
     */
    public function cancel(Request $request, Settlement $settlement)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $this->settlementService->cancelSettlement($settlement, $request->reason, Auth::user());
            return back()->with('success', 'Settlement cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Batch cancel settlements.
     */
    public function batchCancel(Request $request)
    {
        $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
            'reason' => 'required|string|max:255'
        ]);

        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($request->settlement_ids as $id) {
            try {
                $settlement = Settlement::findOrFail($id);
                if ($settlement->status === 'paid') {
                    throw new \Exception("Cannot cancel paid settlement");
                }
                $this->settlementService->cancelSettlement($settlement, $request->reason, Auth::user());
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['settlement_id' => $id, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cancelled {$processed} settlements" . ($failed > 0 ? " ({$failed} failed)" : ""),
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * Process batch payout.
     */
    public function batchPayout(Request $request)
    {
        $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $result = $this->settlementService->processBatchPayout($request->settlement_ids, Auth::user());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => "Successfully processed {$result['processed']} settlements.",
                'data' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400); // 400 Bad Request
        }
    }
    /**
     * Generate PDF/Summary report for settlements.
     */
    public function generateReport(Request $request, \App\Services\Finance\BatchPayoutReport $reportService)
    {
        $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $settlements = Settlement::with('supplier')->whereIn('id', $request->settlement_ids)->get();
        if ($settlements->isEmpty()) {
            return back()->with('error', 'No settlements found selected.');
        }

        $filePath = $reportService->generatePayoutReport($settlements);

        return \Illuminate\Support\Facades\Storage::download($filePath);
    }

    /**
     * Generate Bank Transfer File (CSV/Excel).
     */
    public function generateBankTransferFile(Request $request, \App\Services\Finance\BatchPayoutReport $reportService)
    {
        $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $settlements = Settlement::with(['supplier.supplierBanks'])->whereIn('id', $request->settlement_ids)->get();
        if ($settlements->isEmpty()) {
            return back()->with('error', 'No settlements found selected.');
        }

        $filePath = $reportService->generateBankTransferFile($settlements);

        return \Illuminate\Support\Facades\Storage::download($filePath);
    }
}
