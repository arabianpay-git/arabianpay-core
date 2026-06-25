<?php

namespace Tests\Unit\Services\Integrations;

use App\Services\SimahService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimahServiceTest extends TestCase
{
    private const TEST_BASE_URL = 'https://simah.test.com';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('simah_token');

        Config::set('simah.base_url', self::TEST_BASE_URL);
        Config::set('simah.username', 'test-user');
        Config::set('simah.password', 'test-pass');
        Config::set('simah.timeout', 60);
        Config::set('simah.retry_attempts', 1);
        Config::set('simah.retry_interval', 0);
        Config::set('simah.token_ttl', 3500);
    }

    // --------------- getSilverReport ---------------

    public function test_get_silver_report_success(): void
    {
        $expected = [
            'status' => 'success',
            'data' => ['reportId' => 'RPT-001', 'score' => 750],
        ];

        Http::fakeSequence()
            ->push(['data' => ['token' => 'test-token']])
            ->push($expected);

        $service = new SimahService;
        $result = $service->getSilverReport(['idNumber' => '1010101010']);

        $this->assertSame($expected, $result);
    }

    public function test_get_silver_report_retries_on_401(): void
    {
        $expected = ['status' => 'success', 'data' => ['reportId' => 'RPT-RETRY']];

        Http::fakeSequence()
            ->push(['data' => ['token' => 'initial-token']])
            ->push([], 401)
            ->push(['data' => ['token' => 'new-token']])
            ->push($expected);

        $service = new SimahService;
        $result = $service->getSilverReport(['idNumber' => '1010101010']);

        $this->assertSame($expected, $result);
    }

    public function test_get_silver_report_throws_request_exception_on_failure(): void
    {
        Http::fakeSequence()
            ->push(['data' => ['token' => 'test-token']])
            ->push(['error' => 'Server Error'], 500);

        $service = new SimahService;

        $this->expectException(RequestException::class);
        $service->getSilverReport(['idNumber' => '1010101010']);
    }

    public function test_get_silver_report_validates_id_number(): void
    {
        $service = new SimahService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('idNumber is required.');

        $service->getSilverReport([]);
    }

    public function test_get_silver_report_sends_bearer_token(): void
    {
        Http::fake([
            self::TEST_BASE_URL.'/api/v1/Identity/login' => Http::response([
                'data' => ['token' => 'expected-bearer-token'],
            ]),
            self::TEST_BASE_URL.'/api/v1/enquiry/commercial/silver/report' => function ($request) {
                $this->assertSame(
                    'Bearer expected-bearer-token',
                    $request->header('Authorization')[0],
                );

                return Http::response(['status' => 'ok']);
            },
        ]);

        $service = new SimahService;
        $service->getSilverReport(['idNumber' => '1010101010']);
    }

    // --------------- consumerScore ---------------

    public function test_consumer_score_success(): void
    {
        $expected = [
            'status' => 'success',
            'data' => ['score' => 620, 'decision' => 'Accept'],
        ];

        Http::fakeSequence()
            ->push(['data' => ['token' => 'test-token']])
            ->push($expected);

        $service = new SimahService;
        $result = $service->consumerScore([
            'identityInfo' => ['idNumber' => '1010101010'],
        ]);

        $this->assertSame($expected, $result);
    }

    public function test_consumer_score_retries_on_401(): void
    {
        $expected = ['status' => 'success', 'data' => ['score' => 700]];

        Http::fakeSequence()
            ->push(['data' => ['token' => 'initial-token']])
            ->push([], 401)
            ->push(['data' => ['token' => 'new-token']])
            ->push($expected);

        $service = new SimahService;
        $result = $service->consumerScore([
            'identityInfo' => ['idNumber' => '1010101010'],
        ]);

        $this->assertSame($expected, $result);
    }

    public function test_consumer_score_throws_request_exception_on_failure(): void
    {
        Http::fakeSequence()
            ->push(['data' => ['token' => 'test-token']])
            ->push(['error' => 'Bad Request'], 400);

        $service = new SimahService;

        $this->expectException(RequestException::class);
        $service->consumerScore([
            'identityInfo' => ['idNumber' => '1010101010'],
        ]);
    }

    public function test_consumer_score_validates_id_number(): void
    {
        $service = new SimahService;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('identityInfo.idNumber is required.');

        $service->consumerScore([]);
    }

    public function test_consumer_score_sends_language_header(): void
    {
        Http::fake([
            self::TEST_BASE_URL.'/api/v1/Identity/login' => Http::response([
                'data' => ['token' => 'test-token'],
            ]),
            self::TEST_BASE_URL.'/api/v2/enquiry/consumer/score' => function ($request) {
                $this->assertSame('application/json', $request->header('Accept')[0]);
                $this->assertSame('ar', $request->header('language')[0]);

                return Http::response(['status' => 'ok']);
            },
        ]);

        $service = new SimahService;
        $service->consumerScore([
            'identityInfo' => ['idNumber' => '1010101010'],
            'language' => 'ar',
        ]);
    }

    // --------------- HTTP exceptions ---------------

    public function test_http_connection_exception_bubbles_up(): void
    {
        Http::fake([
            '*' => function () {
                throw new ConnectionException('cURL error 28: Connection timed out');
            },
        ]);

        $service = new SimahService;

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection timed out');

        $service->getSilverReport(['idNumber' => '1010101010']);
    }

    // --------------- Authentication ---------------

    public function test_authenticate_fails_on_missing_token_in_response(): void
    {
        Http::fake([
            self::TEST_BASE_URL.'/api/v1/Identity/login' => Http::response([
                'data' => [], // missing 'token'
            ]),
        ]);

        $service = new SimahService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SIMAH authentication: token missing');

        $service->getSilverReport(['idNumber' => '1010101010']);
    }
}
