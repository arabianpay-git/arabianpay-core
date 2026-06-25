<?php

namespace Tests\Unit\Services;

use App\Services\RiskService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RiskServiceComputeOMRSTest extends TestCase
{
    public static function omrsProvider(): array
    {
        return [
            'perfect score no caf adjustment' => [
                ['lps' => 100, 'chs' => 100, 'bcs' => 100, 'bps' => 100, 'bes' => 100, 'caf' => 1.0],
                100.0,
            ],
            'all scores 50 with default weights' => [
                ['lps' => 50, 'chs' => 50, 'bcs' => 50, 'bps' => 50, 'bes' => 50, 'caf' => 1.0],
                50.0,
            ],
            'caf reduces final score' => [
                ['lps' => 100, 'chs' => 100, 'bcs' => 100, 'bps' => 100, 'bes' => 100, 'caf' => 0.8],
                80.0,
            ],
            'caf increases final score' => [
                ['lps' => 50, 'chs' => 50, 'bcs' => 50, 'bps' => 50, 'bes' => 50, 'caf' => 1.2],
                60.0,
            ],
            'missing component uses default' => [
                ['lps' => 100, 'chs' => 100, 'bcs' => 100, 'bps' => 100, 'caf' => 1.0],
                91.0, // bes default 70 contributes weighted 21 instead of 30 -> base 0.91
            ],
        ];
    }

    #[DataProvider('omrsProvider')]
    public function test_compute_omrs(array $values, float $expected): void
    {
        $service = new RiskService;
        $method = new \ReflectionMethod($service, 'computeOMRS');
        $method->setAccessible(true);

        $omrs = $method->invoke($service, $values);

        $this->assertEqualsWithDelta($expected, $omrs, 0.01);
    }
}
