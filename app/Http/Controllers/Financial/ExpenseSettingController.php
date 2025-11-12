<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\ExpenseSetting;
use App\Models\FAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseSettingController extends Controller
{
    /**
     * Display a listing of the expense settings.
     */
    public function index()
    {
       
            $expenseSettings = ExpenseSetting::with('creditAccount')->get();
            
           

        return view('admin.financial.expense-settings.index', compact('expenseSettings'));
    }

    /**
     * Show the form for creating a new expense setting.
     */
    public function create()
    {
        $creditAccounts = FAccounts::orderBy('account_name')->where('account_type1', 1)->where('account_type2', 2)->get();
        return view('admin.financial.expense-settings.create', compact('creditAccounts'));
    }

    /**
     * Store a newly created expense setting in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'refrence_id' => 'required|string|max:255|unique:expense_setting,refrence_id',
            'description' => 'required|string|max:255',
            'amount_type' => 'required|in:fixed,percent',
            'amount' => 'required|numeric|min:0',
            'credit_acc_id' => 'required|exists:f_accounts,id',
        ]);

        try {
            ExpenseSetting::create($validated);

            return redirect()->route('financial.expense-settings.index')
                ->with('success', translate('Expense setting created successfully'));
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', translate('Failed to create expense setting: ') . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified expense setting.
     */
    public function edit(ExpenseSetting $expenseSetting)
    {
        $creditAccounts = FAccounts::orderBy('account_name')->where('account_type1', 1)->where('account_type2', 2)->get();
        return view('admin.financial.expense-settings.edit', compact('expenseSetting', 'creditAccounts'));
    }

    /**
     * Update the specified expense setting in storage.
     */
    public function update(Request $request, ExpenseSetting $expenseSetting)
    {
        $validated = $request->validate([
            'refrence_id' => 'required|string|max:255|unique:expense_setting,refrence_id,' . $expenseSetting->id,
            'description' => 'required|string|max:255',
            'amount_type' => 'required|in:fixed,percent',
            'amount' => 'required|numeric|min:0',
            'credit_acc_id' => 'required|exists:f_accounts,id',
        ]);

        try {
            $expenseSetting->update($validated);

            return redirect()->route('financial.expense-settings.index')
                ->with('success', translate('Expense setting updated successfully'));
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', translate('Failed to update expense setting: ') . $e->getMessage());
        }
    }

    /**
     * Remove the specified expense setting from storage.
     */
    public function destroy(ExpenseSetting $expenseSetting)
    {
        try {
            $expenseSetting->delete();

            return response()->json([
                'success' => true,
                'message' => translate('Expense setting deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => translate('Failed to delete expense setting: ') . $e->getMessage()
            ], 500);
        }
    }
}
