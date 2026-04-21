<?php

// app/Services/ExpenseService.php

namespace App\Services;

use App\Models\ExpenseSetting;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Double;

class ExpenseService
{
    public function __construct(private AuditTrailService $auditTrail) {}

    // Create financial double entry transaction for an expense
    public function createExpenseTransaction($expenseReferenceId, User $user)
    {
        // Retrieve expense setting
        $expenseSetting = ExpenseSetting::where('refrence_id', $expenseReferenceId)->with('creditAccount')->first();
        if (! $expenseSetting) {
            return;
        }
        $amount = 0;
        // Determine amount
        $amount = $expenseSetting->amount;

        // Start DB transaction
        DB::beginTransaction();
        try {
            // Create transaction record
            $transaction = FTransaction::create([
                'date' => now(),
                'notes' => $expenseSetting->description,
                'amount' => $amount,
            ]);

            // Create credit entry (Payable Account)
            FEntry::create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'account_id' => $expenseSetting->creditAccount->id,
                'account_name' => $expenseSetting->creditAccount->name,
                'debit' => 0,
                'credit' => $amount,
                'entry_date' => now(),
                'notes' => $expenseSetting->description.' - '.$user->first_name.' '.$user->last_name,
            ]);

            // Create debit entry (Expense Account)
            $expensAccount = FAccounts::where('id', 5000)->first();
            FEntry::create([
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'account_id' => $expensAccount->id,
                'account_name' => $expensAccount->name,
                'debit' => $amount,
                'credit' => 0,
                'entry_date' => now(),
                'notes' => $expenseSetting->description.' - '.$user->first_name.' '.$user->last_name,
            ]);

            DB::commit();
            $this->auditTrail->logCreated($transaction, 'Expense transaction created');

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
