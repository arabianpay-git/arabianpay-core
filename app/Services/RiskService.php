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
     * Public entry that returns everything
     * @param int|Customer|Merchant $customerOrMerchantOrId
     * @param string $type 'customer' or 'merchant'
     * @return array
     */
    public function analyzeCustomer($customerOrMerchantOrId, string $type = 'customer'): array
    {
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
        ];
    }

    /**
     * Return only Overall Merchant Risk Score (0-100)
     */
    public function getOMRS($customerOrMerchantOrId, string $type = 'customer'): float
    {
        $res = $this->analyzeCustomer($customerOrMerchantOrId, $type);
        return $res['omrs'] ?? 0;
    }

    /**
     * Return only LPS
     */
    public function getLPS($customerOrMerchantOrId, string $type = 'customer'): float
    {
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return 0;
        $res = $this->computeLPS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only CHS
     */
    public function getCHS($customerOrMerchantOrId, string $type = 'customer'): float
    {
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return $this->policy['default_chs'];
        $res = $this->computeCHS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BCS
     */
    public function getBCS($customerOrMerchantOrId, string $type = 'customer'): float
    {
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return $this->policy['default_bcs'];
        $res = $this->computeBCS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BPS
     */
    public function getBPS($customerOrMerchantOrId, string $type = 'customer'): float
    {
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (! $entity) return 0;
        $res = $this->computeBPS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BES
     */
    public function getBES($customerOrMerchantOrId, string $type = 'customer'): float
    {
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

        // LPS formula: 0.4*Age + 0.3*CR + 0.3*Doc
        $lps = 0.4 * $ageScore + 0.3 * $crScore + 0.3 * $docScore;

        $notes[] = "Age_score={$ageScore}, CR_score={$crScore}, Doc_score={$docScore}";

        return [
            'score' => (float)$lps,
            'notes' => implode('; ', $notes),
            'flags' => $flags,
            'components' => [
                'age_score' => round($ageScore, 2),
                'cr_score'  => round($crScore, 2),
                'doc_score' => round($docScore, 2),
                't_business_months' => $tMonths,
            ],
        ];
    }

    /**
     * Compute CHS per spec.
     * For now SIMAH is not integrated — return default and set flag
     * Returns [
     *   score, notes, flags, components: ['bureau_rating_score'=>..., 'dpd_score'=>...]
     * ]
     */
    protected function computeCHS($entity, string $type): array
    {
        $flags = [];
        $notes = [];

        // default behavior: no bureau integration yet
        $chs = (float)$this->policy['default_chs'];
        $notes[] = 'No bureau (SIMAH) integrated — returning default CHS.';
        $flags[] = 'no_bureau_data';

        // components placeholders
        $components = [
            'bureau_rating_score' => null,
            'dpd_score' => null,
            'max_dpd_12m' => null,
        ];

        return [
            'score' => $chs,
            'notes' => implode('; ', $notes),
            'flags' => $flags,
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
                // For customers, use the existing method
                $months = 6;
                $revenueData = Order::getRevenueStreams($months);
                $revenues = $revenueData['revenue'] ?? [];
                $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0;
            }

            // volatility: standard deviation / mean
            $mean = $avgMonthly;
            $variance = 0;
            if (count($revenues) > 0) {
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

            // Map turnover_score
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

            // Compose BCS
            $bcs = 0.35 * $turnoverScore + 0.25 * $volScore + 0.25 * $returnedScore + 0.15 * $minBalanceScore;

            $notes[] = "turnoverScore={$turnoverScore}, volScore={$volScore}, returnedScore={$returnedScore}, minBalanceScore={$minBalanceScore}";

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

        $bps = 0.7 * $sectorScore + 0.3 * $regionScore;
        $notes[] = "sectorScore={$sectorScore}, regionScore={$regionScore}";

        return [
            'score' => (float)$bps,
            'notes' => implode('; ', $notes),
            'flags' => [],
            'components' => [
                'sector_risk_class' => $sectorRiskClass,
                'sector_score' => round($sectorScore, 2),
                'region_risk_class' => $regionRiskClass,
                'region_score' => round($regionScore, 2),
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

        // Combine BES = 0.4*AP_DPD_score + 0.25*Utilization_score + 0.25*Dispute_score + 0.10*Trend_score
        $bes = 0.4 * $apDpdScore + 0.25 * $utilScore + 0.25 * $disputeScore + 0.10 * $trendScore;

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
     * Compose OMRS using weights from doc
     * Input array must contain lps, chs, bcs, bps, bes, caf values (lps..bes in 0..100)
     * Returns final score in 0..100
     */
    protected function computeOMRS(array $values): float
    {
        $w = [
            'w_lps' => 0.15,
            'w_chs' => 0.25,
            'w_bcs' => 0.20,
            'w_bps' => 0.10,
            'w_bes' => 0.30,
        ];

        $LPS = (($values['lps'] ?? 60) / 100.0);
        $CHS = (($values['chs'] ?? $this->policy['default_chs']) / 100.0);
        $BCS = (($values['bcs'] ?? $this->policy['default_bcs']) / 100.0);
        $BPS = (($values['bps'] ?? 70) / 100.0);
        $BES = (($values['bes'] ?? $this->policy['default_bes']) / 100.0);
        $CAF = ($values['caf'] ?? 1.0);

        $base = $w['w_lps'] * $LPS + $w['w_chs'] * $CHS + $w['w_bcs'] * $BCS + $w['w_bps'] * $BPS + $w['w_bes'] * $BES;
        $omrs01 = $base * $CAF;

        return $omrs01 * 100;
    }

    // ---------- Helpers ----------

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
