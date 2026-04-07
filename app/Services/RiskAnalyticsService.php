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
    private const DEFAULT_CR_ID_SUB_ID_MATCH = 30;
    private const DEFAULT_CR_ID_SUB_ID_EXPIRY = 20;
    private const DEFAULT_CR_ID_SUB_CR_EXPIRY = 20;
    private const DEFAULT_CR_ID_SUB_INDUSTRY = 15;
    private const DEFAULT_CR_ID_SUB_ACTIVITY = 15;
    private const DEFAULT_CR_ID_SUB_TOTAL = 100;

    // ---- POS thresholds ----
    private const DEFAULT_POS_THRESHOLD = 50000.0;

    // ---- Repayment sub-scores / thresholds ----
    private const DEFAULT_REPAYMENT_FEW_THRESHOLD = 2;
    private const DEFAULT_REPAYMENT_SCORE_NO_DELAYS = 20.0;
    private const DEFAULT_REPAYMENT_SCORE_FEW_DELAYS = 15.0;
    private const DEFAULT_REPAYMENT_SCORE_MANY_DELAYS = 5.0;

    // ---- Industry risk map ----
    private const INDUSTRY_RISK_MAP = [
        'low' => 15,
        'medium-low' => 12,
        'medium' => 10,
        'high' => 8,
        'very high' => 5,
    ];

    // ---- Location sub-weights / maps ----
    private const LOCATION_CITY_TIER_MAP = [
        'riyadh' => 6,
        'jeddah' => 6,
        'dammam' => 6,
        'makkah' => 4,
        'madinah' => 4,
        'khobar' => 4,
    ];
    private const DEFAULT_LOCATION_ACTIVITY_MAX = 4.5;
    private const DEFAULT_LOCATION_DEFAULT_RATE_MAX = 4.5;
    private const DEFAULT_LOCATION_SUB_TOTAL_MAX = 15.0;

    // Google API settings
    private const GOOGLE_CACHE_DURATION = 604800;
    private const GOOGLE_CACHE_FAILURE_DURATION = 86400;
    private const GOOGLE_REQUEST_TIMEOUT = 8;
    private const GOOGLE_RETRY_ATTEMPTS = 2;
    private const GOOGLE_RETRY_SLEEP_MS = 150;

    // Cache settings for risk weights
    private const RISK_WEIGHTS_CACHE_DURATION = 3600; // 1 hour
    private const RISK_WEIGHTS_CACHE_KEY = 'risk_weights_cache';

    // Weight properties (will be set per user)
    private array $userWeights = [];

    public function __construct()
    {
        // No initialization here - weights will be loaded per user
    }

    /**
     * Load risk weights for a specific user (with caching)
     */
    private function loadUserWeights(int $userId): void
    {
        // Use cached weights if available
        $cacheKey = self::RISK_WEIGHTS_CACHE_KEY . '_' . $userId;
        $cachedWeights = Cache::get($cacheKey);

        if ($cachedWeights) {
            $this->userWeights = $cachedWeights;
            return;
        }

        // Try to get user-specific weights first, then fall back to global weights
        $userWeights = RiskWeight::where('user_id', $userId)
            ->latest()
            ->first();

        if (!$userWeights) {
            // Fall back to global weights (user_id is null)
            $userWeights = RiskWeight::whereNull('user_id')
                ->latest()
                ->first();
        }

        $this->initializeWeights($userWeights);

        // Cache the weights for this user
        Cache::put($cacheKey, $this->userWeights, self::RISK_WEIGHTS_CACHE_DURATION);
    }

    /**
     * Initialize weights from model or defaults
     */
    private function initializeWeights(?RiskWeight $weights): void
    {
        // Main weights
        $this->userWeights['weightCrIdScore'] = $weights->cr_id ?? self::DEFAULT_WEIGHT_CR_ID;
        $this->userWeights['weightPosScore'] = $weights->pos ?? self::DEFAULT_WEIGHT_POS;
        $this->userWeights['weightRepaymentScore'] = $weights->repayment ?? self::DEFAULT_WEIGHT_REPAYMENT;
        $this->userWeights['weightIndustryScore'] = $weights->industry ?? self::DEFAULT_WEIGHT_INDUSTRY;
        $this->userWeights['weightLocationScore'] = $weights->location ?? self::DEFAULT_WEIGHT_LOCATION;

        // CR / ID sub-weights
        $this->userWeights['crIdSubIdMatch'] = $weights->cr_id_sub_id_match ?? self::DEFAULT_CR_ID_SUB_ID_MATCH;
        $this->userWeights['crIdSubIdExpiry'] = $weights->cr_id_sub_id_expiry ?? self::DEFAULT_CR_ID_SUB_ID_EXPIRY;
        $this->userWeights['crIdSubCrExpiry'] = $weights->cr_id_sub_cr_expiry ?? self::DEFAULT_CR_ID_SUB_CR_EXPIRY;
        $this->userWeights['crIdSubIndustry'] = $weights->cr_id_sub_industry ?? self::DEFAULT_CR_ID_SUB_INDUSTRY;
        $this->userWeights['crIdSubActivity'] = $weights->cr_id_sub_activity ?? self::DEFAULT_CR_ID_SUB_ACTIVITY;
        $this->userWeights['crIdSubTotal'] = $weights->cr_id_sub_total ?? self::DEFAULT_CR_ID_SUB_TOTAL;

        // POS
        $this->userWeights['posThreshold'] = $weights->pos_threshold ?? self::DEFAULT_POS_THRESHOLD;

        // Repayment
        $this->userWeights['repaymentFewThreshold'] = $weights->repayment_few_threshold ?? self::DEFAULT_REPAYMENT_FEW_THRESHOLD;
        $this->userWeights['repaymentScoreNoDelays'] = $weights->repayment_score_no_delays ?? self::DEFAULT_REPAYMENT_SCORE_NO_DELAYS;
        $this->userWeights['repaymentScoreFewDelays'] = $weights->repayment_score_few_delays ?? self::DEFAULT_REPAYMENT_SCORE_FEW_DELAYS;
        $this->userWeights['repaymentScoreManyDelays'] = $weights->repayment_score_many_delays ?? self::DEFAULT_REPAYMENT_SCORE_MANY_DELAYS;

        // Location
        $this->userWeights['locationActivityMax'] = $weights->location_activity_max ?? self::DEFAULT_LOCATION_ACTIVITY_MAX;
        $this->userWeights['locationDefaultRateMax'] = $weights->location_default_rate_max ?? self::DEFAULT_LOCATION_DEFAULT_RATE_MAX;
        $this->userWeights['locationSubTotalMax'] = $weights->location_sub_total_max ?? self::DEFAULT_LOCATION_SUB_TOTAL_MAX;
    }

    /**
     * Preload weights for multiple users (optimized for batch processing)
     */
    private function preloadUsersWeights(array $userIds): void
    {
        $userIds = array_unique($userIds);
        $uncachedUserIds = [];

        // Check cache for each user
        foreach ($userIds as $userId) {
            $cacheKey = self::RISK_WEIGHTS_CACHE_KEY . '_' . $userId;
            if (!Cache::has($cacheKey)) {
                $uncachedUserIds[] = $userId;
            }
        }

        // If all are cached, we're done
        if (empty($uncachedUserIds)) {
            return;
        }

        // Fetch all uncached user weights in one query
        $userSpecificWeights = RiskWeight::whereIn('user_id', $uncachedUserIds)
            ->orderBy('user_id')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(function ($weights) {
                return $weights->first(); // Get latest for each user
            });

        // Fetch global weights as fallback
        $globalWeights = RiskWeight::whereNull('user_id')
            ->latest()
            ->first();

        // Cache results for each user
        foreach ($uncachedUserIds as $userId) {
            $userWeight = $userSpecificWeights->get($userId) ?? $globalWeights;
            $tempWeights = [];

            // Initialize temporary weights array
            $this->initializeWeightsForCaching($userWeight, $tempWeights);

            Cache::put(
                self::RISK_WEIGHTS_CACHE_KEY . '_' . $userId,
                $tempWeights,
                self::RISK_WEIGHTS_CACHE_DURATION
            );
        }
    }

    /**
     * Helper method for initializing weights for caching
     */
    private function initializeWeightsForCaching(?RiskWeight $weights, array &$targetArray): void
    {
        // Main weights
        $targetArray['weightCrIdScore'] = $weights->cr_id ?? self::DEFAULT_WEIGHT_CR_ID;
        $targetArray['weightPosScore'] = $weights->pos ?? self::DEFAULT_WEIGHT_POS;
        $targetArray['weightRepaymentScore'] = $weights->repayment ?? self::DEFAULT_WEIGHT_REPAYMENT;
        $targetArray['weightIndustryScore'] = $weights->industry ?? self::DEFAULT_WEIGHT_INDUSTRY;
        $targetArray['weightLocationScore'] = $weights->location ?? self::DEFAULT_WEIGHT_LOCATION;

        // CR / ID sub-weights
        $targetArray['crIdSubIdMatch'] = $weights->cr_id_sub_id_match ?? self::DEFAULT_CR_ID_SUB_ID_MATCH;
        $targetArray['crIdSubIdExpiry'] = $weights->cr_id_sub_id_expiry ?? self::DEFAULT_CR_ID_SUB_ID_EXPIRY;
        $targetArray['crIdSubCrExpiry'] = $weights->cr_id_sub_cr_expiry ?? self::DEFAULT_CR_ID_SUB_CR_EXPIRY;
        $targetArray['crIdSubIndustry'] = $weights->cr_id_sub_industry ?? self::DEFAULT_CR_ID_SUB_INDUSTRY;
        $targetArray['crIdSubActivity'] = $weights->cr_id_sub_activity ?? self::DEFAULT_CR_ID_SUB_ACTIVITY;
        $targetArray['crIdSubTotal'] = $weights->cr_id_sub_total ?? self::DEFAULT_CR_ID_SUB_TOTAL;

        // POS
        $targetArray['posThreshold'] = $weights->pos_threshold ?? self::DEFAULT_POS_THRESHOLD;

        // Repayment
        $targetArray['repaymentFewThreshold'] = $weights->repayment_few_threshold ?? self::DEFAULT_REPAYMENT_FEW_THRESHOLD;
        $targetArray['repaymentScoreNoDelays'] = $weights->repayment_score_no_delays ?? self::DEFAULT_REPAYMENT_SCORE_NO_DELAYS;
        $targetArray['repaymentScoreFewDelays'] = $weights->repayment_score_few_delays ?? self::DEFAULT_REPAYMENT_SCORE_FEW_DELAYS;
        $targetArray['repaymentScoreManyDelays'] = $weights->repayment_score_many_delays ?? self::DEFAULT_REPAYMENT_SCORE_MANY_DELAYS;

        // Location
        $targetArray['locationActivityMax'] = $weights->location_activity_max ?? self::DEFAULT_LOCATION_ACTIVITY_MAX;
        $targetArray['locationDefaultRateMax'] = $weights->location_default_rate_max ?? self::DEFAULT_LOCATION_DEFAULT_RATE_MAX;
        $targetArray['locationSubTotalMax'] = $weights->location_sub_total_max ?? self::DEFAULT_LOCATION_SUB_TOTAL_MAX;
    }

    /**
     * Apply weight overrides for a specific calculation
     */
    private function applyWeightOverrides(array $overrides): void
    {
        $mainWeightMap = [
            'cr_id' => 'weightCrIdScore',
            'pos' => 'weightPosScore',
            'repayment' => 'weightRepaymentScore',
            'industry' => 'weightIndustryScore',
            'location' => 'weightLocationScore',
        ];

        foreach ($overrides as $key => $value) {
            if (isset($mainWeightMap[$key])) {
                $this->userWeights[$mainWeightMap[$key]] = (float) $value;
            }
        }
    }

    // --------------------------------------------------------------------------------
    // ---- PUBLIC METHODS ----
    // --------------------------------------------------------------------------------

    /**
     * Calculate risk object for a single user
     */
    public function calculateForUser(User $user, array $weightOverrides = []): object
    {
        // Load user-specific weights
        $this->loadUserWeights($user->id);

        // Apply any weight overrides for this calculation
        if (!empty($weightOverrides)) {
            $this->applyWeightOverrides($weightOverrides);
        }

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
     * Calculate risks for multiple users (optimized with preloading)
     */
    public function calculateForUsers($users, array $weightOverrides = []): EloquentCollection
    {
        $userCollection = collect($users);

        // Preload weights for all users (optimized single query)
        $userIds = $userCollection->pluck('id')->toArray();
        $this->preloadUsersWeights($userIds);

        // Extract business names for Google prefetching
        $businessNames = $userCollection->map(function ($user) {
            $userData = $this->getUserData($user);
            $decodedCrData = $this->parseCrData($userData['crData']);
            return $decodedCrData['name'] ?? $userData['businessName'];
        })->filter()->unique()->values()->all();

        // Prefetch Google ratings
        if (!empty($businessNames)) {
            $this->prefetchGoogleRatings($businessNames);
        }

        // Calculate for each user (will use cached weights)
        $mapped = $userCollection->map(function ($user) use ($weightOverrides) {
            // Load the pre-cached weights for this user
            $cacheKey = self::RISK_WEIGHTS_CACHE_KEY . '_' . $user->id;
            $this->userWeights = Cache::get($cacheKey);

            // Apply any weight overrides for this calculation
            if (!empty($weightOverrides)) {
                $this->applyWeightOverrides($weightOverrides);
            }

            return $this->calculateForUserInternal($user);
        });

        return new EloquentCollection($mapped->values()->all());
    }

    /**
     * Internal calculation method (assumes weights are already loaded)
     */
    private function calculateForUserInternal(User $user): object
    {
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

        $apiKey = config('services.google.places_api_key'); // [PHASE-5]
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

    /**
     * Clear cached weights for a specific user
     */
    public function clearCachedWeights(int $userId): bool
    {
        return Cache::forget(self::RISK_WEIGHTS_CACHE_KEY . '_' . $userId);
    }

    // --------------------------------------------------------------------------------
    // ---- PRIVATE HELPERS: DATA FETCH/PREP ----
    // --------------------------------------------------------------------------------

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
        $crIdScore = min($crIdRaw / $this->userWeights['crIdSubTotal'], 1) * $this->userWeights['weightCrIdScore'];

        // Use the dynamic posThreshold
        $posScore = min((float) $userData['monthlyPos'] / $this->userWeights['posThreshold'], 1) * $this->userWeights['weightPosScore'];

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

    private function calculateIdMatchScore(?string $idNumber, array $crData): int
    {
        $ownerId = $crData['parties'][0]['identity']['id'] ?? null;

        if (!$idNumber || !$ownerId) return 0;
        if ($idNumber === $ownerId) return $this->userWeights['crIdSubIdMatch'];
        if (str_contains($ownerId, $idNumber) || str_contains($idNumber, $ownerId)) return (int) ($this->userWeights['crIdSubIdMatch'] / 2);
        return 0;
    }

    private function calculateIdExpiryScore(User $user): int
    {
        if (!$user->iqama_expiry) return 0;
        $diff = now()->diffInMonths($user->iqama_expiry, false);
        return match (true) {
            $diff >= 0 => $this->userWeights['crIdSubIdExpiry'],
            $diff >= -3 => (int) ($this->userWeights['crIdSubIdExpiry'] / 2),
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
                $diff >= 0 => $this->userWeights['crIdSubCrExpiry'],
                $diff >= -3 => (int) ($this->userWeights['crIdSubCrExpiry'] / 2),
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
        return count($crActivities) > 0 ? $this->userWeights['crIdSubActivity'] : 0;
    }

    private function calculateRepaymentScore(User $user): array
    {
        $isMerchant = $user->user_type === 'merchant';
        $delays = $user->transactions()
            ->where('payment_status', 'late')
            ->where($isMerchant ? 'seller_id' : 'user_id', $user->id)
            ->count();

        $score = match (true) {
            $delays === 0 => $this->userWeights['repaymentScoreNoDelays'],
            $delays <= $this->userWeights['repaymentFewThreshold'] => $this->userWeights['repaymentScoreFewDelays'],
            default => $this->userWeights['repaymentScoreManyDelays'],
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

        return ['industry' => $industry, 'industry_score' => $industryScore];
    }

    private function calculateLocationScore(array $crData): array
    {
        $city = strtolower($crData['headquarterCityName'] ?? '');
        $cityTiers = self::LOCATION_CITY_TIER_MAP;

        $tierScore = $cityTiers[$city] ?? 2;

        $businessCount = Cache::get("business_density_{$city}", 0);
        $activityScore = match (true) {
            $businessCount >= 500 => $this->userWeights['locationActivityMax'],
            $businessCount >= 100 => 3,
            default => 1
        };

        $defaultRate = Cache::get("default_rate_{$city}", 0);
        $defaultScore = match (true) {
            $defaultRate <= 5 => $this->userWeights['locationDefaultRateMax'],
            $defaultRate <= 10 => 2,
            default => 0
        };

        // scale using the current location main weight and dynamic sub-total max
        $scale = $this->userWeights['weightLocationScore'] / $this->userWeights['locationSubTotalMax'];
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
