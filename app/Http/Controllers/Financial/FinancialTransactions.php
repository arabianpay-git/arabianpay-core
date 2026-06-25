<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FAccounts;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinancialTransactions extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = FTransaction::with(['user', 'customer', 'supplier', 'order', 'payment'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);

        return view('admin.financial.transactions.index', compact('transactions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accounts = FAccounts::where('status', 'active')->orderBy('account_code')->get();
        $users = User::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $suppliers = Merchant::orderBy('name')->get();
        $orders = Order::orderBy('id', 'desc')->limit(100)->get();
        $payments = Payment::orderBy('id', 'desc')->limit(100)->get();

        return view('admin.financial.transactions.create', compact('accounts', 'users', 'customers', 'suppliers', 'orders', 'payments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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

        // Generate UUID
        $validated['uuid'] = Str::uuid()->toString();

        FTransaction::create($validated);

        return redirect()->route('financial.transactions.index')
            ->with('success', 'Financial transaction created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $fTransaction = FTransaction::with(['user', 'customer', 'supplier', 'order', 'payment'])
            ->findOrFail($id);

        // Get related entries for this transaction
        $entries = $fTransaction->entries()->with(['account', 'user'])
            ->orderBy('entry_date', 'desc')
            ->paginate(50);

        return view('admin.financial.transactions.show', compact('fTransaction', 'entries'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $fTransaction = FTransaction::findOrFail($id);
        $accounts = FAccounts::where('status', 'active')->orderBy('account_code')->get();
        $users = User::orderBy('first_name')->get();
        $customers = Customer::orderBy('name')->get();
        $suppliers = Merchant::orderBy('name')->get();
        $orders = Order::orderBy('id', 'desc')->limit(100)->get();
        $payments = Payment::orderBy('id', 'desc')->limit(100)->get();

        return view('admin.financial.transactions.edit', compact('fTransaction', 'accounts', 'users', 'customers', 'suppliers', 'orders', 'payments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $fTransaction = FTransaction::findOrFail($id);

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

        $fTransaction->update($validated);

        return redirect()->route('financial.transactions.index')
            ->with('success', 'Financial transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $fTransaction = FTransaction::findOrFail($id);

        // Check if transaction has entries before deletion
        if ($fTransaction->entries()->count() > 0) {
            return redirect()->route('financial.transactions.index')
                ->with('error', 'Cannot delete transaction that has entries. Please remove all entries first.');
        }

        $fTransaction->delete();

        return redirect()->route('financial.transactions.index')
            ->with('success', 'Financial transaction deleted successfully.');
    }

    /**
     * Get transaction data for modal display
     */
    public function getModalData($id)
    {
        $fTransaction = FTransaction::with(['user', 'customer', 'supplier', 'order', 'payment'])
            ->findOrFail($id);

        $entries = $fTransaction->entries()->with(['account', 'user'])->get();

        return response()->json([
            'success' => true,
            'transaction' => $fTransaction,
            'entries' => $entries,
        ]);
    }
}
