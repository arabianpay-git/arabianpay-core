<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * [PHASE-5] Resilient HTTP client wrapper for third-party integrations.
 *
 * Provides consistent:
 * - Timeouts (connect + request)
 * - Retry with exponential backoff
 * - Structured logging (correlation ID, timing, outcome)
 * - Fail-fast when service is known-down (basic circuit breaker via cache)
 * - Safe payload logging (no sensitive data in logs)
 */
class IntegrationClient
{
    private string $serviceName;
    private int $timeout;
    private int $connectTimeout;
    private int $retries;
    private int $retryDelay;
    private string $circuitBreakerKey;

    private const CIRCUIT_OPEN_DURATION = 60; // seconds to skip calls after repeated failures
    private const FAILURE_THRESHOLD = 3;

    public function __construct(
        string $serviceName,
        int $timeout = 30,
        int $connectTimeout = 10,
        int $retries = 2,
        int $retryDelay = 200,
    ) {
        $this->serviceName = $serviceName;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->retries = $retries;
        $this->retryDelay = $retryDelay;
        $this->circuitBreakerKey = "integration:circuit:{$serviceName}";
    }

    /**
     * Send an HTTP request with resilience wrappers.
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param array $options ['headers' => [], 'body' => [], 'query' => []]
     * @return Response
     * @throws \RuntimeException if circuit is open or all retries exhausted
     */
    public function request(string $method, string $url, array $options = []): Response
    {
        // Circuit breaker check
        $failures = cache()->get($this->circuitBreakerKey, 0);
        if ($failures >= self::FAILURE_THRESHOLD) {
            Log::warning("[INTEGRATION:{$this->serviceName}] Circuit open — skipping request", [
                'url' => $this->sanitizeUrl($url),
                'failures' => $failures,
            ]);
            throw new \RuntimeException("{$this->serviceName} service circuit is open (too many failures). Try again later.");
        }

        $correlationId = request()?->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();
        $startTime = microtime(true);

        try {
            $client = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry($this->retries, $this->retryDelay, function (\Exception $e, PendingRequest $request) {
                    // Only retry on connection/timeout errors, not on 4xx client errors
                    return $e instanceof \Illuminate\Http\Client\ConnectionException;
                });

            // Apply headers
            if (! empty($options['headers'])) {
                $client = $client->withHeaders($options['headers']);
            }

            // Execute request
            $response = match (strtoupper($method)) {
                'GET' => $client->get($url, $options['query'] ?? []),
                'POST' => $client->post($url, $options['body'] ?? []),
                'PUT' => $client->put($url, $options['body'] ?? []),
                'PATCH' => $client->patch($url, $options['body'] ?? []),
                'DELETE' => $client->delete($url, $options['body'] ?? []),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info("[INTEGRATION:{$this->serviceName}] Request completed", [
                'method' => $method,
                'url' => $this->sanitizeUrl($url),
                'status' => $response->status(),
                'duration_ms' => $duration,
                'correlation_id' => $correlationId,
            ]);

            // Reset circuit breaker on success
            if ($response->successful()) {
                cache()->forget($this->circuitBreakerKey);
            }

            return $response;
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Increment circuit breaker counter
            $currentFailures = cache()->get($this->circuitBreakerKey, 0);
            cache()->put($this->circuitBreakerKey, $currentFailures + 1, self::CIRCUIT_OPEN_DURATION);

            Log::error("[INTEGRATION:{$this->serviceName}] Request failed", [
                'method' => $method,
                'url' => $this->sanitizeUrl($url),
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'correlation_id' => $correlationId,
                'failure_count' => $currentFailures + 1,
            ]);

            throw $e;
        }
    }

    /**
     * Convenience methods.
     */
    public function get(string $url, array $query = [], array $headers = []): Response
    {
        return $this->request('GET', $url, ['query' => $query, 'headers' => $headers]);
    }

    public function post(string $url, array $body = [], array $headers = []): Response
    {
        return $this->request('POST', $url, ['body' => $body, 'headers' => $headers]);
    }

    /**
     * Remove query parameters and sensitive path segments from URLs for logging.
     */
    private function sanitizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'unknown') . ($parsed['path'] ?? '/');
    }
}
