<?php

namespace Tests\Unit\Services\Integrations;

use App\Services\ClickPayService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClickPayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.clickpay.base_url', 'https://test.clickpay.example');
        Config::set('services.clickpay.profile_id', 'TEST_PROFILE');
        Config::set('services.clickpay.server_key', 'TEST_SERVER_KEY');
        Config::set('services.clickpay.currency', 'SAR');
    }

    public function test_successful_charge_returns_success_and_response(): void
    {
        Http::fake([
            'test.clickpay.example/*' => Http::response([
                'isSuccess' => true,
                'tran_ref' => 'TST12345',
            ], 200),
        ]);

        $service = new ClickPayService;
        $result = $service->chargeWithToken($this->payload());

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['response']);
        $this->assertSame('TST12345', $result['response']['tran_ref']);
    }

    public function test_failed_charge_with_unsuccessful_response_returns_success_false(): void
    {
        Http::fake([
            'test.clickpay.example/*' => Http::response([
                'isSuccess' => false,
                'code' => 500,
            ], 200),
        ]);

        $service = new ClickPayService;
        $result = $service->chargeWithToken($this->payload());

        $this->assertFalse($result['success']);
        $this->assertSame(500, $result['response']['code']);
    }

    public function test_non_ok_status_returns_success_false(): void
    {
        Http::fake([
            'test.clickpay.example/*' => Http::response(['message' => 'Internal Server Error'], 500),
        ]);

        $service = new ClickPayService;
        $result = $service->chargeWithToken($this->payload());

        $this->assertFalse($result['success']);
        $this->assertSame(500, $result['status']);
    }

    public function test_http_exception_returns_success_false_with_error(): void
    {
        Http::fake([
            'test.clickpay.example/*' => function () {
                throw new ConnectionException('ClickPay connection timed out');
            },
        ]);

        $service = new ClickPayService;
        $result = $service->chargeWithToken($this->payload());

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('timed out', $result['error']);
    }

    public function test_service_uses_config_values_for_request(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('https://test.clickpay.example/payment/request', $request->url());
            $this->assertSame('TEST_SERVER_KEY', $request->header('Authorization')[0] ?? null);
            $this->assertSame('application/json', $request->header('Content-Type')[0] ?? null);
            $this->assertSame('TEST_PROFILE', $request['profile_id']);
            $this->assertSame('SAR', $request['cart_currency']);

            return Http::response(['isSuccess' => true, 'tran_ref' => 'TST54321']);
        });

        $service = new ClickPayService;
        $result = $service->chargeWithToken($this->payload());

        $this->assertTrue($result['success']);
        $this->assertSame('TST54321', $result['response']['tran_ref']);
    }

    private function payload(): array
    {
        return [
            'token' => 'tok_abc123',
            'tran_ref' => 'PREV_REF_001',
            'cart_id' => 'CART-123',
            'cart_amount' => 100.00,
            'cart_description' => 'Test recurring charge',
        ];
    }
}
