<?php

namespace App\Services\Finance;

use App\Models\Settlement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BatchPayoutReport
{
    /**
     * Generate comprehensive report for batch payout.
     * 
     * @param array $settlements Collection/Array of Settlement models
     * @param float $totalAmount Total amount processed
     * @return string Path to the generated report
     */
    /**
     * 
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
        $content .= "Total Amount: " . number_format($totalAmount, 2) . "\n";
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
     * @param array $settlements Collection/Array of Settlement models
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
        $content = "Beneficiary Name,Account Number,Bank Name,Account Name,Amount,Reference,Settlement Number\n";

        foreach ($settlements as $settlement) {
            $supplier = $settlement->supplier;
            $bankAccount = $supplier->supplierBanks->first() ?? null; // Assuming relationship
            $accountNumber = $bankAccount ? $bankAccount->iban : 'N/A';
            $bankName = $bankAccount ? $bankAccount->bank_name : 'N/A';
            $accountName = $bankAccount ? $bankAccount->account_name : 'N/A';
            $name = $supplier->business_name;

            $content .= "{$name},{$accountNumber},{$bankName},{$accountName},{$settlement->payable_amount},Weekly Payout,{$settlement->settlement_number}\n";
        }

        $fileName = "bank-transfers/transfer_" . now()->format('YmdHis') . ".csv";
        Storage::disk('public')->put($fileName, $content);

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
