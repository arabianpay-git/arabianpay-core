<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\TransactionResource;
use App\Http\Resources\Finance\WalletResource;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoanTransactionController extends Controller
{
    protected const VALID_STATUSES = ['pending', 'due', 'late', 'paid', 'failed'];

    public function index(): JsonResponse
    {
        $query = Transaction::with(['user', 'seller', 'order']);

        if (! Auth::user()->hasRole('admin')) {
            $query->where('assigned_to', Auth::id());
        }

        $perPage = min((int) request('per_page', 15), 100);
        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function byStatus($status): JsonResponse
    {
        if (! in_array($status, self::VALID_STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 422);
        }

        $query = Transaction::with(['user', 'seller', 'order'])
            ->where('payment_status', $status);

        if (! Auth::user()->hasRole('admin')) {
            $query->where('assigned_to', Auth::id());
        }

        $perPage = min((int) request('per_page', 15), 100);
        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function wallet(): JsonResponse
    {
        $wallets = Wallet::with(['user', 'order'])
            ->where('seller_id', Auth::id())
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => WalletResource::collection($wallets),
            'meta' => [
                'current_page' => $wallets->currentPage(),
                'last_page' => $wallets->lastPage(),
                'per_page' => $wallets->perPage(),
                'total' => $wallets->total(),
            ],
        ]);
    }

    public function invoice($order)
    {
        $orderModel = \App\Models\Order::findOrFail($order);
        $transaction = Transaction::where('order_id', $order)->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'transaction' => $transaction->load(['user', 'seller', 'schedulePayments', 'wallet']),
        ]);

        return $pdf->stream("invoice-{$order}.pdf");
    }
}
