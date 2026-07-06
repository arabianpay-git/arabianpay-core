<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\FTransactionResource;
use App\Models\Customer;
use App\Models\FAccounts;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FTransaction::with(['user', 'customer', 'supplier', 'order', 'payment'])
            ->orderBy('transaction_date', 'desc');

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => FTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_id' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:merchants,id',
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'payment_id' => 'nullable|exists:payments,id',
            'transaction_type' => 'required|string|in:payment,refund,withdrawal,deposit,transfer',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,completed,failed,cancelled',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $validated['uuid'] = Str::uuid()->toString();

        $transaction = FTransaction::create($validated);
        $transaction->load(['user', 'customer', 'supplier', 'order', 'payment']);

        return response()->json([
            'success' => true,
            'data' => new FTransactionResource($transaction),
            'message' => 'Transaction created successfully',
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $transaction = FTransaction::with(['user', 'customer', 'supplier', 'order', 'payment', 'entries.account', 'entries.user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new FTransactionResource($transaction),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $transaction = FTransaction::findOrFail($id);

        $validated = $request->validate([
            'reference_id' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:merchants,id',
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
            'payment_id' => 'nullable|exists:payments,id',
            'transaction_type' => 'required|string|in:payment,refund,withdrawal,deposit,transfer',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,completed,failed,cancelled',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);
        $transaction->load(['user', 'customer', 'supplier', 'order', 'payment']);

        return response()->json([
            'success' => true,
            'data' => new FTransactionResource($transaction),
            'message' => 'Transaction updated successfully',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $transaction = FTransaction::findOrFail($id);

        if ($transaction->entries()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete transaction that has entries. Please remove all entries first.',
            ], 409);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }

    public function createFormData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => FAccounts::where('status', 'active')->orderBy('id')->get(['id', 'account_name']),
                'users' => User::orderBy('name')->get(['id', 'name']),
                'customers' => Customer::orderBy('name')->get(['id', 'name']),
                'suppliers' => Merchant::orderBy('name')->get(['id', 'name']),
                'orders' => Order::orderBy('id', 'desc')->limit(100)->get(['id', 'grand_total']),
                'payments' => Payment::orderBy('id', 'desc')->limit(100)->get(['id', 'amount']),
                'transaction_types' => ['payment', 'refund', 'withdrawal', 'deposit', 'transfer'],
                'statuses' => ['pending', 'completed', 'failed', 'cancelled'],
            ],
        ]);
    }
}
