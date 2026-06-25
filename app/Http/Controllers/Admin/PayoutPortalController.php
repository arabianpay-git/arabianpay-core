<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\SupplierPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayoutPortalController extends Controller
{
    // GET /admin/payouts (Order-centric list with action to create payout)
    public function index(Request $request)
    {
        // if range did not selected, set default for the current month
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'general_status' => ['nullable', 'string', 'in:pending,processing,ready_to_payout,paid,canceled'],
        ]);

        // Date range: default to current month
        $start = $request->filled('date_from')
            ? Carbon::parse($request->get('date_from'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $end = $request->filled('date_to')
            ? Carbon::parse($request->get('date_to'))->endOfDay()
            : Carbon::now()->endOfMonth();

        $general_status = $request->input('general_status');
        // Load all orders within date range
        $orders = Order::query()
            ->with(['seller', 'user', 'assigned', 'payouts'])
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $payouts = SupplierPayout::query()
            ->with(['supplier', 'creator'])
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payouts.index', data: [
            'orders' => $orders,
            'payouts' => $payouts,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'general_status' => $general_status,
        ]);
    }

    // POST /admin/payouts
    public function store(Request $request)
    {
        $hasErrors = false;
        $errorMessage = '';
        Log::info('Payout store method called', $request->all());

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::with('seller')->findOrFail($data['order_id']);

        // Resolve merchant by seller's user_id

        $merchant = Merchant::where('user_id', $order->seller_id)->first();
        if (! $merchant) {
            return back()->withErrors(['order_id' => __('No merchant found for the order seller. id : :id', ['id' => $order->seller_id])])->withInput();
        }
        // get customer data
        $customer = Customer::where('user_id', $order->user_id)->first();

        $amount = $data['amount'] ?? $this->computeDefaultPayoutAmount($order);
        if (! $amount || $amount <= 0) {
            return back()->withErrors(['amount' => __('Unable to compute a positive payout amount for this order. Provide an amount manually.')])->withInput();
        }
        Log::info('Computed payout amount: '.$amount);
        //  DB::beginTransaction();
        try {
            $currentUser = Auth::user();
            if (! $currentUser) {
                // This should not happen as the user is authenticated
                throw new \Exception('Authenticated user not found.');
            }
            Log::info('Starting transaction...');
            $payout = new SupplierPayout;
            $payout->uuid = (string) Str::uuid();
            $payout->supplier_id = $merchant->id;
            $payout->order_id = $order->id;
            $payout->amount = $amount;
            $payout->status = 'pending';
            $payout->payout_date = null;
            $payout->notes = $data['notes'] ?? null;
            $payout->created_by = $currentUser->id;
            Log::info('About to save payout...', ['payout' => $payout->toArray()]);
            $payout->save();

            // Log::info('Payout saved successfully. ID: ' . $payout->id);
            if ($order->grand_total - $order->commission_amount == $amount) {
                $order->general_status = 'completed';
            } elseif ($order->grand_total - $order->commission_amount > $amount) {
                $order->general_status = 'partially_paid';
            } else {
                //  DB::rollBack();
                return back()->withErrors(['amount' => __('Payout amount exceeds order amount.')])->withInput();
            }
            $order->save();

            // get the current user

            // create financial transaction record here if needed
            $fTransaction = FTransaction::create([
                'uuid' => (string) Str::uuid(),
                'checkout_id' => $order->checkout_id,
                'customer_id' => null,
                'supplier_id' => $merchant->id,
                'user_id' => $currentUser->id,
                'payment_id' => null,
                'transaction_type' => 'order_placement',
                'amount' => $payout->amount,
                'status' => 'Waiting approval',
                'transaction_date' => now(),
                'notes' => ' Payout for order number  #'.$order->id.', for seller: '.$order->seller?->first_name.' '.$order->seller?->last_name,
            ]);
            $supplierAccount = FAccounts::where('id', '2400')->first();

            FEntry::create([
                'transaction_id' => $fTransaction->id,
                'checkout_id' => $order->checkout_id,
                'user_id' => $currentUser->id,
                'customer_id' => null,
                'supplier_id' => $merchant->id,
                'order_id' => $order->id,
                'payment_id' => null,
                'account_id' => $supplierAccount->id,
                'account_name' => $supplierAccount->account_name,
                'debit' => $payout->amount,
                'credit' => 0,
                'status' => 'Waiting approval',
                'entry_date' => now(),
                'notes' => 'Payout for seller: '.$order->seller?->first_name.' '.$order->seller?->last_name.' for order #'.$order->id.',',
            ]);
            // bank account entry
            $bankAccount = FAccounts::where('id', '1201')->first();
            FEntry::create([
                'transaction_id' => $fTransaction->id,
                'checkout_id' => $order->checkout_id,
                'user_id' => $currentUser->id,
                'customer_id' => null,
                'supplier_id' => $merchant->id,
                'order_id' => $order->id,
                'payment_id' => null,
                'account_id' => $bankAccount->id,
                'account_name' => $bankAccount->account_name,
                'debit' => 0,
                'credit' => $payout->amount,
                'status' => 'Waiting approval',
                'entry_date' => now(),
                'notes' => 'Payout for seller: '.$order->seller?->first_name.' '.$order->seller?->last_name.' for order #'.$order->id.',',
            ]);
            Log::info('All records created, committing transaction...');
            DB::commit();
            Log::info('Transaction committed successfully!');
        } catch (\Exception $e) {
            Log::error('Transaction failed, rolling back...', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to create payout: :message', ['message' => $e->getMessage()]),
                ], 500);
            }

            return back()->withErrors(['general' => __('Failed to create payout: :message', ['message' => $e->getMessage()])])->withInput();
        }

        Log::info('Redirecting to payouts index with success message');

        // Return JSON for AJAX requests

        return redirect()->route('payouts.index')->with('success', __('Payout created and marked as pending.'));
    }

    // POST /admin/payouts/{payout}/complete
    public function markCompleted(SupplierPayout $payout)
    {
        $payout->status = 'completed';
        $payout->payout_date = now();
        $payout->save();

        return redirect()->route('payouts.index')->with('success', __('Payout marked as completed.'));
    }

    // POST /admin/payouts/{payout}/fail
    public function markFailed(Request $request, SupplierPayout $payout)
    {
        $payout->status = 'failed';
        $payout->notes = trim(($payout->notes ? $payout->notes."\n" : '').($request->input('notes') ?? '')) ?: $payout->notes;
        $payout->save();

        return redirect()->route('payouts.index')->with('success', __('Payout marked as failed.'));
    }

    // GET /admin/payouts/status-by-order/{order}
    public function statusByOrder(Order $order)
    {
        $payout = SupplierPayout::where('order_id', $order->id)->latest()->first();

        return response()->json([
            'order_id' => $order->id,
            'has_payout' => (bool) $payout,
            'status' => $payout?->status,
            'payout_id' => $payout?->id,
        ]);
    }

    private function computeDefaultPayoutAmount(Order $order): ?float
    {
        // Business Rule: grand_total - commission_amount
        $grand = (float) ($order->grand_total ?? 0);
        $commission = (float) ($order->commission_amount ?? 0);
        $amount = max(0, $grand - $commission);

        return $amount > 0 ? round($amount, 2) : null;
    }
}
