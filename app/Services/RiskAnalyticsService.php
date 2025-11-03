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
    // --------------------------------------------------------------------------------
    // ---- CONSTANTS (Used as defaults if no RiskWeight model is present/field null) ----
    // --------------------------------------------------------------------------------

    // ---- Main default weights (visible defaults at top) ----
    private const DEFAULT_WEIGHT_CR_ID = 25.0;
    private const DEFAULT_WEIGHT_POS = 25.0;
    private const DEFAULT_WEIGHT_REPAYMENT = 20.0;
    private const DEFAULT_WEIGHT_INDUSTRY = 15.0;
    private const DEFAULT_WEIGHT_LOCATION = 10.0;

    // ---- CR / ID sub-weights (raw points) ----
    // These sum to CR_ID_SUB_TOTAL (100)
    private const DEFAULT_CR_ID_SUB_ID_MATCH = 30;     // id match max raw points
    private const DEFAULT_CR_ID_SUB_ID_EXPIRY = 20;    // id expiry max raw points
    private const DEFAULT_CR_ID_SUB_CR_EXPIRY = 20;    // cr expiry max raw points
    private const DEFAULT_CR_ID_SUB_INDUSTRY = 15;     // industry (as part of CR/ID) max raw points
    private const DEFAULT_CR_ID_SUB_ACTIVITY = 15;     // activity presence max raw points
    private const DEFAULT_CR_ID_SUB_TOTAL = 100;       // sum of the above

    // ---- POS thresholds ----
    private const DEFAULT_POS_THRESHOLD = 50000.0;     // monthly POS revenue threshold used to get full POS weight

    // ---- Repayment sub-scores / thresholds ----
    private const DEFAULT_REPAYMENT_FEW_THRESHOLD = 2;                 // <= this many late payments considered "few"
    private const DEFAULT_REPAYMENT_SCORE_NO_DELAYS = 20.0;
    private const DEFAULT_REPAYMENT_SCORE_FEW_DELAYS = 15.0;
    private const DEFAULT_REPAYMENT_SCORE_MANY_DELAYS = 5.0;

    // ---- Industry risk map (maps risk level -> numeric score) ----
    private const INDUSTRY_RISK_MAP = [
        'low' => 15,
        'medium-low' => 12,
        'medium' => 10,
        'high' => 8,
        'very high' => 5,
    ];

    // ---- Location sub-weights / maps ----
    // City tier map (raw - hardcoded as it is not a weight)
    private const LOCATION_CITY_TIER_MAP = [
        'riyadh' => 6,
        'jeddah' => 6,
        'dammam' => 6,
        'makkah' => 4,
        'madinah' => 4,
        'khobar' => 4,
    ];
    private const DEFAULT_LOCATION_ACTIVITY_MAX = 4.5;     // business density component max
    private const DEFAULT_LOCATION_DEFAULT_RATE_MAX = 4.5;  // default rate component max
    private const DEFAULT_LOCATION_SUB_TOTAL_MAX = 15.0;    // CITY_TIER(<=6) + ACTIVITY(<=4.5) + DEFAULT_RATE(<=4.5)

    // Google API settings (Hardcoded as they are infrastructure/external service constants)
    private const GOOGLE_CACHE_DURATION = 604800; // 7 days
    private const GOOGLE_CACHE_FAILURE_DURATION = 86400; // 1 day
    private const GOOGLE_REQUEST_TIMEOUT = 8; // seconds
    private const GOOGLE_RETRY_ATTEMPTS = 2;
    private const GOOGLE_RETRY_SLEEP_MS = 150; // milliseconds

    // --------------------------------------------------------------------------------
    // ---- CONFIGURABLE PROPERTIES (Loaded from RiskWeight or defaults) ----
    // --------------------------------------------------------------------------------

    // Main weights
    private float $weightCrIdScore;
    private float $weightPosScore;
    private float $weightRepaymentScore;
    private float $weightIndustryScore;
    private float $weightLocationScore;

    // CR / ID sub-weights
    private int $crIdSubIdMatch;
    private int $crIdSubIdExpiry;
    private int $crIdSubCrExpiry;
    private int $crIdSubIndustry;
    private int $crIdSubActivity;
    private int $crIdSubTotal;

    // POS
    private float $posThreshold;

    // Repayment
    private int $repaymentFewThreshold;
    private float $repaymentScoreNoDelays;
    private float $repaymentScoreFewDelays;
    private float $repaymentScoreManyDelays;

    // Location
    private float $locationActivityMax;
    private float $locationDefaultRateMax;
    private float $locationSubTotalMax;

    // --------------------------------------------------------------------------------
    // ---- CONSTRUCTOR / INITIALIZATION ----
    // --------------------------------------------------------------------------------

    public function __construct()
    {
        // Initialize with all defaults first
        $this->initializeDefaults();

        // Fetch latest weights from RiskWeight model and override defaults when available
        $latestWeights = RiskWeight::latest()->first();

        if ($latestWeights) {
            $this->applyModelWeights($latestWeights);
        }
    }

    /**
     * Initialize all properties with their default constant values.
     */
    private function initializeDefaults(): void
    {
        // Main weights
        $this->weightCrIdScore = self::DEFAULT_WEIGHT_CR_ID;
        $this->weightPosScore = self::DEFAULT_WEIGHT_POS;
        $this->weightRepaymentScore = self::DEFAULT_WEIGHT_REPAYMENT;
        $this->weightIndustryScore = self::DEFAULT_WEIGHT_INDUSTRY;
        $this->weightLocationScore = self::DEFAULT_WEIGHT_LOCATION;

        // CR / ID sub-weights
        $this->crIdSubIdMatch = self::DEFAULT_CR_ID_SUB_ID_MATCH;
        $this->crIdSubIdExpiry = self::DEFAULT_CR_ID_SUB_ID_EXPIRY;
        $this->crIdSubCrExpiry = self::DEFAULT_CR_ID_SUB_CR_EXPIRY;
        $this->crIdSubIndustry = self::DEFAULT_CR_ID_SUB_INDUSTRY;
        $this->crIdSubActivity = self::DEFAULT_CR_ID_SUB_ACTIVITY;
        $this->crIdSubTotal = self::DEFAULT_CR_ID_SUB_TOTAL;

        // POS
        $this->posThreshold = self::DEFAULT_POS_THRESHOLD;

        // Repayment
        $this->repaymentFewThreshold = self::DEFAULT_REPAYMENT_FEW_THRESHOLD;
        $this->repaymentScoreNoDelays = self::DEFAULT_REPAYMENT_SCORE_NO_DELAYS;
        $this->repaymentScoreFewDelays = self::DEFAULT_REPAYMENT_SCORE_FEW_DELAYS;
        $this->repaymentScoreManyDelays = self::DEFAULT_REPAYMENT_SCORE_MANY_DELAYS;

        // Location
        $this->locationActivityMax = self::DEFAULT_LOCATION_ACTIVITY_MAX;
        $this->locationDefaultRateMax = self::DEFAULT_LOCATION_DEFAULT_RATE_MAX;
        $this->locationSubTotalMax = self::DEFAULT_LOCATION_SUB_TOTAL_MAX;
    }

    /**
     * Override properties with values from the RiskWeight model.
     */
    private function applyModelWeights(RiskWeight $weights): void
    {
        // Main weights
        $this->weightCrIdScore = $weights->cr_id ?? $this->weightCrIdScore;
        $this->weightPosScore = $weights->pos ?? $this->weightPosScore;
        $this->weightRepaymentScore = $weights->repayment ?? $this->weightRepaymentScore;
        $this->weightIndustryScore = $weights->industry ?? $this->weightIndustryScore;
        $this->weightLocationScore = $weights->location ?? $this->weightLocationScore;

        // CR / ID sub-weights
        $this->crIdSubIdMatch = $weights->cr_id_sub_id_match ?? $this->crIdSubIdMatch;
        $this->crIdSubIdExpiry = $weights->cr_id_sub_id_expiry ?? $this->crIdSubIdExpiry;
        $this->crIdSubCrExpiry = $weights->cr_id_sub_cr_expiry ?? $this->crIdSubCrExpiry;
        $this->crIdSubIndustry = $weights->cr_id_sub_industry ?? $this->crIdSubIndustry;
        $this->crIdSubActivity = $weights->cr_id_sub_activity ?? $this->crIdSubActivity;
        $this->crIdSubTotal = $weights->cr_id_sub_total ?? $this->crIdSubTotal;

        // POS
        $this->posThreshold = $weights->pos_threshold ?? $this->posThreshold;

        // Repayment
        $this->repaymentFewThreshold = $weights->repayment_few_threshold ?? $this->repaymentFewThreshold;
        $this->repaymentScoreNoDelays = $weights->repayment_score_no_delays ?? $this->repaymentScoreNoDelays;
        $this->repaymentScoreFewDelays = $weights->repayment_score_few_delays ?? $this->repaymentScoreFewDelays;
        $this->repaymentScoreManyDelays = $weights->repayment_score_many_delays ?? $this->repaymentScoreManyDelays;

        // Location
        $this->locationActivityMax = $weights->location_activity_max ?? $this->locationActivityMax;
        $this->locationDefaultRateMax = $weights->location_default_rate_max ?? $this->locationDefaultRateMax;
        $this->locationSubTotalMax = $weights->location_sub_total_max ?? $this->locationSubTotalMax;
    }

    // --------------------------------------------------------------------------------
    // ---- PUBLIC METHODS ----
    // --------------------------------------------------------------------------------

    /**
     * Calculate risk object for a single user
     */
    public function calculateForUser(User $user, array $weights = []): object
    {
        // Override weights if provided (only main weights can be overridden here)
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
     * Prefetch Google ratings (synchronous chunked fetch).
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

    /**
     * Clear cached Google rating for a specific business
     */
    public function clearCachedGoogleRating(string $businessName): bool
    {
        $cacheKey = $this->getGoogleCacheKey($businessName);
        return Cache::forget($cacheKey);
    }

    // --------------------------------------------------------------------------------
    // ---- PRIVATE HELPERS: DATA FETCH/PREP ----
    // --------------------------------------------------------------------------------

    /**
     * Apply only main weights overrides (used in calculateForUser)
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
     * Parse CR JSON-like data into an array safely
     */
    private function parseCrData($crData): array
    {
        if (is_string($crData)) {
            return json_decode($crData, true) ?: [];
        }
        return is_array($crData) ? $crData : [];
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

    // --------------------------------------------------------------------------------
    // ---- PRIVATE HELPERS: SCORE CALCULATION ----
    // --------------------------------------------------------------------------------

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

        // Use the dynamic sub-total for the division
        $crIdRaw = $idMatchScore + $idExpiryScore + $crExpiryScore + $industryData['industry_score'] + $activityScore;
        $crIdScore = min($crIdRaw / $this->crIdSubTotal, 1) * $this->weightCrIdScore;

        // Use the dynamic posThreshold
        $posScore = min((float) $userData['monthlyPos'] / $this->posThreshold, 1) * $this->weightPosScore;

        $repaymentData = $this->calculateRepaymentScore($user);
        $locationData = $this->calculateLocationScore($crData);

        // Use cached rating only (do not fetch here)
        $businessNameForGoogle = $crData['name'] ?? $userData['businessName'];
        $googleRating = $this->calculateGoogleScore($businessNameForGoogle);

        $manualRisk = $this->getManualRisk($user->id);

        $totalScore = $crIdScore + $posScore +
            $repaymentData['score'] +
            $industryData['industry_score'] + // Note: industry score is currently a raw score, not weighted by $this->weightIndustryScore
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

    private function calculateIdMatchScore(?string $idNumber, array $crData): int
    {
        $ownerId = $crData['parties'][0]['identity']['id'] ?? null;

        if (!$idNumber || !$ownerId) return 0;
        if ($idNumber === $ownerId) return $this->crIdSubIdMatch;
        if (str_contains($ownerId, $idNumber) || str_contains($idNumber, $ownerId)) return (int) ($this->crIdSubIdMatch / 2);
        return 0;
    }

    private function calculateIdExpiryScore(User $user): int
    {
        if (!$user->iqama_expiry) return 0;
        $diff = now()->diffInMonths($user->iqama_expiry, false);
        return match (true) {
            $diff >= 0 => $this->crIdSubIdExpiry,
            $diff >= -3 => (int) ($this->crIdSubIdExpiry / 2),
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
                $diff >= 0 => $this->crIdSubCrExpiry,
                $diff >= -3 => (int) ($this->crIdSubCrExpiry / 2),
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
        return count($crActivities) > 0 ? $this->crIdSubActivity : 0;
    }

    private function calculateRepaymentScore(User $user): array
    {
        $isMerchant = $user->user_type === 'merchant';
        $delays = $user->transactions()
            ->where('payment_status', 'late')
            ->where($isMerchant ? 'seller_id' : 'user_id', $user->id)
            ->count();

        $score = match (true) {
            $delays === 0 => $this->repaymentScoreNoDelays,
            $delays <= $this->repaymentFewThreshold => $this->repaymentScoreFewDelays,
            default => $this->repaymentScoreManyDelays,
        };

        return ['delays' => $delays, 'score' => $score];
    }

    private function calculateIndustryScore(User $user): array
    {
        $isMerchant = $user->user_type === 'merchant';
        $relation = $isMerchant ? 'merchant' : 'customer';

        $businessType = $user->$relation->businessType ?? null;
        $industryRisk = strtolower($businessType->risk_level ?? 'medium');

        $industryScore = self::INDUSTRY_RISK_MAP[$industryRisk] ?? self::INDUSTRY_RISK_MAP['medium'];
        $industry = $businessType->name ?? 'Unknown Industry';

        // NOTE: The original code used the CR/ID sub-weight for industry.
        // I will keep the raw score here and then in calculateAllScores,
        // it is added to crIdRaw (which is weird) and the final score, but it's part of CR/ID raw
        // in the original logic which implies it's a fixed value, not a percentage of the total weight.
        // For consistency with the original code structure:
        return ['industry' => $industry, 'industry_score' => $industryScore];
    }

    private function calculateLocationScore(array $crData): array
    {
        $city = strtolower($crData['headquarterCityName'] ?? '');
        $cityTiers = self::LOCATION_CITY_TIER_MAP;

        $tierScore = $cityTiers[$city] ?? 2;

        $businessCount = Cache::get("business_density_{$city}", 0);
        $activityScore = match (true) {
            $businessCount >= 500 => $this->locationActivityMax,
            $businessCount >= 100 => 3,
            default => 1
        };

        $defaultRate = Cache::get("default_rate_{$city}", 0);
        $defaultScore = match (true) {
            $defaultRate <= 5 => $this->locationDefaultRateMax,
            $defaultRate <= 10 => 2,
            default => 0
        };

        // scale using the current location main weight and dynamic sub-total max
        $scale = $this->weightLocationScore / $this->locationSubTotalMax;
        $totalScore = round(($tierScore + $activityScore + $defaultScore) * $scale, 2);

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
}
