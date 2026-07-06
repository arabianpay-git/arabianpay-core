<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\SettlementResource;
use App\Models\Order;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Finance\BatchPayoutReport;
use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettlementController extends Controller
{
    public function __construct(
        protected SettlementService $settlementService,
        protected BatchPayoutReport $payoutReport
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Settlement::with(['supplier', 'creator', 'approver', 'payer']);

        $currentPeriod = $this->settlementService->getCurrentSettlementPeriod();
        $finishedPeriod = $this->settlementService->getPreviousSettlementPeriod();

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

        $perPage = min((int) $request->get('per_page', 20), 100);
        $settlements = $query->withCount('orders')->latest('settlement_date')->paginate($perPage);

        $currentOrders = Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$currentPeriod['start_date'], $currentPeriod['end_date']])
            ->whereNull('settlement_id')
            ->get();

        $finishedOrders = Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$finishedPeriod['start_date'], $finishedPeriod['end_date']])
            ->whereNotNull('settlement_id')
            ->get();

        $finishedOrdersUnsettled = Order::where('delivery_status', 'delivered')
            ->whereBetween('delivered_at', [$finishedPeriod['start_date'], $finishedPeriod['end_date']])
            ->whereNull('settlement_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'settlements' => SettlementResource::collection($settlements),
                'periods' => [
                    'current' => [
                        'start_date' => $currentPeriod['start_date']->toDateString(),
                        'end_date' => $currentPeriod['end_date']->toDateString(),
                        'settlement_date' => $currentPeriod['settlement_date']->toDateString(),
                        'total_amount' => $currentOrders->sum(fn ($o) => $o->grand_total - ($o->commission_amount ?? 0)),
                        'orders_count' => $currentOrders->count(),
                    ],
                    'finished' => [
                        'start_date' => $finishedPeriod['start_date']->toDateString(),
                        'end_date' => $finishedPeriod['end_date']->toDateString(),
                        'settlement_date' => $finishedPeriod['settlement_date']->toDateString(),
                        'total_amount' => $finishedOrders->sum(fn ($o) => $o->grand_total - ($o->commission_amount ?? 0)),
                        'orders_count' => $finishedOrders->count(),
                        'unsettled_amount' => $finishedOrdersUnsettled->sum(fn ($o) => $o->grand_total - ($o->commission_amount ?? 0)),
                        'unsettled_orders_count' => $finishedOrdersUnsettled->count(),
                    ],
                ],
                'merchants' => User::where('user_type', 'supplier')->get(['id', 'name', 'business_name']),
            ],
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $period = $this->settlementService->getPreviousSettlementPeriod();

        $startDate = Carbon::parse($period['start_date']);
        $endDate = Carbon::parse($period['end_date']);

        $created = $this->settlementService->generateSettlementsForPeriod($startDate, $endDate, Auth::user());

        return response()->json([
            'success' => true,
            'message' => "Generated {$created->count()} settlements for period {$startDate->toDateString()} to {$endDate->toDateString()}",
            'data' => [
                'count' => $created->count(),
                'settlements' => SettlementResource::collection($created),
            ],
        ]);
    }

    public function show(Settlement $settlement): JsonResponse
    {
        $settlement->load(['orders', 'payouts', 'supplier.supplierBanks', 'approver', 'payer', 'creator']);
        $settlement->loadCount('orders');

        return response()->json([
            'success' => true,
            'data' => new SettlementResource($settlement),
        ]);
    }

    public function approve(Settlement $settlement): JsonResponse
    {
        try {
            $this->settlementService->approveSettlement($settlement, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Settlement approved successfully',
                'data' => new SettlementResource($settlement->fresh()->load(['supplier', 'creator', 'approver', 'payer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function pay(Settlement $settlement): JsonResponse
    {
        try {
            $this->settlementService->markSettlementAsPaid($settlement, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Settlement marked as paid successfully',
                'data' => new SettlementResource($settlement->fresh()->load(['supplier', 'creator', 'approver', 'payer', 'payouts'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Request $request, Settlement $settlement): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        try {
            $this->settlementService->cancelSettlement($settlement, $validated['reason'], Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Settlement cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function batchApprove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $result = $this->settlementService->batchApprove($validated['settlement_ids'], Auth::user());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? "Successfully approved {$result['processed']} settlements"
                : $result['error'],
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    public function batchCancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
            'reason' => 'required|string|max:1000',
        ]);

        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($validated['settlement_ids'] as $id) {
            try {
                $settlement = Settlement::findOrFail($id);
                $this->settlementService->cancelSettlement($settlement, $validated['reason'], Auth::user());
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = ['settlement_id' => $id, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cancelled {$processed} settlements".($failed > 0 ? " ({$failed} failed)" : ''),
            'data' => compact('processed', 'failed', 'errors'),
        ]);
    }

    public function batchPayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $result = $this->settlementService->processBatchPayout($validated['settlement_ids'], Auth::user());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? "Successfully processed {$result['processed']} payouts"
                : $result['error'],
            'data' => $result,
        ], $result['success'] ? 200 : 400);
    }

    public function report(Request $request)
    {
        $validated = $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $settlements = Settlement::with('supplier')->whereIn('id', $validated['settlement_ids'])->get();

        if ($settlements->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No settlements found'], 404);
        }

        $filePath = $this->payoutReport->generatePayoutReport($settlements);

        return response()->json([
            'success' => true,
            'message' => 'Report generated',
            'data' => ['file_path' => $filePath],
        ]);
    }

    public function bankTransferFile(Request $request)
    {
        $validated = $request->validate([
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ]);

        $settlements = Settlement::with(['supplier.supplierBanks'])->whereIn('id', $validated['settlement_ids'])->get();

        if ($settlements->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No settlements found'], 404);
        }

        return response()->streamDownload(function () use ($settlements) {
            echo "Beneficiary Name,Account Number,Bank Name,Account Name,Amount,Reference,Settlement Number\n";

            foreach ($settlements as $settlement) {
                $supplier = $settlement->supplier;
                $bank = $supplier->supplierBanks->first();

                $fp = fopen('php://output', 'w');
                fputcsv($fp, [
                    $supplier->business_name ?? 'N/A',
                    $bank?->iban ?? 'N/A',
                    $bank?->bank_name ?? 'N/A',
                    $bank?->account_name ?? 'N/A',
                    $settlement->payable_amount,
                    'Weekly Payout',
                    $settlement->settlement_number,
                ]);
                fclose($fp);
            }
        }, 'transfer_'.now()->format('YmdHis').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
