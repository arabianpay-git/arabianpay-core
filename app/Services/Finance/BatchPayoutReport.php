<?php

namespace App\Services\Finance;

use App\Models\Settlement;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BatchPayoutReport
{
    /**
     * Generate comprehensive report for batch payout.
     *
     * @param  array  $settlements  Collection/Array of Settlement models
     * @param  float  $totalAmount  Total amount processed
     * @return string Path to the generated report
     */
    public function generateReport($settlements, $totalAmount)
    {
        // Placeholder for PDF generation
        // In a real app, use dompdf or snappy to generate a PDF

        $reportId = Str::uuid();
        $date = now()->format('Y-m-d H:i:s');

        $content = "BATCH PAYOUT REPORT\n";
        $content .= "Date: $date\n";
        $content .= "Report ID: $reportId\n";
        $content .= 'Total Amount: '.number_format($totalAmount, 2)."\n";
        $content .= "--------------------------------------------------\n";
        $content .= "Settlement No\tSupplier\tAmount\tStatus\n";
        $content .= "--------------------------------------------------\n";

        foreach ($settlements as $settlement) {
            $supplierName = $settlement->supplier->business_name ?? 'Unknown';
            $content .= "{$settlement->settlement_number}\t{$supplierName}\t{$settlement->payable_amount}\t{$settlement->status}\n";
        }

        $fileName = "payout-reports/exclude/batch_report_{$reportId}.txt";
        Storage::put($fileName, $content);

        return $fileName;
    }

    /**
     * Generate payout report (alias for generateReport).
     *
     * @param  array  $settlements  Collection/Array of Settlement models
     * @return string Path to the generated report
     */
    public function generatePayoutReport($settlements)
    {
        $totalAmount = collect($settlements)->sum('payable_amount');

        return $this->generateReport($settlements, $totalAmount);
    }

    /**
     * Export batch data to Excel format.
     */
    public function exportToExcel($settlements)
    {
        // Placeholder for Excel export
        // In a real app, use Maatwebsite/Excel
        return true;
    }

    /**
     * Generate bank transfer file (CSV/XML).
     */
    public function generateBankTransferFile($settlements, $format = 'csv')
    {
        $rows = [];
        $rows[] = ['Beneficiary Name', 'Account Number', 'Bank Name', 'Account Name', 'Amount', 'Reference', 'Settlement Number'];

        foreach ($settlements as $settlement) {
            $supplier = $settlement->supplier;
            $bankAccount = $supplier->supplierBanks->first() ?? null;
            $rows[] = [
                $supplier->business_name,
                $bankAccount ? $bankAccount->iban : 'N/A',
                $bankAccount ? $bankAccount->bank_name : 'N/A',
                $bankAccount ? $bankAccount->account_name : 'N/A',
                number_format($settlement->payable_amount, 2),
                'Weekly Payout',
                $settlement->settlement_number,
            ];
        }

        $fileName = 'bank-transfers/transfer_'.now()->format('YmdHis').'.csv';
        $path = Storage::disk('public')->path($fileName);
        Storage::disk('public')->makeDirectory(dirname($fileName));

        $handle = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $fileName;
    }

    /**
     * Log batch payout details.
     */
    public function logBatchPayout($batchId, $settlements, User $user)
    {
        // Placeholder for Audit Logging
        Log::info("Batch Payout $batchId processed by {$user->id}");
    }
}
