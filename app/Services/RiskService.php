<?php

namespace App\Services;

use App\Models\BusinessCategory;
use App\Models\City;
use App\Models\Customer;
use App\Models\Merchant; // Add Merchant model
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Models\RiskWeight; // <- added
use App\Models\SimahReport;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RiskService
{
    /**
     * Default policy variables (change here if you want different thresholds).
     * You can override these by setting $this->policy[...] before calling functions.
     */
    protected array $policy = [
        // CHS defaults
        'default_chs' => 60,

        // BCS thresholds for turnover_score (SAR) - tune as per policy
        'bcs_th_low'  => 5_000,
        'bcs_th_mid'  => 50_000,
        'bcs_th_high' => 200_000,

        // BCS default when no banking data
        'default_bcs' => 60,

        // BES default when no history
        'default_bes' => 70,

        // CHS/BCS defaults for missing data flags
        'default_no_bureau_chs' => 60,
        'default_no_banking_bcs' => 60,

        // CAF defaults
        'default_caf' => 1.0,
    ];

    /**
     * Active weights (populated per-request by loadWeights())
     * Uses RiskWeight values if provided, otherwise RiskWeight::getDefaultWeights()
     */
    protected array $weights = [];

    /**
     * Public entry that returns everything
     * @param int|Customer|Merchant $customerOrMerchantOrId
     * @param string $type 'customer' or 'merchant'
     * @param int|null $weightUserId Optional: user_id used to load RiskWeight (dynamic weights)
     * @return array
     */
    public function analyzeCustomer($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): array
    {
        // load weights for this request
        $this->weights = $this->loadWeights($customerOrMerchantOrId->user_id);

        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) {
            return ['error' => ucfirst($type) . ' not found'];
        }

        $flags = [];
        $notes = [];
        $components = [];

        // LPS
        $lpsRes = $this->computeLPS($entity, $type);
        $lps = $lpsRes['score'];
        $notes['lps'] = $lpsRes['notes'];
        $components['lps'] = $lpsRes['components'] ?? [];
        $flags = array_merge($flags, $lpsRes['flags'] ?? []);

        // CHS
        $chsRes = $this->computeCHS($entity, $type);
        $chs = $chsRes['score'];
        $notes['chs'] = $chsRes['notes'];
        $components['chs'] = $chsRes['components'] ?? [];
        $flags = array_merge($flags, $chsRes['flags'] ?? []);

        // BCS
        $bcsRes = $this->computeBCS($entity, $type);
        $bcs = $bcsRes['score'];
        $notes['bcs'] = $bcsRes['notes'];
        $components['bcs'] = $bcsRes['components'] ?? [];
        $flags = array_merge($flags, $bcsRes['flags'] ?? []);

        // BPS
        $bpsRes = $this->computeBPS($entity, $type);
        $bps = $bpsRes['score'];
        $notes['bps'] = $bpsRes['notes'];
        $components['bps'] = $bpsRes['components'] ?? [];

        // BES
        $besRes = $this->computeBES($entity, $type);
        $bes = $besRes['score'];
        $notes['bes'] = $besRes['notes'];
        $components['bes'] = $besRes['components'] ?? [];
        $flags = array_merge($flags, $besRes['flags'] ?? []);

        // CAF
        $cafRes = $this->computeCAF($entity, $type);
        $caf = $cafRes['score'] ?? ($cafRes['caf'] ?? $this->policy['default_caf']);
        $notes['caf'] = $cafRes['notes'] ?? '';
        $components['caf'] = $cafRes['components'] ?? ['caf' => $caf];

        // OMRS
        $omrs = $this->computeOMRS(compact('lps', 'chs', 'bcs', 'bps', 'bes', 'caf'));

        return [
            'lps'       => round($lps, 2),
            'chs'       => round($chs, 2),
            'bcs'       => round($bcs, 2),
            'bps'       => round($bps, 2),
            'bes'       => round($bes, 2),
            'caf'       => round($caf, 2),
            'omrs'      => round($omrs, 2),
            'flags'     => array_values(array_unique($flags)),
            'notes'     => $notes,
            'components' => $components,
            'type'      => $type, // Include type in response
            'weights_used' => $this->weights, // helpful for debugging
        ];
    }

    /**
     * Return only Overall Merchant Risk Score (0-100)
     */
    public function getOMRS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->weights = $this->loadWeights($weightUserId);
        $res = $this->analyzeCustomer($customerOrMerchantOrId, $type, $weightUserId);
        return $res['omrs'] ?? 0;
    }

    /**
     * Return only LPS
     */
    public function getLPS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->weights = $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return 0;
        $res = $this->computeLPS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only CHS
     */
    public function getCHS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->weights = $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (!$entity) return $this->policy['default_chs'];

        $res = $this->computeCHS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BCS
     */
    public function getBCS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->weights = $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return $this->policy['default_bcs'];
        $res = $this->computeBCS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BPS
     */
    public function getBPS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->weights = $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return 0;
        $res = $this->computeBPS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BES
     */
    public function getBES($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->weights = $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return $this->policy['default_bes'];
        $res = $this->computeBES($entity, $type);
        return round($res['score'], 2);
    }

    // ---------- Individual compute functions ----------

    /**
     * Compute LPS per spec.
     * Returns [
     *   'score' => float,
     *   'notes' => string,
     *   'flags' => array,
     *   'components' => ['age_score'=>..., 'cr_score'=>..., 'doc_score'=>...]
     * ]
     */
    protected function computeLPS($entity, string $type): array
    {
        $notes = [];
        $flags = [];

        // Use appropriate data source based on type
        if ($type === 'merchant') {
            $crData = json_decode($entity->goverment_data ?? '{}', true) ?? null; // Note: misspelled as 'goverment_data'
        } else {
            $crData = $entity->cr_data ?? null;
        }

        // Determine T_business_months from cr_data.issueDateGregorian or status.confirmationDate.gregorian
        $tMonths = null;
        if (is_array($crData) && !empty($crData)) {
            $dateStr = $crData['issueDateGregorian'] ??
                ($crData['status']['confirmationDate']['gregorian'] ?? null);
            if ($dateStr) {
                try {
                    $start = Carbon::parse($dateStr);
                    $tMonths = $start->diffInMonths(Carbon::now());
                } catch (\Exception $e) {
                    $tMonths = null;
                }
            }
        }

        // CR_valid
        $crValid = false;
        if (is_array($crData) && !empty($crData)) {
            // if crNumber exists and status name is Active -> valid
            $crNumber = $crData['crNumber'] ?? null;
            $statusName = $crData['status']['name'] ?? null;
            if (!empty($crNumber) && strtolower($statusName) === 'active') {
                $crValid = true;
            }
        }

        // Docs completeness: check cr_data and nafath_data presence.
        $docScore = 0;
        $hasCr = !empty($crData);
        $nafath = $entity->nafath_data ?? null;
        $hasNafath = !empty($nafath);
        if ($hasCr && $hasNafath) {
            $docScore = 100;
        } elseif ($hasCr) {
            $docScore = 70;
        } else {
            $docScore = 0;
        }

        // Age_score
        $ageScore = 0;

        if ($tMonths === null) {
            // unknown, assume conservative low value
            $ageScore = 40; // treat as new
            $notes[] = 'T_business_months unknown; assuming low age score.';
            $flags[] = 'no_cr_issue_date';
        } else {
            $T = (int)$tMonths;
            if ($T >= 36) {
                $ageScore = 100;
            } elseif ($T >= 12 && $T < 36) {
                $ageScore = 60 + ($T - 12) * (40 / 24);
            } else { // T < 12
                $ageScore = 40 * ($T / 12);
            }
        }

        // CR score
        $crScore = $crValid ? 100 : 0;

        // LPS formula uses dynamic weights from RiskWeight (lps_age_weight, lps_cr_weight, lps_doc_weight)
        $ageW = $this->weights['lps_age_weight'] ?? 40;
        $crW   = $this->weights['lps_cr_weight'] ?? 30;
        $docW  = $this->weights['lps_doc_weight'] ?? 30;
        $sum = ($ageW + $crW + $docW) ?: 1;
        $ageFrac = $ageW / $sum;
        $crFrac = $crW / $sum;
        $docFrac = $docW / $sum;

        $lps = $ageFrac * $ageScore + $crFrac * $crScore + $docFrac * $docScore;

        $notes[] = "Age_score={$ageScore}, CR_score={$crScore}, Doc_score={$docScore}; weights(age,cr,doc)=({$ageW},{$crW},{$docW})";

        return [
            'score' => (float)$lps,
            'notes' => implode('; ', $notes),
            'flags' => $flags,
            'components' => [
                'age_score' => round($ageScore, 2),
                'cr_score'  => round($crScore, 2),
                'doc_score' => round($docScore, 2),
                't_business_months' => $tMonths,
                'used_weights' => [
                    'lps_age_weight' => $ageW,
                    'lps_cr_weight' => $crW,
                    'lps_doc_weight' => $docW,
                ],
            ],
        ];
    }

    /**
     * Compute CHS per spec using SIMAH if available.
     * Returns [
     *   'score' => float,
     *   'notes' => string,
     *   'flags' => array,
     *   'components' => array
     * ]
     */
    protected function computeCHS($entity, string $type): array
    {
        $flags = [];
        $notes = [];

        // components placeholders (will fill from SIMAH if possible)
        $components = [
            'bureau_rating_score' => null,
            'dpd_score' => null,
            'max_dpd_12m' => null,
            'raw_report' => null, // small copy for debugging (optional)
        ];

        try {
            // get user id for lookups
            $userId = $this->getUserId($entity, $type);
            if ($userId) {
                // latest SIMAH consumerScore report for this user
                $simah = SimahReport::where('user_id', $userId)
                    ->where('type', 'consumerScore')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($simah && !empty($simah->report_json)) {
                    $report = is_array($simah->report_json) ? $simah->report_json : json_decode($simah->report_json, true);
                    $components['raw_report'] = $report;

                    // typical path: data -> score -> [ { score: <number>, scoreCard: {...} } ]
                    $scoreEntry = $report['data']['score'][0] ?? null;
                    $bureauScore = $scoreEntry['score'] ?? null;

                    if (is_numeric($bureauScore)) {
                        // use SIMAH score directly (assumed 0..100)
                        $chs = (float)$bureauScore;
                        $components['bureau_rating_score'] = $chs;
                        $notes[] = 'SIMAH consumerScore used from latest report.';
                        // detect non-scorable indicator
                        $scoreCardCode = $scoreEntry['scoreCard']['scoreCardCode'] ?? null;
                        if ($scoreCardCode === 'NS' || $chs === 0) {
                            $flags[] = 'simah_non_scorable';
                        }

                        // try to extract some DPD / delinquency info if present
                        // common fields: summaryInfo -> summCurrentDelinquentBalance / summDefaults etc.
                        $summary = $report['data']['summaryInfo'] ?? null;
                        if (is_array($summary)) {
                            // If there is a field that looks like a max DPD in 12m, pick it (best-effort)
                            if (isset($summary['summCurrentDelinquentBalance'])) {
                                $components['max_dpd_12m'] = $summary['summCurrentDelinquentBalance'];
                            } elseif (isset($summary['summDefaults'])) {
                                $components['max_dpd_12m'] = $summary['summDefaults'];
                            }
                        }

                        // dpd_score not available directly in many SIMAH payloads — leave null unless you compute mapping
                        $components['dpd_score'] = null;

                        return [
                            'score' => $chs,
                            'notes' => implode('; ', $notes),
                            'flags' => array_values(array_unique($flags)),
                            'components' => $components,
                        ];
                    }

                    // if score exists but not numeric, add flag and fallthrough to default
                    $flags[] = 'simah_score_unparseable';
                    $notes[] = 'SIMAH report found but score not numeric.';
                } else {
                    $notes[] = 'No SIMAH consumerScore report found for user.';
                    $flags[] = 'no_bureau_data';
                }
            } else {
                $notes[] = 'Unable to resolve user id for SIMAH lookup.';
                $flags[] = 'no_bureau_data';
            }
        } catch (\Throwable $e) {
            Log::warning('RiskService computeCHS: Simah parse/lookup failed: ' . $e->getMessage());
            $notes[] = 'Error reading SIMAH report: ' . $e->getMessage();
            $flags[] = 'simah_lookup_error';
        }

        // fallback: return default CHS with flags/notes
        $chs = (float)$this->policy['default_chs'];
        $notes[] = 'Returning default CHS.';
        $flags[] = 'no_bureau_data';

        return [
            'score' => $chs,
            'notes' => implode('; ', $notes),
            'flags' => array_values(array_unique($flags)),
            'components' => $components,
        ];
    }


    /**
     * Compute BCS per spec using Orders as proxy for open-banking.
     * Returns [score, notes, flags, components]
     */
    protected function computeBCS($entity, string $type): array
    {
        $flags = [];
        $notes = [];

        // load component weights for BCS
        $turnoverW = $this->weights['bcs_turnover_weight'] ?? 35;
        $volatilityW = $this->weights['bcs_volatility_weight'] ?? 25;
        $returnedW = $this->weights['bcs_returned_weight'] ?? 25;
        $balanceW = $this->weights['bcs_balance_weight'] ?? 15;
        $sumW = ($turnoverW + $volatilityW + $returnedW + $balanceW) ?: 1;
        $turnoverFrac = $turnoverW / $sumW;
        $volFrac = $volatilityW / $sumW;
        $returnedFrac = $returnedW / $sumW;
        $balanceFrac = $balanceW / $sumW;

        // Get user ID based on entity type
        $userId = $this->getUserId($entity, $type);

        // Attempt to compute Avg_monthly_turnover from Order data
        try {
            // For merchants, we need to get orders where they are the seller
            if ($type === 'merchant') {
                $months = 6;
                // You might need to adjust this based on your Order model structure
                $recentOrders = Order::where('seller_id', $userId)
                    ->where('created_at', '>=', Carbon::now()->subMonths($months))
                    ->get();

                // Group by month and calculate monthly revenue
                $monthlyRevenue = [];
                foreach ($recentOrders as $order) {
                    $month = $order->created_at->format('Y-m');
                    if (!isset($monthlyRevenue[$month])) {
                        $monthlyRevenue[$month] = 0;
                    }
                    $monthlyRevenue[$month] += $order->grand_total ?? 0;
                }

                $revenues = array_values($monthlyRevenue);
                $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0;
            } else {
                // For customers, use the existing method if present
                $months = 6;
                if (method_exists(Order::class, 'getRevenueStreams')) {
                    $revenueData = Order::getRevenueStreams($months);
                    $revenues = $revenueData['revenue'] ?? [];
                } else {
                    // fallback to using orders by user
                    $recentOrders = Order::where('user_id', $userId)
                        ->where('created_at', '>=', Carbon::now()->subMonths($months))
                        ->get();
                    $monthlyRevenue = [];
                    foreach ($recentOrders as $order) {
                        $month = $order->created_at->format('Y-m');
                        if (!isset($monthlyRevenue[$month])) {
                            $monthlyRevenue[$month] = 0;
                        }
                        $monthlyRevenue[$month] += $order->grand_total ?? 0;
                    }
                    $revenues = array_values($monthlyRevenue);
                }

                $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0;
            }

            // volatility: standard deviation / mean
            $mean = $avgMonthly;
            $variance = 0;
            if (!empty($revenues)) {
                foreach ($revenues as $v) {
                    $variance += pow(($v - $mean), 2);
                }
                $variance = $variance / count($revenues);
                $stddev = sqrt($variance);
                $volatility = $mean > 0 ? ($stddev / $mean) : 0;
            } else {
                $stddev = 0;
                $volatility = 0;
            }

            $notes[] = "Avg_monthly_turnover={$avgMonthly}, volatility={$volatility}";

            // returned_rate (proxy): refunds / orders
            if ($type === 'merchant') {
                $totalOrders = Order::where('seller_id', $userId)->count() ?: 0;
                $refunds = RefundRequest::where('seller_id', $userId)->count() ?: 0;
            } else {
                $totalOrders = Order::where('user_id', $userId)->count() ?: 0;
                $refunds = RefundRequest::where('user_id', $userId)->count() ?: 0;
            }
            $returnedRate = $totalOrders > 0 ? ($refunds / $totalOrders) : 0;

            // min_balance_ratio - we don't have account balance, approximate from orders vs avg turnover
            $minBalanceRatio = $avgMonthly > 0 ? 0.1 : 0.05;

            // Map turnover_score (keep prior thresholds)
            if ($avgMonthly >= $this->policy['bcs_th_high']) {
                $turnoverScore = 100;
            } elseif ($avgMonthly >= $this->policy['bcs_th_mid']) {
                $turnoverScore = 80;
            } elseif ($avgMonthly >= $this->policy['bcs_th_low']) {
                $turnoverScore = 60;
            } else {
                $turnoverScore = 40;
            }

            // Volatility_score mapping
            if ($volatility <= 0.2) {
                $volScore = 100;
            } elseif ($volatility <= 0.5) {
                $volScore = 80;
            } elseif ($volatility <= 1.0) {
                $volScore = 60;
            } else {
                $volScore = 40;
            }

            // Returned rate mapping
            if ($returnedRate == 0) {
                $returnedScore = 100;
            } elseif ($returnedRate <= 0.05) {
                $returnedScore = 80;
            } elseif ($returnedRate <= 0.15) {
                $returnedScore = 60;
            } else {
                $returnedScore = 40;
            }

            // Min balance mapping
            if ($minBalanceRatio >= 0.2) {
                $minBalanceScore = 100;
            } elseif ($minBalanceRatio >= 0.1) {
                $minBalanceScore = 80;
            } elseif ($minBalanceRatio >= 0.05) {
                $minBalanceScore = 60;
            } else {
                $minBalanceScore = 40;
            }

            // Compose BCS using dynamic weights
            $bcs = $turnoverFrac * $turnoverScore + $volFrac * $volScore + $returnedFrac * $returnedScore + $balanceFrac * $minBalanceScore;

            $notes[] = "turnoverScore={$turnoverScore}, volScore={$volScore}, returnedScore={$returnedScore}, minBalanceScore={$minBalanceScore}; weights(turnover,volatility,returned,balance)=({$turnoverW},{$volatilityW},{$returnedW},{$balanceW})";

            // If we detect absence of real open-banking info, flag it
            if ($avgMonthly == 0) {
                $flags[] = 'no_banking_data';
                $notes[] = 'Avg monthly turnover computed as 0 — check open-banking integration if you expect data.';
            }

            return [
                'score' => (float)$bcs,
                'notes' => implode('; ', $notes),
                'flags' => $flags,
                'components' => [
                    'avg_monthly_turnover' => round($avgMonthly, 2),
                    'turnover_score' => round($turnoverScore, 2),
                    'volatility' => round($volatility, 4),
                    'volatility_score' => round($volScore, 2),
                    'returned_rate' => round($returnedRate, 4),
                    'returned_score' => round($returnedScore, 2),
                    'min_balance_ratio' => round($minBalanceRatio, 4),
                    'min_balance_score' => round($minBalanceScore, 2),
                    'used_weights' => [
                        'bcs_turnover_weight' => $turnoverW,
                        'bcs_volatility_weight' => $volatilityW,
                        'bcs_returned_weight' => $returnedW,
                        'bcs_balance_weight' => $balanceW,
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('RiskService computeBCS error: ' . $e->getMessage());
            $flags[] = 'no_banking_data';
            return [
                'score' => (float)$this->policy['default_bcs'],
                'notes' => 'Error computing BCS; returning default',
                'flags' => $flags,
                'components' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Compute BPS (sector + region)
     * Returns [score(0-100), notes, components]
     */
    protected function computeBPS($entity, string $type): array
    {
        $notes = [];

        // Sector risk class: check business_category_id
        $categoryIds = $entity->business_category_id;
        if (is_string($categoryIds) && $this->looksLikeJsonArray($categoryIds)) {
            $categoryIds = json_decode($categoryIds, true);
        }
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $categoryIds = array_filter($categoryIds);

        $sectorRiskClass = null;
        if (!empty($categoryIds)) {
            $cats = BusinessCategory::whereIn('id', $categoryIds)->pluck('risk')->filter()->toArray();
            if (!empty($cats)) {
                // choose the highest risk class number (worst)
                $sectorRiskClass = max($cats);
            }
        }
        if ($sectorRiskClass === null) {
            // fallback to neutral risk 3
            $sectorRiskClass = 3;
            $notes[] = 'Sector risk class unknown -> using default 3';
        }

        // Map sector to score
        $mapping = [
            1 => 100,
            2 => 85,
            3 => 70,
            4 => 55,
            5 => 40,
        ];
        $sectorScore = $mapping[$sectorRiskClass] ?? 70;

        // Region risk class: via user
        $regionRiskClass = null;
        $user = $entity->user ?? null;
        if ($user && $user->city_id) {
            $city = City::find($user->city_id);
            if ($city) {
                $regionRiskClass = (int)($city->risk ?? null);
            }
        }
        if ($regionRiskClass === null) {
            $regionRiskClass = 2; // neutral
            $notes[] = 'Region risk unknown -> default to 2';
        }

        $regionMapping = [1 => 100, 2 => 80, 3 => 60];
        $regionScore = $regionMapping[$regionRiskClass] ?? 80;

        // use dynamic weights for bps
        $sectorW = $this->weights['bps_sector_weight'] ?? 70;
        $regionW = $this->weights['bps_region_weight'] ?? 30;
        $sum = ($sectorW + $regionW) ?: 1;
        $sectorFrac = $sectorW / $sum;
        $regionFrac = $regionW / $sum;

        $bps = $sectorFrac * $sectorScore + $regionFrac * $regionScore;
        $notes[] = "sectorScore={$sectorScore}, regionScore={$regionScore}; weights(sector,region)=({$sectorW},{$regionW})";

        return [
            'score' => (float)$bps,
            'notes' => implode('; ', $notes),
            'flags' => [],
            'components' => [
                'sector_risk_class' => $sectorRiskClass,
                'sector_score' => round($sectorScore, 2),
                'region_risk_class' => $regionRiskClass,
                'region_score' => round($regionScore, 2),
                'used_weights' => [
                    'bps_sector_weight' => $sectorW,
                    'bps_region_weight' => $regionW,
                ],
            ],
        ];
    }

    /**
     * Compute BES from ArabianPay (SchedulePayment + Orders).
     * Returns [score, notes, flags, components]
     */
    protected function computeBES($entity, string $type): array
    {
        $flags = [];
        $notes = [];

        // component weights for BES
        $dpdW = $this->weights['bes_dpd_weight'] ?? 40;
        $utilW = $this->weights['bes_utilization_weight'] ?? 25;
        $disputeW = $this->weights['bes_dispute_weight'] ?? 25;
        $trendW = $this->weights['bes_trend_weight'] ?? 10;
        $sumW = ($dpdW + $utilW + $disputeW + $trendW) ?: 1;
        $dpdFrac = $dpdW / $sumW;
        $utilFrac = $utilW / $sumW;
        $disputeFrac = $disputeW / $sumW;
        $trendFrac = $trendW / $sumW;

        $userId = $this->getUserId($entity, $type);

        // DPD_AP_max: max late days from schedule_payments
        if ($type === 'merchant') {
            $dpdMax = SchedulePayment::where('seller_id', $userId)
                ->whereNotNull('late_days')
                ->max('late_days');
        } else {
            $dpdMax = SchedulePayment::where('user_id', $userId)
                ->whereNotNull('late_days')
                ->max('late_days');
        }

        $dpdMax = $dpdMax ?? 0;

        // AP_DPD_score mapping
        if ($dpdMax == 0) {
            $apDpdScore = 100;
        } elseif ($dpdMax <= 30) {
            $apDpdScore = 80;
        } elseif ($dpdMax <= 60) {
            $apDpdScore = 60;
        } elseif ($dpdMax <= 90) {
            $apDpdScore = 40;
        } else {
            $apDpdScore = 20;
        }

        $notes[] = "DPD_AP_max={$dpdMax} -> apDpdScore={$apDpdScore}";

        // Utilization_ratio: use customer/merchant credit limit
        $user = User::find($userId);
        $utilizationRatio = null;

        if ($user) {
            if ($type === 'merchant' && method_exists($user, 'merchantCreditLimit')) {
                $limit = $user->merchantCreditLimit ? ($user->merchantCreditLimit->credit_limit ?? null) : null;
                if ($limit && $limit > 0) {
                    // outstanding = sum of schedule payments not paid
                    if ($type === 'merchant') {
                        $outstanding = SchedulePayment::where('seller_id', $userId)
                            ->where('payment_status', '!=', 'paid')
                            ->sum('instalment_amount');
                    } else {
                        $outstanding = SchedulePayment::where('user_id', $userId)
                            ->where('payment_status', '!=', 'paid')
                            ->sum('instalment_amount');
                    }
                    $utilizationRatio = $limit > 0 ? ($outstanding / $limit) : null;
                }
            } elseif (method_exists($user, 'customerCreditLimit')) {
                $limit = $user->customerCreditLimit ? ($user->customerCreditLimit->credit_limit ?? null) : null;
                if ($limit && $limit > 0) {
                    $outstanding = SchedulePayment::where('user_id', $userId)
                        ->where('payment_status', '!=', 'paid')
                        ->sum('instalment_amount');
                    $utilizationRatio = $limit > 0 ? ($outstanding / $limit) : null;
                }
            }
        }

        // fallback if we cannot compute
        if ($utilizationRatio === null) {
            $utilizationRatio = 0.5; // neutral
            $notes[] = "Utilization not computed from credit limit -> assumed 0.5";
            $flags[] = 'no_credit_limit_info';
        }

        // Utilization score mapping
        if ($utilizationRatio >= 0.3 && $utilizationRatio <= 0.8) {
            $utilScore = 100;
        } elseif (($utilizationRatio >= 0.1 && $utilizationRatio < 0.3) || ($utilizationRatio > 0.8 && $utilizationRatio <= 1.0)) {
            $utilScore = 80;
        } else {
            $utilScore = 60;
        }

        $notes[] = "utilizationRatio={$utilizationRatio}, utilScore={$utilScore}";

        // Dispute_rate: refunds / total orders
        if ($type === 'merchant') {
            $totalOrders = Order::where('seller_id', $userId)->count() ?: 0;
            $disputes = RefundRequest::where('seller_id', $userId)->count() ?: 0;
        } else {
            $totalOrders = Order::where('user_id', $userId)->count() ?: 0;
            $disputes = RefundRequest::where('user_id', $userId)->count() ?: 0;
        }
        $disputeRate = $totalOrders > 0 ? ($disputes / $totalOrders) : 0;

        if ($disputeRate <= 0.02) {
            $disputeScore = 100;
        } elseif ($disputeRate <= 0.05) {
            $disputeScore = 80;
        } elseif ($disputeRate <= 0.10) {
            $disputeScore = 60;
        } else {
            $disputeScore = 40;
        }

        $notes[] = "disputeRate={$disputeRate}, disputeScore={$disputeScore}";

        // Turnover_trend: compare AP-financed volume last 6 months vs previous 6 months
        try {
            $now = Carbon::now();
            $recentStart = $now->copy()->subMonths(6);
            $prevStart = $now->copy()->subMonths(12);

            if ($type === 'merchant') {
                $recentSum = Order::where('seller_id', $userId)->whereBetween('created_at', [$recentStart, $now])->sum('grand_total');
                $prevSum = Order::where('seller_id', $userId)->whereBetween('created_at', [$prevStart, $recentStart])->sum('grand_total');
            } else {
                $recentSum = Order::where('user_id', $userId)->whereBetween('created_at', [$recentStart, $now])->sum('grand_total');
                $prevSum = Order::where('user_id', $userId)->whereBetween('created_at', [$prevStart, $recentStart])->sum('grand_total');
            }

            if ($prevSum == 0 && $recentSum == 0) {
                $trendPct = 0;
            } elseif ($prevSum == 0) {
                $trendPct = 100; // new growth
            } else {
                $trendPct = (($recentSum - $prevSum) / max(1, $prevSum)) * 100;
            }

            if ($trendPct >= 20) {
                $trendScore = 100;
            } elseif ($trendPct >= 0) {
                $trendScore = 80;
            } elseif ($trendPct >= -20) {
                $trendScore = 70;
            } else {
                $trendScore = 60;
            }
        } catch (\Throwable $e) {
            $trendPct = 0;
            $trendScore = 70;
        }

        $notes[] = "turnover_trend_pct={$trendPct}, trendScore={$trendScore}";

        // Combine BES using dynamic weights
        $bes = $dpdFrac * $apDpdScore + $utilFrac * $utilScore + $disputeFrac * $disputeScore + $trendFrac * $trendScore;

        // If no AP history at all (no schedule payments and no orders), set default
        if ($type === 'merchant') {
            $hasAPHistory = SchedulePayment::where('seller_id', $userId)->exists() || Order::where('seller_id', $userId)->exists();
        } else {
            $hasAPHistory = SchedulePayment::where('user_id', $userId)->exists() || Order::where('user_id', $userId)->exists();
        }

        if (! $hasAPHistory) {
            $flags[] = 'no_behavior_history';
            $bes = $this->policy['default_bes'];
            $notes[] = 'No AP history; returning default BES';
        }

        return [
            'score' => (float)$bes,
            'notes' => implode('; ', $notes),
            'flags' => $flags,
            'components' => [
                'dpd_ap_max' => $dpdMax,
                'ap_dpd_score' => round($apDpdScore, 2),
                'utilization_ratio' => round($utilizationRatio, 4),
                'utilization_score' => round($utilScore, 2),
                'dispute_rate' => round($disputeRate, 4),
                'dispute_score' => round($disputeScore, 2),
                'turnover_trend_pct' => round($trendPct, 2),
                'trend_score' => round($trendScore, 2),
                'used_weights' => [
                    'bes_dpd_weight' => $dpdW,
                    'bes_utilization_weight' => $utilW,
                    'bes_dispute_weight' => $disputeW,
                    'bes_trend_weight' => $trendW,
                ],
            ],
        ];
    }

    /**
     * Compute Compliance Adjustment Factor (CAF)
     * For now return default 1.0. If you integrate compliance flags, scale accordingly.
     * Returns ['score' => caf, 'notes'=>..., 'components'=>['caf'=>...]]
     */
    protected function computeCAF($entity, string $type): array
    {
        // placeholder: in future consider PEP, sanctions, SAR, etc.
        $caf = $this->policy['default_caf'];
        $notes = 'Compliance module not integrated; using default CAF=1.0';
        return [
            'score' => (float)$caf,
            'notes' => $notes,
            'flags' => [],
            'components' => [
                'caf' => (float)$caf,
            ],
        ];
    }

    /**
     * Compose OMRS using weights from RiskWeight
     * Input array must contain lps, chs, bcs, bps, bes, caf values (lps..bes in 0..100)
     * Returns final score in 0..100
     */
    protected function computeOMRS(array $values): float
    {
        // read weights from loaded weights (lps_weight, chs_weight, bcs_weight, bps_weight, bes_weight)
        $w_lps = $this->weights['lps_weight'] ?? 15;
        $w_chs = $this->weights['chs_weight'] ?? 25;
        $w_bcs = $this->weights['bcs_weight'] ?? 20;
        $w_bps = $this->weights['bps_weight'] ?? 10;
        $w_bes = $this->weights['bes_weight'] ?? 30;

        $sumW = ($w_lps + $w_chs + $w_bcs + $w_bps + $w_bes) ?: 1;

        $LPS = (($values['lps'] ?? 60) / 100.0);
        $CHS = (($values['chs'] ?? $this->policy['default_chs']) / 100.0);
        $BCS = (($values['bcs'] ?? $this->policy['default_bcs']) / 100.0);
        $BPS = (($values['bps'] ?? 70) / 100.0);
        $BES = (($values['bes'] ?? $this->policy['default_bes']) / 100.0);
        $CAF = ($values['caf'] ?? 1.0);

        // normalized weights -> fractions that sum to 1
        $base = ($w_lps / $sumW) * $LPS
            + ($w_chs / $sumW) * $CHS
            + ($w_bcs / $sumW) * $BCS
            + ($w_bps / $sumW) * $BPS
            + ($w_bes / $sumW) * $BES;

        $omrs01 = $base * $CAF;

        return $omrs01 * 100;
    }

    // ---------- Helpers ----------

    /**
     * Load RiskWeight for a given user_id (the user who sets weights). If not found, return defaults.
     * @param int|null $weightUserId
     * @return array
     */
    protected function loadWeights($weightUserId = null): array
    {
        $defaults = RiskWeight::getDefaultWeights();

        if (empty($weightUserId)) {
            return $defaults;
        }

        $rw = RiskWeight::where('user_id', $weightUserId)->first();
        if (! $rw) {
            return $defaults;
        }

        // merge values: prefer non-null values from model, else defaults
        $mapped = [];
        foreach ($defaults as $k => $v) {
            $mapped[$k] = $rw->{$k} !== null ? $rw->{$k} : $v;
        }

        // also allow last_weight/new_weight arrays if present to be included (optional)
        if ($rw->last_weight) {
            $mapped['last_weight'] = $rw->last_weight;
        }
        if ($rw->new_weight) {
            $mapped['new_weight'] = $rw->new_weight;
        }

        return $mapped;
    }

    protected function resolveEntity($entityOrId, string $type)
    {
        if ($entityOrId instanceof Customer || $entityOrId instanceof Merchant) {
            return $entityOrId;
        }

        if ($type === 'merchant') {
            return Merchant::find($entityOrId);
        }

        return Customer::find($entityOrId);
    }

    protected function getUserId($entity, string $type)
    {
        if ($type === 'merchant') {
            return $entity->seller_id;
        }

        return $entity->user_id;
    }

    protected function looksLikeJsonArray(string $s): bool
    {
        $s = trim($s);
        return Str::startsWith($s, '[') && Str::endsWith($s, ']');
    }
}
