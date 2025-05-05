<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected $selectFields = [
        'id',
        'uuid',
        'refrence_payment',
        'user_id',
        'seller_id',
        'order_id',
        'loan_amount',
        'loan_start_date',
        'loan_end_date',
        'payment_status',
        'settlement_status',
        'created_at',
    ];

    protected function getTransactionsByStatus($status)
    {
        return Transaction::select($this->selectFields)
            ->with([
                'order' => function ($query) {
                    $query->select('id', 'grand_total', 'shipping_city', 'general_status');
                },
                'user'
            ])
            ->where('payment_status', $status)
            ->paginate(10);
    }

    public function transactionHistory()
    {
        $transactions = Transaction::select($this->selectFields)
            ->with([
                'order' => function ($query) {
                    $query->select('id', 'grand_total', 'shipping_city', 'general_status');
                },
                'user'
            ])
            ->paginate(10);

        $type = 'All Transactions';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }

    public function pending()
    {
        $transactions = $this->getTransactionsByStatus('pending');
        $type = 'Pending';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }

    public function due()
    {
        $transactions = $this->getTransactionsByStatus('due');
        $type = 'Due';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }

    public function late()
    {
        $transactions = $this->getTransactionsByStatus('late');
        $type = 'Late';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }

    public function paid()
    {
        $transactions = $this->getTransactionsByStatus('paid');
        $type = 'Paid';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }

    public function failed()
    {
        $transactions = $this->getTransactionsByStatus('failed');
        $type = 'Failed';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }

    public function payments()
    {
        $transactions = Transaction::select($this->selectFields)
            ->with([
                'order' => function ($query) {
                    $query->select('id', 'grand_total', 'shipping_city', 'general_status');
                },
                'user'
            ])
            ->paginate(10);
        $type = 'All';
        return view('admin.transactions.index', compact('transactions', 'type'));
    }



    public function wallet()
    {
        $wallets = Wallet::select([
            'id',
            'order_id',
            'amount',
            'balance_after',
            'transaction_type',
            'status',
            'created_at'
        ])
            ->where('seller_id', Auth::id())
            ->with([
                'order:id,invoice_number',
            ])
            ->latest()
            ->paginate(10);

        return view('admin.transactions.wallet', compact('wallets'));
    }


    public function generate($order)
    {
        try {
            $orderId = $order;

            $transaction = Transaction::where('order_id', $orderId)
                ->with([
                    'user:id,first_name,last_name,business_name,email,city_id,country_id',
                    'seller:id,first_name,last_name,business_name,email,city_id,country_id',
                    'user.city:id,name',
                    'user.country:id,name',
                    'order:id,invoice_number',
                    'wallet:id,order_id,seller_id,balance_after,status',
                    'schedulePayments' => function ($query) use ($orderId) {
                        $query->where('order_id', $orderId)->orderBy('due_date');
                    },
                ])
                ->first();

            if (!$transaction) {
                Log::error("No transaction found with order_id: {$orderId}");
                return abort(404, 'Transaction not found for this order ID');
            }

            $pdf = Pdf::loadView('admin.transactions.invoice', compact('transaction'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => false,
                    'defaultFont' => 'dejavu sans',
                    'fontDir' => storage_path('fonts/'),
                    'fontCache' => storage_path('fonts/'),
                    'tempDir' => storage_path('temp/'),
                    'chroot' => realpath(base_path()),
                    'logOutputFile' => storage_path('logs/dompdf.log'),
                    'enable_font_subsetting' => true,
                    'dpi' => 96,
                    'isFontSubsettingEnabled' => true,
                ]);

            $fileName = 'Invoice-' . $transaction->uuid . '.pdf';

            return $pdf->stream($fileName);
        } catch (\Exception $e) {
            Log::error("PDF generation failed for order_id {$order}: " . $e->getMessage());
            return back()->with('error', 'Invoice PDF generation failed. Please try again later.');
        }
    }
}
