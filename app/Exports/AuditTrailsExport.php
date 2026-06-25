<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditTrailsExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
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
            'Updated At',
        ];
    }

    public function map($trail): array
    {
        $actorName = $trail->actorUser
            ? $trail->actorUser->first_name.' '.$trail->actorUser->last_name
            : 'System';

        $piiFields = '';
        if ($trail->pii_fields_involved) {
            $decoded = is_array($trail->pii_fields_involved)
                ? $trail->pii_fields_involved
                : json_decode($trail->pii_fields_involved, true);

            $piiFields = is_array($decoded)
                ? implode(', ', $decoded)
                : (string) $trail->pii_fields_involved;
        }

        return [
            $trail->id,
            $trail->timestamp ? "'".$trail->timestamp->format('Y-m-d H:i:s') : '',
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
            "'".$trail->created_at->format('Y-m-d H:i:s'),
            "'".$trail->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function title(): string
    {
        return 'Audit Trails';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 22,
            'C' => 15,
            'D' => 20,
            'E' => 20,
            'F' => 15,
            'G' => 15,
            'H' => 25,
            'I' => 25,
            'J' => 15,
            'K' => 18,
            'L' => 30,
            'M' => 50,
            'N' => 40,
            'O' => 18,
            'P' => 30,
            'Q' => 15,
            'R' => 30,
            'S' => 30,
            'T' => 22,
            'U' => 22,
        ];
    }

    public function styles(Worksheet $sheet)
    {
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
                ],
            ],
        ]);

        $sheet->freezePane('A2');

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

        $pdplColumn = 'O';
        for ($row = 2; $row <= $lastRow; $row++) {
            $category = $sheet->getCell($pdplColumn.$row)->getValue();

            $color = match ($category) {
                'Highly Sensitive' => 'FF0000',
                'Personal' => 'FFA500',
                'Sensitive' => 'FFC300',
                'Confidential' => '800080',
                'Internal' => '0000FF',
                'Public' => '008000',
                default => '000000'
            };

            $sheet->getStyle($pdplColumn.$row)->getFont()->setBold(true)->getColor()->setRGB($color);
        }

        return [];
    }

    private function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return $email;
        }

        [$name, $domain] = explode('@', $email);

        return substr($name, 0, 1).'***@'.$domain;
    }

    private function maskIp(?string $ip): ?string
    {
        if (! $ip || ! str_contains($ip, '.')) {
            return $ip;
        }

        $parts = explode('.', $ip);

        return $parts[0].'.***.***.'.end($parts);
    }

    private function maskFingerprint(?string $fp): ?string
    {
        return $fp ? 'fp-****'.substr($fp, -3) : null;
    }
}
