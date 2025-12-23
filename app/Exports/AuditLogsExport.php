<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AuditLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'Log ID',
            'Timestamp',
            'Event Type',
            'Log Category',
            'Severity',
            'Status',
            'User',
            'User Type',
            'Resource',
            'Endpoint',
            'Method',
            'IP Address',
            'Device Fingerprint',
            'Request ID',
            'Authentication Provider',
            'PDPL Category',
            'PII Fields',
            'Masking State',
            'Failure Reason',
            'Environment',
            'Created At',
        ];
    }

    /**
     * Map data for each row
     */
    public function map($log): array
    {
        return [
            $log->id,
            $log->timestamp ? $log->timestamp->format('Y-m-d H:i:s') : '',
            $log->event_type,
            $log->log_category,
            $log->severity,
            $log->status,
            $this->maskEmail($log->subject_identifier),
            $log->subject_type,
            $log->resource,
            $log->endpoint,
            $log->method,
            $this->maskIp($log->ip_address),
            $this->maskFingerprint($log->device_fingerprint),
            $log->request_id,
            $log->idp_provider ?? 'Local System',
            $log->pdpl_category,
            is_array($log->pii_fields_involved)
                ? implode(', ', $log->pii_fields_involved)
                : (json_decode($log->pii_fields_involved, true)
                    ? implode(', ', json_decode($log->pii_fields_involved, true))
                    : ''),
            $log->masking_state,
            $log->failure_reason,
            strtoupper($log->environment ?? 'PRODUCTION'),
            $log->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Worksheet title
     */
    public function title(): string
    {
        return 'Audit Logs';
    }

    /**
     * Column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Log ID
            'B' => 20,  // Timestamp
            'C' => 25,  // Event Type
            'D' => 15,  // Log Category
            'E' => 12,  // Severity
            'F' => 12,  // Status
            'G' => 25,  // User
            'H' => 12,  // User Type
            'I' => 20,  // Resource
            'J' => 40,  // Endpoint
            'K' => 10,  // Method
            'L' => 15,  // IP Address
            'M' => 25,  // Device Fingerprint
            'N' => 30,  // Request ID
            'O' => 25,  // Authentication Provider
            'P' => 15,  // PDPL Category
            'Q' => 30,  // PII Fields
            'R' => 15,  // Masking State
            'S' => 40,  // Failure Reason
            'T' => 15,  // Environment
            'U' => 20,  // Created At
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style for header row
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Auto-size columns based on content
        foreach (range('A', 'U') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add freeze pane for headers
        $sheet->freezePane('A2');

        // Apply borders to all cells
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
        ]);

        // Style severity cells with colors
        $severityColumn = 'E';
        for ($row = 2; $row <= $lastRow; $row++) {
            $severity = $sheet->getCell($severityColumn . $row)->getValue();

            $color = match ($severity) {
                'Critical' => 'FF0000',
                'High' => 'FF6B00',
                'Medium' => 'FFA500',
                'Low' => '008000',
                'Info' => '0000FF',
                default => '000000'
            };

            $sheet->getStyle($severityColumn . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $color],
                ],
            ]);
        }

        // Style status cells
        $statusColumn = 'F';
        for ($row = 2; $row <= $lastRow; $row++) {
            $status = $sheet->getCell($statusColumn . $row)->getValue();

            $color = match ($status) {
                'success', 'Success' => '008000',
                'failed', 'Failed' => 'FF0000',
                'pending', 'Pending' => 'FFA500',
                default => '000000'
            };

            $sheet->getStyle($statusColumn . $row)->applyFromArray([
                'font' => [
                    'bold' => $status === 'Failed',
                    'color' => ['rgb' => $color],
                ],
            ]);
        }

        return [];
    }

    /**
     * Mask email for privacy
     */
    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email);
        return substr($name, 0, 1) . '***@' . $domain;
    }

    /**
     * Mask IP address for privacy
     */
    private function maskIp(?string $ip): ?string
    {
        if (!$ip || !str_contains($ip, '.')) {
            return $ip;
        }

        $parts = explode('.', $ip);
        return $parts[0] . '.***.***.' . end($parts);
    }

    /**
     * Mask device fingerprint for privacy
     */
    private function maskFingerprint(?string $fp): ?string
    {
        if (!$fp) {
            return null;
        }

        return 'fp-****' . substr($fp, -3);
    }
}
