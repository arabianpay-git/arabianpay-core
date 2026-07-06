<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\SupplierPayoutResource;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\SupplierPayout;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,processing,ready_to_payout,paid,canceled'],
        ]);

        $start = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->startOfMonth();
        $end = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfMonth();

        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = SupplierPayout::with(['supplier', 'creator', 'order'])
            ->whereBetween('created_at', [$start, $end])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SupplierPayoutResource::collection($payouts),
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::with('seller')->findOrFail($validated['order_id']);
        $merchant = Merchant::where('user_id', $order->seller_id)->first();

        if (! $merchant) {
            return response()->json([
                'success' => false,
                'message' => 'No merchant found for the order seller',
            ], 422);
        }

        $amount = $validated['amount'] ?? $this->computeDefaultPayoutAmount($order);
        if (! $amount || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to compute a positive payout amount. Provide an amount manually.',
            ], 422);
        }

        try {
            $payout = DB::transaction(function () use ($order, $merchant, $amount, $validated) {
                $currentUser = Auth::user();

                $payout = SupplierPayout::create([
                    'uuid' => (string) Str::uuid(),
                    'supplier_id' => $merchant->id,
                    'order_id' => $order->id,
                    'amount' => $amount,
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $currentUser->id,
                ]);

                $order->general_status = $order->grand_total - $order->commission_amount == $amount
                    ? 'completed'
                    : 'partially_paid';
                $order->save();

                $fTransaction = FTransaction::create([
                    'uuid' => (string) Str::uuid(),
                    'checkout_id' => $order->checkout_id,
                    'supplier_id' => $merchant->id,
                    'user_id' => $currentUser->id,
                    'transaction_type' => 'order_placement',
                    'amount' => $payout->amount,
                    'status' => 'Waiting approval',
                    'transaction_date' => now(),
                    'notes' => 'Payout for order #'.$order->id.', seller: '.($order->seller?->name ?? ''),
                ]);

                $supplierAccount = FAccounts::find(2400);
                FEntry::create([
                    'transaction_id' => $fTransaction->id,
                    'user_id' => $currentUser->id,
                    'supplier_id' => $merchant->id,
                    'order_id' => $order->id,
                    'account_id' => $supplierAccount->id,
                    'account_name' => $supplierAccount->account_name,
                    'debit' => $payout->amount,
                    'credit' => 0,
                    'status' => 'Waiting approval',
                    'entry_date' => now(),
                    'notes' => 'Payout for order #'.$order->id,
                ]);

                $bankAccount = FAccounts::find(1201);
                FEntry::create([
                    'transaction_id' => $fTransaction->id,
                    'user_id' => $currentUser->id,
                    'supplier_id' => $merchant->id,
                    'order_id' => $order->id,
                    'account_id' => $bankAccount->id,
                    'account_name' => $bankAccount->account_name,
                    'debit' => 0,
                    'credit' => $payout->amount,
                    'status' => 'Waiting approval',
                    'entry_date' => now(),
                    'notes' => 'Payout for order #'.$order->id,
                ]);

                return $payout;
            });

            $payout->load(['supplier', 'creator', 'order']);

            return response()->json([
                'success' => true,
                'data' => new SupplierPayoutResource($payout),
                'message' => 'Payout created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payout: '.$e->getMessage(),
            ], 500);
        }
    }

    public function complete(SupplierPayout $payout): JsonResponse
    {
        $payout->update([
            'status' => 'completed',
            'payout_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => new SupplierPayoutResource($payout->fresh()->load(['supplier', 'creator'])),
            'message' => 'Payout marked as completed',
        ]);
    }

    public function fail(Request $request, SupplierPayout $payout): JsonResponse
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:2000']);

        $payout->update([
            'status' => 'failed',
            'notes' => $validated['notes'] ?? $payout->notes,
        ]);

        return response()->json([
            'success' => true,
            'data' => new SupplierPayoutResource($payout->fresh()->load(['supplier', 'creator'])),
            'message' => 'Payout marked as failed',
        ]);
    }

    public function statusByOrder(Order $order): JsonResponse
    {
        $payout = SupplierPayout::where('order_id', $order->id)->latest()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'has_payout' => (bool) $payout,
                'status' => $payout?->status,
                'payout_id' => $payout?->id,
            ],
        ]);
    }

    private function computeDefaultPayoutAmount(Order $order): ?float
    {
        $amount = max(0, (float) ($order->grand_total ?? 0) - (float) ($order->commission_amount ?? 0));

        return $amount > 0 ? round($amount, 2) : null;
    }
}
