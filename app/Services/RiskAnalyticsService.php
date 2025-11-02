<?php

namespace App\Services;

use App\Models\User;
use App\Models\RiskScore;
use App\Models\RiskWeight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RiskAnalyticsService
{
    // Default weights
    private float $weightCrIdScore;
    private float $weightPosScore;
    private float $weightRepaymentScore;
    private float $weightIndustryScore;
    private float $weightLocationScore;

    // Google API settings
    private const GOOGLE_CACHE_DURATION = 604800; // 7 days
    private const GOOGLE_CACHE_FAILURE_DURATION = 86400; // 1 day
    private const GOOGLE_REQUEST_TIMEOUT = 8; // seconds
    private const GOOGLE_RETRY_ATTEMPTS = 2;
    private const GOOGLE_RETRY_SLEEP_MS = 150; // milliseconds

    public function __construct()
    {
        // Fetch latest weights from RiskWeight model
        $latestWeights = RiskWeight::latest()->first();

        $this->weightCrIdScore = $latestWeights->cr_id ?? 25;
        $this->weightPosScore = $latestWeights->pos ?? 25;
        $this->weightRepaymentScore = $latestWeights->repayment ?? 20;
        $this->weightIndustryScore = $latestWeights->industry ?? 15;
        $this->weightLocationScore = $latestWeights->location ?? 10;
    }

    /**
     * Calculate risk object for a single user
     */
    public function calculateForUser(User $user, array $weights = []): object
    {
        // Override weights if provided
        $this->applyWeights($weights);

        // Normalize user data
        $userData = $this->getUserData($user);
        $decodedCrData = $this->parseCrData($userData['crData']);

        // Calculate all internal scores
        $scores = $this->calculateAllScores($user, $decodedCrData, $userData);

        // Choose business name for display and for Google lookup
        $businessNameForGoogle = $decodedCrData['name'] ?? $userData['businessName'];

        return (object)[
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'business_name' => $userData['businessName'],
            'business_name_for_google' => $businessNameForGoogle,
            'cr_number' => $userData['crNumber'],
            'id_number' => $userData['idNumber'],
            'cr_id_match_score' => $scores['idMatchScore'],
            'id_expiry_score' => $scores['idExpiryScore'],
            'cr_expiry_score' => $scores['crExpiryScore'],
            'business_type_score' => $scores['industryScore'],
            'activity_score' => $scores['activityScore'],
            'cr_id_total' => $scores['crIdRaw'],
            'cr_id_score' => round($scores['crIdScore'], 2),
            'pos_revenue' => $userData['monthlyPos'],
            'pos_score' => round($scores['posScore'], 2),
            'late_payments' => $scores['repaymentDelays'],
            'repayment_score' => $scores['repaymentScore'],
            'industry' => $scores['industryName'],
            'industry_score' => $scores['industryScore'],
            'location' => $scores['locationDetails'],
            'location_score' => $scores['locationScore'],
            'flagged' => $scores['manualRisk']['flagged'],
            'risk_score' => $scores['manualRisk']['score'],
            'reason' => $scores['manualRisk']['reason'],
            'google_rating' => $scores['googleRating'],
            'total_score' => round($scores['totalScore'], 2),
        ];
    }

    /**
     * Calculate risks for multiple users (prefetches Google ratings into cache first).
     */
    public function calculateForUsers($users, array $weights = []): EloquentCollection
    {
        $userCollection = collect($users);

        // Extract business names (CR name fallback to stored business name)
        $businessNames = $userCollection->map(function ($user) {
            $userData = $this->getUserData($user);
            $decodedCrData = $this->parseCrData($userData['crData']);
            return $decodedCrData['name'] ?? $userData['businessName'];
        })->filter()->unique()->values()->all();

        // Prefetch ratings synchronously (will populate cache)
        if (!empty($businessNames)) {
            $this->prefetchGoogleRatings($businessNames);
        }

        // Compute objects (calculateForUser will read cached google rating)
        $mapped = $userCollection->map(fn($user) => $this->calculateForUser($user, $weights));

        return new EloquentCollection($mapped->values()->all());
    }

    /**
     * Helper: extract business names (public)
     */
    public function extractBusinessNamesFromUsers($users): array
    {
        return collect($users)->map(function ($user) {
            $userData = $this->getUserData($user);
            $decodedCrData = $this->parseCrData($userData['crData']);
            return $decodedCrData['name'] ?? $userData['businessName'];
        })->filter()->unique()->values()->all();
    }

    /**
     * Apply weights overrides
     */
    private function applyWeights(array $weights): void
    {
        $this->weightCrIdScore = $weights['cr_id'] ?? $this->weightCrIdScore;
        $this->weightPosScore = $weights['pos'] ?? $this->weightPosScore;
        $this->weightRepaymentScore = $weights['repayment'] ?? $this->weightRepaymentScore;
        $this->weightIndustryScore = $weights['industry'] ?? $this->weightIndustryScore;
        $this->weightLocationScore = $weights['location'] ?? $this->weightLocationScore;
    }

    /**
     * Normalize user-related data for merchants vs customers
     */
    private function getUserData(User $user): array
    {
        if ($user->user_type === 'merchant') {
            $govData = $user->merchant;
            return [
                'crData' => $govData->goverment_data ?? null,
                'crNumber' => $govData->cr_number ?? null,
                'idNumber' => $govData->owner_iqama_number ?? null,
                'businessName' => $govData->business_name ?? ($user->business_name ?? 'Unknown Business'),
                'monthlyPos' => $govData->pos_revenue ?? 30000,
            ];
        }

        $govData = $user->customer;
        return [
            'crData' => $govData->cr_data ?? null,
            'crNumber' => $govData->cr_number ?? null,
            'idNumber' => $govData->id_number ?? null,
            'businessName' => $govData->business_name ?? ($user->business_name ?? 'Unknown Business'),
            'monthlyPos' => 30000,
        ];
    }

    /**
     * Compute all component scores for a single user (internal)
     */
    private function calculateAllScores(User $user, array $crData, array $userData): array
    {
        $idMatchScore = $this->calculateIdMatchScore($userData['idNumber'], $crData);
        $idExpiryScore = $this->calculateIdExpiryScore($user);
        $crExpiryScore = $this->calculateCrExpiryScore($crData);
        $industryData = $this->calculateIndustryScore($user);
        $activityScore = $this->calculateActivityScore($crData);

        $crIdRaw = $idMatchScore + $idExpiryScore + $crExpiryScore + $industryData['industry_score'] + $activityScore;
        $crIdScore = min($crIdRaw / 100, 1) * $this->weightCrIdScore;
        $posScore = min($userData['monthlyPos'] / 50000, 1) * $this->weightPosScore;

        $repaymentData = $this->calculateRepaymentScore($user);
        $locationData = $this->calculateLocationScore($crData);

        // Use cached rating only (do not fetch here)
        $businessNameForGoogle = $crData['name'] ?? $userData['businessName'];
        $googleRating = $this->calculateGoogleScore($businessNameForGoogle);

        $manualRisk = $this->getManualRisk($user->id);

        $totalScore = $crIdScore + $posScore +
            $repaymentData['score'] +
            $industryData['industry_score'] +
            $locationData['score'] +
            $manualRisk['score'] +
            ($googleRating ?? 0);

        return [
            'idMatchScore' => $idMatchScore,
            'idExpiryScore' => $idExpiryScore,
            'crExpiryScore' => $crExpiryScore,
            'industryScore' => $industryData['industry_score'],
            'industryName' => $industryData['industry'],
            'activityScore' => $activityScore,
            'crIdRaw' => $crIdRaw,
            'crIdScore' => $crIdScore,
            'posScore' => $posScore,
            'repaymentDelays' => $repaymentData['delays'],
            'repaymentScore' => $repaymentData['score'],
            'locationScore' => $locationData['score'],
            'locationDetails' => $locationData['details'],
            'googleRating' => $googleRating,
            'manualRisk' => $manualRisk,
            'totalScore' => $totalScore,
        ];
    }

    /**
     * Prefetch Google ratings (synchronous chunked fetch).
     *
     * NOTE: This method is synchronous and will call fetchAndCacheGoogleRating for uncached names.
     *       It is intentionally chunked to avoid long single-run request bursts and TTFB spikes.
     *
     * @param string[] $businessNames
     * @param int $chunkSize
     * @param int $sleepMicroseconds pause between chunks (microseconds)
     */
    public function prefetchGoogleRatings(array $businessNames, int $chunkSize = 10, int $sleepMicroseconds = 300000): void
    {
        $uniqueNames = array_unique(array_filter($businessNames));
        $toFetch = [];

        foreach ($uniqueNames as $name) {
            $cacheKey = $this->getGoogleCacheKey($name);
            if (!Cache::has($cacheKey)) {
                $toFetch[] = $name;
            }
        }

        if (empty($toFetch)) {
            return;
        }

        foreach (array_chunk($toFetch, $chunkSize) as $chunk) {
            foreach ($chunk as $name) {
                try {
                    $this->fetchAndCacheGoogleRating($name);
                } catch (\Throwable $e) {
                    Log::warning("Prefetch failed for '{$name}': " . $e->getMessage());
                }
            }
            // yield / gentle pause between chunks
            usleep($sleepMicroseconds);
        }
    }

    /**
     * Parse CR JSON-like data into an array safely
     */
    private function parseCrData($crData): array
    {
        if (is_string($crData)) {
            return json_decode($crData, true) ?: [];
        }
        return is_array($crData) ? $crData : [];
    }

    private function calculateIdMatchScore(?string $idNumber, array $crData): int
    {
        $ownerId = $crData['parties'][0]['identity']['id'] ?? null;

        if (!$idNumber || !$ownerId) return 0;
        if ($idNumber === $ownerId) return 30;
        if (str_contains($ownerId, $idNumber) || str_contains($idNumber, $ownerId)) return 15;
        return 0;
    }

    private function calculateIdExpiryScore(User $user): int
    {
        if (!$user->iqama_expiry) return 0;
        $diff = now()->diffInMonths($user->iqama_expiry, false);
        return match (true) {
            $diff >= 0 => 20,
            $diff >= -3 => 10,
            default => 0
        };
    }

    private function calculateCrExpiryScore(array $crData): int
    {
        $expiryDate = $crData['status']['confirmationDate']['gregorian'] ?? null;
        if (!$expiryDate) return 0;

        try {
            $expiry = \Carbon\Carbon::parse($expiryDate);
            $diff = now()->diffInMonths($expiry, false);
            return match (true) {
                $diff >= 0 => 20,
                $diff >= -3 => 10,
                default => 0
            };
        } catch (\Exception $e) {
            Log::warning("Failed to parse CR expiry date: {$expiryDate}");
            return 0;
        }
    }

    private function calculateActivityScore(array $crData): int
    {
        $crActivities = collect($crData['activities'] ?? [])->pluck('name')->toArray();
        return count($crActivities) > 0 ? 15 : 0;
    }

    private function calculateRepaymentScore(User $user): array
    {
        $isMerchant = $user->user_type === 'merchant';
        $delays = $user->transactions()
            ->where('payment_status', 'late')
            ->where($isMerchant ? 'seller_id' : 'user_id', $user->id)
            ->count();

        $score = match (true) {
            $delays === 0 => 20,
            $delays <= 2 => 15,
            default => 5,
        };

        return ['delays' => $delays, 'score' => $score];
    }

    private function calculateIndustryScore(User $user): array
    {
        $isMerchant = $user->user_type === 'merchant';
        $relation = $isMerchant ? 'merchant' : 'customer';

        $businessType = $user->$relation->businessType ?? null;
        $industryRisk = strtolower($businessType->risk_level ?? 'medium');

        $riskMap = [
            'low' => 15,
            'medium-low' => 12,
            'medium' => 10,
            'high' => 8,
            'very high' => 5,
        ];

        $industryScore = $riskMap[$industryRisk] ?? 10;
        $industry = $businessType->name ?? 'Unknown Industry';

        return ['industry' => $industry, 'industry_score' => $industryScore];
    }

    private function calculateLocationScore(array $crData): array
    {
        $city = strtolower($crData['headquarterCityName'] ?? '');
        $cityTiers = [
            'riyadh' => 6,
            'jeddah' => 6,
            'dammam' => 6,
            'makkah' => 4,
            'madinah' => 4,
            'khobar' => 4
        ];

        $tierScore = $cityTiers[$city] ?? 2;

        $businessCount = Cache::get("business_density_{$city}", 0);
        $activityScore = match (true) {
            $businessCount >= 500 => 4.5,
            $businessCount >= 100 => 3,
            default => 1
        };

        $defaultRate = Cache::get("default_rate_{$city}", 0);
        $defaultScore = match (true) {
            $defaultRate <= 5 => 4.5,
            $defaultRate <= 10 => 2,
            default => 0
        };

        $totalScore = round(($tierScore + $activityScore + $defaultScore) * (10 / 15), 2);

        return [
            'score' => $totalScore,
            'details' => [
                'city' => ucwords($city) ?: 'Unknown',
                'tier_score' => $tierScore,
                'activity_score' => $activityScore,
                'default_rate_score' => $defaultScore,
            ]
        ];
    }

    /**
     * Read cached Google rating for a business; return null if not cached.
     * This avoids synchronous calls during page render.
     */
    private function calculateGoogleScore($businessName = null): ?float
    {
        if (!$businessName) {
            return null;
        }

        $cacheKey = $this->getGoogleCacheKey($businessName);

        if (!Cache::has($cacheKey)) {
            return null; // do not fetch here to keep page fast
        }

        $val = Cache::get($cacheKey);
        return $val === 'false' ? null : (float)$val;
    }

    /**
     * Fetch rating using Google TextSearch API and cache it.
     * Returns the rating or null on failure.
     */
    public function fetchAndCacheGoogleRating(string $businessName): ?float
    {
        $cacheKey = $this->getGoogleCacheKey($businessName);

        // double-check cache
        if (Cache::has($cacheKey)) {
            $val = Cache::get($cacheKey);
            return $val === 'false' ? null : (float)$val;
        }

        $apiKey = env('GOOGLE_PLACE_API_KEY');
        if (!$apiKey) {
            Log::warning("Google API key not configured; skipping rating fetch for: {$businessName}");
            Cache::put($cacheKey, 'false', self::GOOGLE_CACHE_FAILURE_DURATION);
            return null;
        }

        try {
            $response = Http::timeout(self::GOOGLE_REQUEST_TIMEOUT)
                ->retry(self::GOOGLE_RETRY_ATTEMPTS, self::GOOGLE_RETRY_SLEEP_MS)
                ->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                    'query' => $businessName,
                    'key' => $apiKey,
                ]);

            if (!$response->successful()) {
                Log::warning("Google TextSearch API failed for: {$businessName}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                Cache::put($cacheKey, 'false', self::GOOGLE_CACHE_FAILURE_DURATION);
                return null;
            }

            $data = $response->json();
            $rating = $data['results'][0]['rating'] ?? null;

            if ($rating !== null) {
                $ratingValue = (float)$rating;
                Cache::put($cacheKey, $ratingValue, self::GOOGLE_CACHE_DURATION);
                Log::info("Cached Google rating for {$businessName}: {$ratingValue}");
                return $ratingValue;
            }

            Log::info("No rating found for business: {$businessName}");
            Cache::put($cacheKey, 'false', self::GOOGLE_CACHE_FAILURE_DURATION);
            return null;
        } catch (\Throwable $e) {
            Log::error("Google API exception for business: {$businessName} - " . $e->getMessage());
            Cache::put($cacheKey, 'false', self::GOOGLE_CACHE_FAILURE_DURATION);
            return null;
        }
    }

    private function getGoogleCacheKey(string $businessName): string
    {
        return 'google_rating_' . md5(strtolower(trim($businessName)));
    }

    private function getManualRisk(int $userId): array
    {
        $riskScore = RiskScore::where('user_id', $userId)->first();
        if ($riskScore && $riskScore->risk_score !== null) {
            return [
                'score' => $riskScore->risk_score,
                'flagged' => true,
                'reason' => $riskScore->reason
            ];
        }

        return ['score' => 0, 'flagged' => false, 'reason' => null];
    }

    /**
     * Clear cached Google rating for a specific business
     */
    public function clearCachedGoogleRating(string $businessName): bool
    {
        $cacheKey = $this->getGoogleCacheKey($businessName);
        return Cache::forget($cacheKey);
    }
}
