<?php

namespace Tests\Unit\Services\Finance;

use App\Models\Order;
use App\Services\Finance\SettlementService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    public static function calculateAmountProvider(): array
    {
        return [
            'two orders with commission' => [
                [
                    ['grand_total' => 1000, 'commission_amount' => 50],
                    ['grand_total' => 2000, 'commission_amount' => 100],
                ],
                ['total_amount' => '3000.00', 'commission_amount' => '150.00', 'payable_amount' => '2850.00'],
            ],
            'commission exceeds total is floored at zero' => [
                [
                    ['grand_total' => 100, 'commission_amount' => 150],
                ],
                ['total_amount' => '100.00', 'commission_amount' => '150.00', 'payable_amount' => '0.00'],
            ],
            'empty collection returns zero' => [
                [],
                ['total_amount' => '0.00', 'commission_amount' => '0.00', 'payable_amount' => '0.00'],
            ],
            'null commission treated as zero' => [
                [
                    ['grand_total' => 500, 'commission_amount' => null],
                ],
                ['total_amount' => '500.00', 'commission_amount' => '0.00', 'payable_amount' => '500.00'],
            ],
        ];
    }

    #[DataProvider('calculateAmountProvider')]
    public function test_calculate_settlement_amount(array $ordersData, array $expected): void
    {
        $orders = collect(array_map(fn ($data) => new Order($data), $ordersData));

        $service = new SettlementService;
        $amounts = $service->calculateSettlementAmount($orders);

        $this->assertSame($expected['total_amount'], $amounts['total_amount']);
        $this->assertSame($expected['commission_amount'], $amounts['commission_amount']);
        $this->assertSame($expected['payable_amount'], $amounts['payable_amount']);
    }

    public function test_generate_settlement_number_format(): void
    {
        $service = new SettlementService;

        $number = $service->generateSettlementNumber(42, now()->setDate(2026, 6, 25));

        $this->assertSame('SETT-20260625-42', $number);
    }

    public function test_calculate_settlement_date_is_next_tuesday(): void
    {
        $service = new SettlementService;

        // Monday 2026-06-29 -> next Tuesday is 2026-06-30
        $date = $service->calculateSettlementDate(now()->setDate(2026, 6, 29));
        $this->assertSame('2026-06-30', $date->toDateString());

        // Tuesday 2026-06-30 -> next Tuesday is 2026-07-07
        $date = $service->calculateSettlementDate(now()->setDate(2026, 6, 30));
        $this->assertSame('2026-07-07', $date->toDateString());
    }
}
