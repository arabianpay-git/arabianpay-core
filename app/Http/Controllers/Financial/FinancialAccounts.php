<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FAccounts;
use App\Models\FEntry;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinancialAccounts extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = FAccounts::orderBy('id')->paginate(15);

        return view('admin.financial.accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.financial.accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' =>'required|integer|unique:f_accounts,id',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'account_type1' => 'required|integer|in:1,2',
            'account_type2' => 'required|integer|in:1,2',
            'status' => 'required|string|in:active,inactive',
        ]);

        FAccounts::create($validated);

        return redirect()->route('financial.accounts.index')
            ->with('success', 'Financial account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $fAccount = FAccounts::findOrFail($id);
        
        $entries = $fAccount->entries()->with(['user', 'order', 'payment', 'customer', 'supplier', 'transaction'])
            ->orderBy('entry_date', 'desc')
            ->paginate(20);
        
        return view('admin.financial.accounts.show', compact('fAccount', 'entries'));
    }

    /**
     * Ledger view with running balance for a given account.
     * Default date range is the current month; opening balance is the cumulative
     * balance of all entries before the start date.
     */
    public function ledger(Request $request, $accountId)
    {
        $account = FAccounts::findOrFail($accountId);

        // Date range: default to current month
        $start = $request->filled('date_from')
            ? Carbon::parse($request->get('date_from'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $end = $request->filled('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : Carbon::now()->endOfMonth();

        // Get customers/suppliers for filter dropdowns
        $customers = [];
        $suppliers = [];
        
        if ($accountId == 1203) {
            // Accounts Receivable - get all customers
            $customers = \App\Models\Customer::with('user')->get()->map(function($customer) {
                return (object)[
                    'id' => $customer->id,
                    'name' => $customer->user->name ?? '',
                    'business_name' => $customer->user->business_name ?? $customer->user->name ?? ''
                ];
            });
        } elseif ($accountId == 2400) {
            // Accounts Payable - get suppliers/merchants
            $suppliers = \App\Models\Merchant::with('user')->get()->map(function($merchant) {
                return (object)[
                    'id' => $merchant->id,
                    'name' => $merchant->user->name ?? '',
                    'business_name' => $merchant->user->business_name ?? $merchant->user->name ?? ''
                ];
            });
        }

        // Opening balance: all entries before start
        $priorQuery = FEntry::where('account_id', $account->id)
            ->whereDate('entry_date', '<', $start->toDateString());
        
        // Apply customer/supplier filter to opening balance
        if ($request->filled('customer_id') && $accountId == 1203) {
            $priorQuery->where('customer_id', $request->get('customer_id'));
        } elseif ($request->filled('supplier_id') && $accountId == 2400) {
            $priorQuery->where('supplier_id', $request->get('supplier_id'));
        }
        
        $prior = $priorQuery->selectRaw('COALESCE(SUM(debit),0) as sum_debit, COALESCE(SUM(credit),0) as sum_credit')
            ->first();

        // Natural balance side: account_type2 (1=debit, 2=credit)
        $isDebitNormal = ((int)$account->account_type2) === 1;

        $openingBalance = $isDebitNormal
            ? (float)$prior->sum_debit - (float)$prior->sum_credit
            : (float)$prior->sum_credit - (float)$prior->sum_debit;

        // Period entries within range
        $entriesQuery = FEntry::with(['user'])
            ->where('account_id', $account->id)
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', $end->toDateString());
        
        // Apply customer/supplier filter to entries
        if ($request->filled('customer_id') && $accountId == 1203) {
            $entriesQuery->where('customer_id', $request->get('customer_id'));
        } elseif ($request->filled('supplier_id') && $accountId == 2400) {
            $entriesQuery->where('supplier_id', $request->get('supplier_id'));
        }
        
        $entriesQuery->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        // For display totals (period)
        $totalsQuery = FEntry::where('account_id', $account->id)
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', $end->toDateString());
        
        // Apply customer/supplier filter to totals
        if ($request->filled('customer_id') && $accountId == 1203) {
            $totalsQuery->where('customer_id', $request->get('customer_id'));
        } elseif ($request->filled('supplier_id') && $accountId == 2400) {
            $totalsQuery->where('supplier_id', $request->get('supplier_id'));
        }
        
        $totals = $totalsQuery->selectRaw('COALESCE(SUM(debit),0) as sum_debit, COALESCE(SUM(credit),0) as sum_credit')
            ->first();

        // We paginate but also need running balances; compute on the current page
        $perPage = (int)($request->get('per_page', 50));
        $entries = $entriesQuery->paginate($perPage)->withQueryString();

        // Compute running balance for the entries on this page based on opening balance plus
        // all entries BEFORE the first row of this page (to keep running accurate across pages)
        if ($entries->currentPage() > 1) {
            $beforeOffset = ($entries->currentPage() - 1) * $entries->perPage();
            $prePageQuery = FEntry::where('account_id', $account->id)
                ->whereDate('entry_date', '>=', $start->toDateString())
                ->whereDate('entry_date', '<=', $end->toDateString());
            
            // Apply customer/supplier filter to pre-page aggregate
            if ($request->filled('customer_id') && $accountId == 1203) {
                $prePageQuery->where('customer_id', $request->get('customer_id'));
            } elseif ($request->filled('supplier_id') && $accountId == 2400) {
                $prePageQuery->where('supplier_id', $request->get('supplier_id'));
            }
            
            $prePageAgg = $prePageQuery->orderBy('entry_date', 'asc')
                ->orderBy('id', 'asc')
                ->limit($beforeOffset)
                ->get(['debit', 'credit']);

            foreach ($prePageAgg as $row) {
                $delta = $isDebitNormal
                    ? (float)($row->debit ?? 0) - (float)($row->credit ?? 0)
                    : (float)($row->credit ?? 0) - (float)($row->debit ?? 0);
                $openingBalance += $delta;
            }
        }

        // Attach running_balance to each row (non-persistent attribute)
        $running = $openingBalance;
        $entries->getCollection()->transform(function ($row) use (&$running, $isDebitNormal) {
            $delta = $isDebitNormal
                ? (float)($row->debit ?? 0) - (float)($row->credit ?? 0)
                : (float)($row->credit ?? 0) - (float)($row->debit ?? 0);
            $running += $delta;
            $row->running_balance = $running;
            return $row;
        });

        $closingBalance = $running; // after last row on the current page

        return view('admin.financial.accounts.ledger', [
            'account' => $account,
            'entries' => $entries,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'totals' => $totals,
            'isDebitNormal' => $isDebitNormal,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'customers' => $customers,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FAccounts $fAccounts)
    {
        return view('admin.financial.accounts.edit', compact('fAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FAccounts $fAccounts)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:f_accounts,id',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'account_type1' => 'required|integer|in:1,2',
            'account_type2' => 'required|integer|in:1,2',
            'status' => 'required|string|in:active,inactive',
        ]);

        $fAccounts->update($validated);

        return redirect()->route('financial.accounts.index')
            ->with('success', 'Financial account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FAccounts $fAccounts)
    {
        // Check if account has entries before deletion
        if ($fAccounts->entries()->count() > 0) {
            return redirect()->route('financial.accounts.index')
                ->with('error', 'Cannot delete account that has entries. Please remove all entries first.');
        }

        $fAccounts->delete();

        return redirect()->route('financial.accounts.index')
            ->with('success', 'Financial account deleted successfully.');
    }
}
