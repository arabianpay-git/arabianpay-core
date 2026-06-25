<?php

namespace Tests\Feature\Exports;

use App\Exports\SuppliersExport;
use App\Models\BusinessType;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SuppliersExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_headings_are_correct(): void
    {
        $export = new SuppliersExport(collect());

        $expected = [
            'Arabianpay code',
            'الاسم',
            'اسم الشركة',
            'رقم الجوال',
            'الايميل',
            'الرقم الموحد',
            'نوع العمل',
            'الحالة',
        ];

        $this->assertSame($expected, $export->headings());
    }

    public function test_title_is_suppliers(): void
    {
        $export = new SuppliersExport(collect());

        $this->assertSame('Suppliers', $export->title());
    }

    public function test_collection_returns_provided_merchants(): void
    {
        $merchants = Merchant::factory()->count(3)->create();
        $fresh = $merchants->fresh()->load('user', 'businessType');

        $export = new SuppliersExport($fresh);
        $this->assertCount(3, $export->collection());
    }

    public function test_maps_merchant_with_full_user_data(): void
    {
        $businessType = BusinessType::create([
            'name' => 'Retail',
            'slug' => 'retail',
            'order_level' => '0',
        ]);

        $user = User::factory()->create([
            'first_name' => 'Ahmed',
            'last_name' => 'Al-Saud',
            'business_name' => 'Ahmed Trading Co',
            'phone_number' => '+966501234567',
            'email' => 'ahmed@example.com',
        ]);

        $merchant = Merchant::factory()->create([
            'user_id' => $user->id,
            'business_type_id' => $businessType->id,
            'cr_number' => 'CR1234567',
            'status' => 'active',
        ]);

        $merchant->load('user', 'businessType');

        $export = new SuppliersExport(collect([$merchant]));
        $mapped = $export->map($merchant);

        $this->assertSame($merchant->id, $mapped[0]);
        $this->assertSame('Ahmed Al-Saud', $mapped[1]);
        $this->assertSame('Ahmed Trading Co', $mapped[2]);
        $this->assertSame('+966501234567', $mapped[3]);
        $this->assertSame('ahmed@example.com', $mapped[4]);
        $this->assertSame('CR1234567', $mapped[5]);
        $this->assertSame('Retail', $mapped[6]);
        $this->assertSame('Active', $mapped[7]);
    }

    public function test_maps_merchant_without_user_gracefully(): void
    {
        $merchant = Merchant::factory()->create([
            'user_id' => null,
            'status' => 'pending',
            'cr_number' => 'CR9999999',
        ]);

        $merchant->load('user', 'businessType');

        $export = new SuppliersExport(collect([$merchant]));
        $mapped = $export->map($merchant);

        $this->assertSame($merchant->id, $mapped[0]);
        $this->assertSame('', $mapped[1]);  // no user → empty name
        $this->assertSame('', $mapped[2]);  // no user → empty business_name
        $this->assertSame('', $mapped[3]);  // no user → empty phone
        $this->assertSame('', $mapped[4]);  // no user → empty email
        $this->assertSame('CR9999999', $mapped[5]);
        $this->assertSame('', $mapped[6]);  // no business type → empty
        $this->assertSame('Pending', $mapped[7]);
    }

    public function test_status_is_formatted_correctly(): void
    {
        $merchant = Merchant::factory()->create([
            'user_id' => null,
            'status' => 'under_review',
        ]);

        $merchant->load('user', 'businessType');

        $export = new SuppliersExport(collect([$merchant]));
        $mapped = $export->map($merchant);

        $this->assertSame('Under review', $mapped[7]);
    }

    public function test_downloads_excel_file(): void
    {
        Excel::fake();

        $export = new SuppliersExport(collect());
        Excel::download($export, 'suppliers.xlsx');

        Excel::assertDownloaded('suppliers.xlsx');
    }
}
