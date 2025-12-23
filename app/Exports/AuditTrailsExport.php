<?php

namespace App\Exports;

use App\Models\AuditTrail;
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

class AuditTrailsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Record ID',
            'Timestamp',
            'Environment',
            'Event Category',
            'Event Type',
            'Entity Type',
            'Entity ID',
            'Actor Name',
            'Actor Email',
            'Actor Role',
            'IP Address',
            'Device Fingerprint',
            'Action Summary',
            'Justification',
            'PDPL Category',
            'PII Fields',
            'Masking State',
            'Request ID',
            'Correlation ID',
            'Created At',
            'Updated At'
        ];
    }

    public function map($trail): array
    {
        // Get actor name
        $actorName = $trail->actorUser
            ? $trail->actorUser->first_name . ' ' . $trail->actorUser->last_name
            : 'System';

        // Format PII fields
        $piiFields = '';
        if ($trail->pii_fields_involved) {
            if (is_array($trail->pii_fields_involved)) {
                $piiFields = implode(', ', $trail->pii_fields_involved);
            } else {
                $decoded = json_decode($trail->pii_fields_involved, true);
                if (is_array($decoded)) {
                    $piiFields = implode(', ', $decoded);
                } else {
                    $piiFields = $trail->pii_fields_involved;
                }
            }
        }

        return [
            $trail->id,
            $trail->timestamp ? $trail->timestamp->format('Y-m-d H:i:s') : '',
            strtoupper($trail->environment),
            $trail->event_category,
            $trail->event_type,
            $trail->entity_type,
            $trail->entity_id,
            $actorName,
            $this->maskEmail($trail->actor_email),
            $trail->actor_role,
            $this->maskIp($trail->ip_address),
            $this->maskFingerprint($trail->device_fingerprint),
            $trail->action_summary,
            $trail->justification ?? '',
            $trail->pdpl_category,
            $piiFields,
            $trail->masking_state,
            $trail->request_id,
            $trail->correlation_id,
            $trail->created_at->format('Y-m-d H:i:s'),
            $trail->updated_at->format('Y-m-d H:i:s')
        ];
    }

    public function title(): string
    {
        return 'Audit Trails';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Record ID
            'B' => 20,  // Timestamp
            'C' => 15,  // Environment
            'D' => 20,  // Event Category
            'E' => 20,  // Event Type
            'F' => 15,  // Entity Type
            'G' => 15,  // Entity ID
            'H' => 25,  // Actor Name
            'I' => 25,  // Actor Email
            'J' => 15,  // Actor Role
            'K' => 15,  // IP Address
            'L' => 25,  // Device Fingerprint
            'M' => 50,  // Action Summary
            'N' => 40,  // Justification
            'O' => 15,  // PDPL Category
            'P' => 30,  // PII Fields
            'Q' => 15,  // Masking State
            'R' => 30,  // Request ID
            'S' => 30,  // Correlation ID
            'T' => 20,  // Created At
            'U' => 20,  // Updated At
        ];
    }

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

        // Style PDPL Category cells with colors
        $pdplColumn = 'O';
        for ($row = 2; $row <= $lastRow; $row++) {
            $category = $sheet->getCell($pdplColumn . $row)->getValue();

            $color = match ($category) {
                'Highly Sensitive' => 'FF0000',
                'Personal' => 'FFA500',
                'Sensitive' => 'FFC300',
                'Confidential' => '800080',
                'Internal' => '0000FF',
                'Public' => '008000',
                default => '000000'
            };

            $sheet->getStyle($pdplColumn . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $color],
                ],
            ]);
        }

        return [];
    }

    private function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email);
        return substr($name, 0, 1) . '***@' . $domain;
    }

    private function maskIp(?string $ip): ?string
    {
        if (!$ip || !str_contains($ip, '.')) {
            return $ip;
        }

        $parts = explode('.', $ip);
        return $parts[0] . '.***.***.' . end($parts);
    }

    private function maskFingerprint(?string $fp): ?string
    {
        if (!$fp) {
            return null;
        }

        return 'fp-****' . substr($fp, -3);
    }
}
