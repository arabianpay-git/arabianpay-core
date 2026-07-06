<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\FAccountResource;
use App\Http\Resources\Finance\FEntryResource;
use App\Models\FAccounts;
use App\Models\FEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FAccounts::orderBy('id');

        if ($request->filled('account_type1')) {
            $query->where('account_type1', $request->account_type1);
        }
        if ($request->filled('account_type2')) {
            $query->where('account_type2', $request->account_type2);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $accounts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => FAccountResource::collection($accounts),
            'meta' => [
                'current_page' => $accounts->currentPage(),
                'last_page' => $accounts->lastPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|unique:f_accounts,id',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'account_type1' => 'required|integer|in:1,2',
            'account_type2' => 'required|integer|in:1,2',
            'status' => 'required|string|in:active,inactive',
        ]);

        $account = FAccounts::create($validated);

        return response()->json([
            'success' => true,
            'data' => new FAccountResource($account),
            'message' => 'Account created successfully',
        ], 201);
    }

    public function show(FAccounts $account): JsonResponse
    {
        $account->loadCount('entries');

        $entries = $account->entries()
            ->with(['user', 'order', 'payment', 'customer', 'supplier', 'transaction'])
            ->orderBy('entry_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'account' => new FAccountResource($account),
                'entries' => FEntryResource::collection($entries),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'total' => $entries->total(),
                ],
            ],
        ]);
    }

    public function update(Request $request, FAccounts $account): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'account_type1' => 'required|integer|in:1,2',
            'account_type2' => 'required|integer|in:1,2',
            'status' => 'required|string|in:active,inactive',
        ]);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'data' => new FAccountResource($account->fresh()),
            'message' => 'Account updated successfully',
        ]);
    }

    public function destroy(FAccounts $account): JsonResponse
    {
        if ($account->entries()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account that has entries. Please remove all entries first.',
            ], 409);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    public function ledger(Request $request, $accountId): JsonResponse
    {
        $account = FAccounts::findOrFail($accountId);

        $start = $request->filled('date_from')
            ? Carbon::parse($request->get('date_from'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $end = $request->filled('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : Carbon::now()->endOfMonth();

        $isDebitNormal = ((int) $account->account_type2) === 1;

        $priorQuery = FEntry::where('account_id', $account->id)
            ->whereDate('entry_date', '<', $start->toDateString());

        if ($request->filled('customer_id') && $accountId == 1203) {
            $priorQuery->where('customer_id', $request->customer_id);
        } elseif ($request->filled('supplier_id') && $accountId == 2400) {
            $priorQuery->where('supplier_id', $request->supplier_id);
        }

        $prior = $priorQuery->selectRaw('COALESCE(SUM(debit),0) as sum_debit, COALESCE(SUM(credit),0) as sum_credit')
            ->first();

        $openingBalance = $isDebitNormal
            ? (float) $prior->sum_debit - (float) $prior->sum_credit
            : (float) $prior->sum_credit - (float) $prior->sum_debit;

        $entriesQuery = FEntry::with(['user'])
            ->where('account_id', $account->id)
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', $end->toDateString());

        if ($request->filled('customer_id') && $accountId == 1203) {
            $entriesQuery->where('customer_id', $request->customer_id);
        } elseif ($request->filled('supplier_id') && $accountId == 2400) {
            $entriesQuery->where('supplier_id', $request->supplier_id);
        }

        $entriesQuery->orderBy('entry_date', 'asc')->orderBy('id', 'asc');

        $totalsQuery = FEntry::where('account_id', $account->id)
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', $end->toDateString());

        if ($request->filled('customer_id') && $accountId == 1203) {
            $totalsQuery->where('customer_id', $request->customer_id);
        } elseif ($request->filled('supplier_id') && $accountId == 2400) {
            $totalsQuery->where('supplier_id', $request->supplier_id);
        }

        $totals = $totalsQuery->selectRaw('COALESCE(SUM(debit),0) as sum_debit, COALESCE(SUM(credit),0) as sum_credit')
            ->first();

        $perPage = min((int) $request->get('per_page', 50), 100);
        $entries = $entriesQuery->paginate($perPage);

        if ($entries->currentPage() > 1) {
            $beforeOffset = ($entries->currentPage() - 1) * $entries->perPage();
            $prePageQuery = FEntry::where('account_id', $account->id)
                ->whereDate('entry_date', '>=', $start->toDateString())
                ->whereDate('entry_date', '<=', $end->toDateString());

            if ($request->filled('customer_id') && $accountId == 1203) {
                $prePageQuery->where('customer_id', $request->customer_id);
            } elseif ($request->filled('supplier_id') && $accountId == 2400) {
                $prePageQuery->where('supplier_id', $request->supplier_id);
            }

            $prePageAgg = $prePageQuery->orderBy('entry_date', 'asc')
                ->orderBy('id', 'asc')
                ->limit($beforeOffset)
                ->get(['debit', 'credit']);

            foreach ($prePageAgg as $row) {
                $delta = $isDebitNormal
                    ? (float) ($row->debit ?? 0) - (float) ($row->credit ?? 0)
                    : (float) ($row->credit ?? 0) - (float) ($row->debit ?? 0);
                $openingBalance += $delta;
            }
        }

        $running = $openingBalance;
        $entries->getCollection()->transform(function ($row) use (&$running, $isDebitNormal) {
            $delta = $isDebitNormal
                ? (float) ($row->debit ?? 0) - (float) ($row->credit ?? 0)
                : (float) ($row->credit ?? 0) - (float) ($row->debit ?? 0);
            $running += $delta;
            $row->running_balance = $running;

            return $row;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'account' => new FAccountResource($account),
                'entries' => FEntryResource::collection($entries),
                'opening_balance' => $openingBalance,
                'closing_balance' => $running,
                'totals' => [
                    'sum_debit' => (float) $totals->sum_debit,
                    'sum_credit' => (float) $totals->sum_credit,
                ],
                'is_debit_normal' => $isDebitNormal,
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ],
        ]);
    }
}
