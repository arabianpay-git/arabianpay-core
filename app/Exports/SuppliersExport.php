<?php

namespace App\Exports;

use App\Models\Merchant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SuppliersExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        protected Collection $merchants
    ) {}

    public function collection(): Collection
    {
        return $this->merchants;
    }

    public function title(): string
    {
        return 'Suppliers';
    }

    public function headings(): array
    {
        return [
            'Arabianpay code',
            'الاسم',
            'اسم الشركة',
            'رقم الجوال',
            'الايميل',
            'الرقم الموحد',
            'نوع العمل',
            'الحالة',
        ];
    }

    /**
     * @param  Merchant  $merchant
     */
    public function map($merchant): array
    {
        $user = $merchant->user;
        $name = $user
            ? trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
            : '';

        $status = $merchant->status
            ? ucfirst(str_replace('_', ' ', (string) $merchant->status))
            : '';

        return [
            $merchant->id,
            $name,
            $user->business_name ?? '',
            $user->phone_number ?? '',
            $user->email ?? '',
            $merchant->cr_number ?? '',
            $merchant->businessType->name ?? '',
            $status,
        ];
    }
}
