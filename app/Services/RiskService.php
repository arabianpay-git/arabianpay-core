<?php

namespace App\Services;

use App\Models\BusinessCategory;
use App\Models\City;
use App\Models\Customer;
use App\Models\LeanReport;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Models\RiskWeight;
use App\Models\SimahReport;
use App\Models\Setting; // Added Setting model
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
     */
    protected array $weights = [];

    /**
     * Source of weights for this analysis
     */
    protected string $weightsSource = 'defaults';

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
        $this->loadWeights($weightUserId);

        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (!$entity) {
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
            'lps'           => round($lps, 2),
            'chs'           => round($chs, 2),
            'bcs'           => round($bcs, 2),
            'bps'           => round($bps, 2),
            'bes'           => round($bes, 2),
            'caf'           => round($caf, 2),
            'omrs'          => round($omrs, 2),
            'flags'         => array_values(array_unique($flags)),
            'notes'         => $notes,
            'components'    => $components,
            'type'          => $type,
            'weights_used'  => $this->weights,
            'weights_source' => $this->weightsSource, // Added weights source
        ];
    }

    /**
     * Return only Overall Merchant Risk Score (0-100)
     */
    public function getOMRS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->loadWeights($weightUserId);
        $res = $this->analyzeCustomer($customerOrMerchantOrId, $type, $weightUserId);
        return $res['omrs'] ?? 0;
    }

    /**
     * Return only LPS
     */
    public function getLPS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (!$entity) return 0;
        $res = $this->computeLPS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only CHS
     */
    public function getCHS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->loadWeights($weightUserId);
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
        $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (!$entity) return $this->policy['default_bcs'];
        $res = $this->computeBCS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BPS
     */
    public function getBPS($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (!$entity) return 0;
        $res = $this->computeBPS($entity, $type);
        return round($res['score'], 2);
    }

    /**
     * Return only BES
     */
    public function getBES($customerOrMerchantOrId, string $type = 'customer', $weightUserId = null): float
    {
        $this->loadWeights($weightUserId);
        $entity = $this->resolveEntity($customerOrMerchantOrId, $type);
        if (!$entity) return $this->policy['default_bes'];
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
            $crData = json_decode($entity->goverment_data ?? '{}', true) ?? null;
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

        // CHANGED: For merchants, do NOT use $entity->nafath_data.
        // Instead check nafath_verifications table for an approved record for this entity's user_id.
        $hasNafath = false;
        if ($type === 'merchant') {
            // ADDED: Query nafath_verifications for approved status (use \DB to avoid adding imports).
            // If your status values are stored differently (e.g. 'Approved', or an integer), adjust the where clause accordingly.
            try {
                $userId = $entity->user_id ?? null;
                if ($userId) {
                    $nafathExists = DB::table('nafath_verifications')
                        ->where('user_id', $userId)
                        ->where('status', 'approved')
                        ->exists();
                    $hasNafath = (bool)$nafathExists;
                } else {
                    $hasNafath = false;
                    $flags[] = 'merchant_no_user_id_for_nafath_check';
                    $notes[] = 'Merchant entity has no user_id; cannot check nafath_verifications.';
                }
            } catch (\Exception $e) {
                // If DB fails for some reason, treat as not present but add a flag/note.
                $hasNafath = false;
                $flags[] = 'nafath_db_error';
                $notes[] = 'Error checking nafath_verifications: ' . $e->getMessage();
            }
        } else {
            // Non-merchant: use existing nafath_data field
            $nafath = $entity->nafath_data ?? null;
            $hasNafath = !empty($nafath);
        }

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

        // LPS formula uses dynamic weights
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
            'bureau_rating_raw' => null,
            'bureau_rating_score' => null, // scaled to max part score (0..60)
            'dpd_score' => null,
            'max_dpd_12m' => null,
            'raw_report' => null,
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
                        // --- CHANGED: handle raw SIMAH score (e.g. 617) and scale it to the part max (60) ---
                        // Define raw score expected bounds and the part's max score
                        $RAW_MIN = 300.0;   // assumed minimum possible SIMAH score (clamp below to 0)
                        $RAW_MAX = 900.0;   // assumed maximum possible SIMAH score
                        $PART_MAX = 60.0;   // maximum points available for this CHS part

                        $raw = (float)$bureauScore;
                        $components['bureau_rating_raw'] = $raw;

                        // compute normalized ratio and clamp to [0,1]
                        if ($RAW_MAX > $RAW_MIN) {
                            $ratio = ($raw - $RAW_MIN) / ($RAW_MAX - $RAW_MIN);
                        } else {
                            $ratio = 0.0;
                        }
                        $ratio = max(0.0, min(1.0, $ratio));

                        // scaled score for this CHS component (0..PART_MAX)
                        $scaled = round($ratio * $PART_MAX, 2);
                        $components['bureau_rating_score'] = $scaled;

                        $notes[] = 'SIMAH consumerScore used from latest report and scaled to part max ' . $PART_MAX . '.';

                        // detect non-scorable indicator
                        $scoreCardCode = $scoreEntry['scoreCard']['scoreCardCode'] ?? null;
                        if ($scoreCardCode === 'NS' || $raw === 0.0) {
                            $flags[] = 'simah_non_scorable';
                            // if non-scorable, ensure scaled is zero
                            $components['bureau_rating_score'] = 0.0;
                            $scaled = 0.0;
                        }

                        // try to extract some DPD / delinquency info if present
                        $summary = $report['data']['summaryInfo'] ?? null;
                        if (is_array($summary)) {
                            if (isset($summary['summCurrentDelinquentBalance'])) {
                                $components['max_dpd_12m'] = $summary['summCurrentDelinquentBalance'];
                            } elseif (isset($summary['summDefaults'])) {
                                $components['max_dpd_12m'] = $summary['summDefaults'];
                            }
                        }

                        // dpd_score not available directly in many SIMAH payloads
                        $components['dpd_score'] = null;

                        return [
                            'score' => (float)$scaled,
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

        // -----------------------
        // Defaults (CTO-provided) - preserved but overridable from $this->policy / $this->weights
        // -----------------------
        $defaultPolicy = [
            'bcs_th_low'  => 30000,
            'bcs_th_mid'  => 80000,
            'bcs_th_high' => 150000,
            'default_bcs' => 50,
            'bcs_min_months' => 3, // Min months required
            'bcs_lookback_months' => 6,
            'bcs_as_of' => '2025-12-15', // optional override in policy
        ];

        $policy = array_merge($defaultPolicy, $this->policy ?? []);

        // Weights (must sum to 1 ideally). Use provided weights, or fall back to CTO defaults.
        $turnoverW = $this->weights['bcs_turnover_weight'] ?? 0.35;
        $volatilityW = $this->weights['bcs_volatility_weight'] ?? 0.25;
        $returnedW = $this->weights['bcs_returned_weight'] ?? 0.25;
        $balanceW = $this->weights['bcs_balance_weight'] ?? 0.15;

        // Normalize weights to fractions (defensive)
        $sumW = ($turnoverW + $volatilityW + $returnedW + $balanceW) ?: 1;
        $turnoverFrac = $turnoverW / $sumW;
        $volFrac = $volatilityW / $sumW;
        $returnedFrac = $returnedW / $sumW;
        $balanceFrac = $balanceW / $sumW;

        // Get user ID based on entity type
        $userId = $this->getUserId($entity, $type);

        // Lookback and min months
        $months = (int)($policy['bcs_lookback_months'] ?? 6);
        $minMonths = max(1, (int)($policy['bcs_min_months'] ?? 3));

        // As-Of date (evaluation reference)
        try {
            $asOf = Carbon::parse($policy['bcs_as_of'] ?? now());
        } catch (\Throwable $parseEx) {
            $asOf = Carbon::now();
        }

        try {
            $revenues = [];
            $usedLean = false;
            $avgMonthly = 0;
            $leanReport = null;
            $data = null;

            // -----------------------
            // A: Try the fast path: use generated column `has_data` if present (very cheap)
            // -----------------------
            try {
                $leanReport = LeanReport::where('user_id', $userId)
                    ->where('has_data', 1)
                    ->orderBy('created_at', 'desc')
                    ->first();
            } catch (\Throwable $e) {
                // has_data might not exist — ignore and fallback
                $leanReport = null;
            }

            // -----------------------
            // B: If fast path didn't yield a report, do a controlled lookup (limit 5) and pick the first with non-empty data
            // -----------------------
            if (!$leanReport) {
                $recentReports = LeanReport::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                foreach ($recentReports as $r) {
                    if (!empty($r->data)) {
                        $leanReport = $r;
                        break;
                    }
                }
            }

            // -----------------------
            // Prepare vars for reversal detection (will be filled if Lean used)
            // -----------------------
            $reversalKeywords = ['REVERSAL', 'REFUND', 'CHARGEBACK', 'RETURN', 'REVERSED', 'REVERS'];
            $reversalCount = 0;
            $reversalAmount = 0.0;
            $totalTxnCount = 0;
            $totalCreditAmount = 0.0;
            $totalAbsAmount = 0.0;

            // -----------------------
            // C: If we have a leanReport with data, try to compute monthly revenues from it and detect reversals
            // -----------------------
            if ($leanReport && !empty($leanReport->data)) {
                $data = $leanReport->data; // model casts JSON -> array

                $monthlyRevenue = [];

                if (isset($data['report']['banks']) && is_array($data['report']['banks'])) {
                    foreach ($data['report']['banks'] as $bank) {
                        if (!isset($bank['accounts']) || !is_array($bank['accounts'])) {
                            continue;
                        }
                        foreach ($bank['accounts'] as $acctWrapper) {
                            $txns = $acctWrapper['transactions'] ?? [];
                            if (!is_array($txns)) {
                                continue;
                            }
                            foreach ($txns as $txn) {
                                // Count total txns regardless for reversal ratio denominator
                                $totalTxnCount++;

                                // Extract booking date
                                if (empty($txn['booking_date_time'])) {
                                    // still attempt reversal detection even if date missing
                                    $dt = null;
                                } else {
                                    try {
                                        $dt = Carbon::parse($txn['booking_date_time']);
                                    } catch (\Throwable $e) {
                                        $dt = null;
                                    }
                                }

                                // Determine amount numeric
                                $amount = 0.0;
                                if (isset($txn['amount']['amount'])) {
                                    $amount = (float)$txn['amount']['amount'];
                                } elseif (isset($txn['amount'])) {
                                    $amount = (float)$txn['amount'];
                                }
                                $totalAbsAmount += abs($amount);

                                // Track credit totals (inflows) — used as primary denominator for returned amount ratio
                                $isCredit = false;
                                if (isset($txn['credit_debit_indicator'])) {
                                    $isCredit = strtoupper($txn['credit_debit_indicator']) === 'CREDIT';
                                } else {
                                    $isCredit = $amount >= 0;
                                }
                                if ($isCredit) {
                                    $totalCreditAmount += abs($amount);
                                }

                                // Only include transactions within lookback window for monthly turnover calculation
                                if ($dt && ($dt->lt($asOf->copy()->subMonths($months)) || $dt->gt($asOf))) {
                                    // skip adding to monthly buckets but still allow reversal detection (we may still want to consider recent reversal events only)
                                    // continue; // DON'T continue because we want reversal detection across lookback window only? We'll only consider txns within lookback for month buckets below.
                                }

                                // Build monthly revenue buckets only if date present and within lookback window
                                if ($dt && !$dt->lt($asOf->copy()->subMonths($months)) && !$dt->gt($asOf)) {
                                    $monthKey = $dt->format('Y-m');

                                    if ($type === 'merchant') {
                                        // For merchant, prefer credits (inflows) as turnover
                                        if ($isCredit) {
                                            $monthlyRevenue[$monthKey] = ($monthlyRevenue[$monthKey] ?? 0.0) + $amount;
                                        }
                                    } else {
                                        // For customer, count absolute movement
                                        $monthlyRevenue[$monthKey] = ($monthlyRevenue[$monthKey] ?? 0.0) + abs($amount);
                                    }
                                }

                                // -----------------------
                                // Reversal detection (transaction_information)
                                // -----------------------
                                $info = $txn['transaction_information'] ?? ($txn['transactionDescription'] ?? ($txn['description'] ?? ''));
                                if (!empty($info) && is_string($info)) {
                                    $upperInfo = strtoupper($info);
                                    foreach ($reversalKeywords as $kw) {
                                        if (strpos($upperInfo, $kw) !== false) {
                                            $reversalCount++;
                                            $reversalAmount += abs($amount);
                                            break; // count each txn once even if multiple keywords
                                        }
                                    }
                                }
                            } // foreach txn
                        } // foreach accounts
                    } // foreach banks
                }

                // Build months list (ensures zero months are present)
                $monthsList = [];
                for ($i = 0; $i < $months; $i++) {
                    $m = $asOf->copy()->subMonths($i)->startOfMonth();
                    $monthsList[] = $m->format('Y-m');
                }
                $monthsList = array_reverse($monthsList);

                $revenues = [];
                foreach ($monthsList as $mKey) {
                    $revenues[] = round($monthlyRevenue[$mKey] ?? 0.0, 2);
                }

                $nonZeroMonths = 0;
                foreach ($revenues as $r) {
                    if ($r > 0) $nonZeroMonths++;
                }

                if ($nonZeroMonths >= $minMonths) {
                    $usedLean = true;
                    $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0.0;
                    $notes[] = "Used LeanReport (id={$leanReport->id}) for turnover calculation; months={$months}, non_zero_months={$nonZeroMonths}";
                } else {
                    $notes[] = "LeanReport present (id=" . ($leanReport->id ?? 'n/a') . ") but insufficient months (non_zero_months={$nonZeroMonths}); will fall back to orders";
                    $flags[] = 'insufficient_lean_months';
                    $usedLean = false;
                }
            }

            // -----------------------
            // D: If Lean not used, fallback to Order-based calculation
            // -----------------------
            if (!$usedLean) {
                if ($type === 'merchant') {
                    $recentOrders = Order::where('seller_id', $userId)
                        ->where('created_at', '>=', Carbon::now()->subMonths($months))
                        ->get();

                    $monthlyRevenue = [];
                    foreach ($recentOrders as $order) {
                        $month = $order->created_at->format('Y-m');
                        $monthlyRevenue[$month] = ($monthlyRevenue[$month] ?? 0.0) + (float)($order->grand_total ?? 0.0);
                    }

                    $monthsList = [];
                    for ($i = 0; $i < $months; $i++) {
                        $m = Carbon::now()->subMonths($i)->startOfMonth();
                        $monthsList[] = $m->format('Y-m');
                    }
                    $monthsList = array_reverse($monthsList);

                    $revenues = [];
                    foreach ($monthsList as $mKey) {
                        $revenues[] = round($monthlyRevenue[$mKey] ?? 0.0, 2);
                    }

                    $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0.0;
                    $notes[] = "Used Order data for merchant turnover; months={$months}";
                } else {
                    if (method_exists(Order::class, 'getRevenueStreams')) {
                        $revenueData = Order::getRevenueStreams($months);
                        $revenues = $revenueData['revenue'] ?? [];
                        $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0.0;
                        $notes[] = "Used getRevenueStreams() for customer turnover";
                    } else {
                        $recentOrders = Order::where('user_id', $userId)
                            ->where('created_at', '>=', Carbon::now()->subMonths($months))
                            ->get();
                        $monthlyRevenue = [];
                        foreach ($recentOrders as $order) {
                            $month = $order->created_at->format('Y-m');
                            $monthlyRevenue[$month] = ($monthlyRevenue[$month] ?? 0.0) + (float)($order->grand_total ?? 0.0);
                        }
                        $monthsList = [];
                        for ($i = 0; $i < $months; $i++) {
                            $m = Carbon::now()->subMonths($i)->startOfMonth();
                            $monthsList[] = $m->format('Y-m');
                        }
                        $monthsList = array_reverse($monthsList);
                        $revenues = [];
                        foreach ($monthsList as $mKey) {
                            $revenues[] = round($monthlyRevenue[$mKey] ?? 0.0, 2);
                        }
                        $avgMonthly = count($revenues) ? (array_sum($revenues) / count($revenues)) : 0.0;
                        $notes[] = "Used Order data for customer turnover; months={$months}";
                    }
                }
            }

            // -----------------------
            // Volatility calculation
            // -----------------------
            $mean = $avgMonthly;
            $variance = 0.0;
            if (!empty($revenues)) {
                foreach ($revenues as $v) {
                    $variance += pow(($v - $mean), 2);
                }
                $variance = $variance / count($revenues); // population variance
                $stddev = sqrt($variance);
                $volatility = $mean > 0 ? ($stddev / $mean) : 0.0;
            } else {
                $stddev = 0.0;
                $volatility = 0.0;
            }

            $notes[] = "Avg_monthly_turnover={$avgMonthly}, volatility={$volatility}";

            // -----------------------
            // Returned_rate (proxy)
            // If Lean used -> derive from bank transactions (transaction_information keywords)
            // Otherwise -> fallback to Order/RefundRequest counts (legacy)
            // -----------------------
            if ($usedLean) {
                // Use amounts as primary signal when possible
                // totalCreditAmount computed earlier while scanning transactions
                $reversalCount = $reversalCount ?? 0;
                $reversalAmount = $reversalAmount ?? 0.0;
                $totalTxnCount = $totalTxnCount ?? 0;
                $totalCreditAmount = $totalCreditAmount ?? 0.0;
                $totalAbsAmount = $totalAbsAmount ?? 0.0;

                // Preferred ratio: reversal_amount / total_credit_amount (if credit amount > 0)
                if ($totalCreditAmount > 0) {
                    $returnedRate = $reversalAmount / $totalCreditAmount;
                } elseif ($totalAbsAmount > 0) {
                    // fallback: use reversal_amount / total_abs_amount
                    $returnedRate = $reversalAmount / $totalAbsAmount;
                } else {
                    // fallback: use count-based measure
                    $returnedRate = $totalTxnCount > 0 ? ($reversalCount / $totalTxnCount) : 0.0;
                }

                $notes[] = "Derived returnedRate from bank transactions (reversal_count={$reversalCount}, reversal_amount={$reversalAmount}, total_credit_amount={$totalCreditAmount}, total_txns={$totalTxnCount})";
            } else {
                // legacy fallback using orders/refunds
                if ($type === 'merchant') {
                    $totalOrders = Order::where('seller_id', $userId)->count() ?: 0;
                    $refunds = RefundRequest::where('seller_id', $userId)->count() ?: 0;
                } else {
                    $totalOrders = Order::where('user_id', $userId)->count() ?: 0;
                    $refunds = RefundRequest::where('user_id', $userId)->count() ?: 0;
                }
                $returnedRate = $totalOrders > 0 ? ($refunds / $totalOrders) : 0.0;

                $notes[] = "Used Order/Refund counts for returnedRate fallback (orders={$totalOrders}, refunds={$refunds})";
            }

            // -----------------------
            // min_balance_ratio: prefer Lean closing balances if Lean used, else approximate
            // -----------------------
            $minBalanceRatio = 0.05; // default
            $minClosing = null;
            if ($leanReport && $usedLean && isset($data['report']['banks'])) {
                foreach ($data['report']['banks'] as $bank) {
                    foreach ($bank['accounts'] ?? [] as $acctWrapper) {
                        foreach ($acctWrapper['balances'] ?? [] as $bal) {
                            if (isset($bal['type']) && strtoupper($bal['type']) === 'CLOSING_BOOKED' && isset($bal['amount']['amount'])) {
                                $val = (float)$bal['amount']['amount'];
                                if ($minClosing === null || $val < $minClosing) {
                                    $minClosing = $val;
                                }
                            }
                        }
                    }
                }
                if ($minClosing !== null && $avgMonthly > 0) {
                    $minBalanceRatio = $minClosing / $avgMonthly;
                } elseif ($minClosing !== null) {
                    $minBalanceRatio = $minClosing > 0 ? 0.1 : 0.05;
                } else {
                    $minBalanceRatio = $avgMonthly > 0 ? 0.1 : 0.05;
                }
            } else {
                $minBalanceRatio = $avgMonthly > 0 ? 0.1 : 0.05;
            }

            if ($minBalanceRatio < 0) {
                $minBalanceRatio = 0.0;
            }

            // -----------------------
            // Score mappings using thresholds from policy
            // -----------------------
            $thHigh = $policy['bcs_th_high'];
            $thMid = $policy['bcs_th_mid'];
            $thLow = $policy['bcs_th_low'];

            if ($avgMonthly >= $thHigh) {
                $turnoverScore = 100;
            } elseif ($avgMonthly >= $thMid) {
                $turnoverScore = 80;
            } elseif ($avgMonthly >= $thLow) {
                $turnoverScore = 60;
            } else {
                $turnoverScore = 40;
            }

            if ($volatility <= 0.2) {
                $volScore = 100;
            } elseif ($volatility <= 0.5) {
                $volScore = 80;
            } elseif ($volatility <= 1.0) {
                $volScore = 60;
            } else {
                $volScore = 40;
            }

            if ($returnedRate == 0) {
                $returnedScore = 100;
            } elseif ($returnedRate <= 0.05) {
                $returnedScore = 80;
            } elseif ($returnedRate <= 0.15) {
                $returnedScore = 60;
            } else {
                $returnedScore = 40;
            }

            if ($minBalanceRatio >= 0.2) {
                $minBalanceScore = 100;
            } elseif ($minBalanceRatio >= 0.1) {
                $minBalanceScore = 80;
            } elseif ($minBalanceRatio >= 0.05) {
                $minBalanceScore = 60;
            } else {
                $minBalanceScore = 40;
            }

            // Compose BCS using normalized weights
            $bcs = $turnoverFrac * $turnoverScore
                + $volFrac * $volScore
                + $returnedFrac * $returnedScore
                + $balanceFrac * $minBalanceScore;

            $notes[] = "turnoverScore={$turnoverScore}, volScore={$volScore}, returnedScore={$returnedScore}, minBalanceScore={$minBalanceScore}; weights(turnover,volatility,returned,balance)=({$turnoverW},{$volatilityW},{$returnedW},{$balanceW})";

            if ($avgMonthly == 0) {
                $flags[] = 'no_banking_data';
                $notes[] = 'Avg monthly turnover computed as 0 — check open-banking integration if you expect data.';
            }
            if (isset($leanReport) && !$usedLean) {
                $flags[] = 'lean_present_but_not_used';
            }

            return [
                'score' => (float)$bcs,
                'notes' => implode('; ', $notes),
                'flags' => array_values(array_unique($flags)),
                'components' => [
                    'avg_monthly_turnover' => round($avgMonthly, 2),
                    'turnover_score' => round($turnoverScore, 2),
                    'volatility' => round($volatility, 4),
                    'volatility_score' => round($volScore, 2),
                    'returned_rate' => round($returnedRate, 4),
                    'returned_score' => round($returnedScore, 2),
                    'returned_count' => $reversalCount ?? 0,
                    'returned_amount' => round($reversalAmount ?? 0.0, 2),
                    'min_balance_ratio' => round($minBalanceRatio, 4),
                    'min_balance_score' => round($minBalanceScore, 2),
                    'used_weights' => [
                        'bcs_turnover_weight' => $turnoverW,
                        'bcs_volatility_weight' => $volatilityW,
                        'bcs_returned_weight' => $returnedW,
                        'bcs_balance_weight' => $balanceW,
                    ],
                    'used_data_source' => ($usedLean ? 'lean_report' : 'orders'),
                    'lean_report_id' => $leanReport->report_id ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('RiskService computeBCS error: ' . $e->getMessage());
            $flags[] = 'no_banking_data';
            return [
                'score' => (float)($this->policy['default_bcs'] ?? $policy['default_bcs']),
                'notes' => 'Error computing BCS; returning default',
                'flags' => array_values(array_unique($flags)),
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

        if (!$hasAPHistory) {
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
     * Compose OMRS using weights from loaded weights
     * Input array must contain lps, chs, bcs, bps, bes, caf values (lps..bes in 0..100)
     * Returns final score in 0..100
     */
    protected function computeOMRS(array $values): float
    {
        // read weights from loaded weights
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
     * Load weights for a given user_id (the user who sets weights). If not found, use defaults from settings.
     * @param int|null $weightUserId
     * @return void
     */
    protected function loadWeights($weightUserId = null): void
    {
        // First get defaults from settings
        $settingsDefaults = Setting::getByKey('risk_weights', []);

        // Fallback hardcoded defaults in case settings are empty
        $hardcodedDefaults = [
            'lps_weight' => 15,
            'chs_weight' => 25,
            'bcs_weight' => 20,
            'bps_weight' => 10,
            'bes_weight' => 30,
            'lps_age_weight' => 40,
            'lps_cr_weight' => 30,
            'lps_doc_weight' => 30,
            'bcs_turnover_weight' => 35,
            'bcs_volatility_weight' => 25,
            'bcs_returned_weight' => 25,
            'bcs_balance_weight' => 15,
            'bps_sector_weight' => 70,
            'bps_region_weight' => 30,
            'bes_dpd_weight' => 40,
            'bes_utilization_weight' => 25,
            'bes_dispute_weight' => 25,
            'bes_trend_weight' => 10,
        ];

        // Merge settings with hardcoded defaults (settings take precedence)
        $defaults = array_merge($hardcodedDefaults, $settingsDefaults);

        // Start with defaults
        $this->weightsSource = 'default weights from controls';
        $finalWeights = $defaults;

        // Check if we have a specific user with custom weights
        if (!empty($weightUserId)) {
            $rw = RiskWeight::where('user_id', $weightUserId)->first();
            if ($rw) {
                // Merge user-specific weights with defaults (user values take precedence)
                foreach ($defaults as $key => $defaultValue) {
                    if (property_exists($rw, $key) && $rw->{$key} !== null) {
                        $finalWeights[$key] = $rw->{$key};
                    }
                }

                // Also include last_weight/new_weight arrays if present
                if ($rw->last_weight) {
                    $finalWeights['last_weight'] = $rw->last_weight;
                }
                if ($rw->new_weight) {
                    $finalWeights['new_weight'] = $rw->new_weight;
                }

                $this->weightsSource = 'risk changes for that user';
            }
        }

        $this->weights = $finalWeights;
    }

    /**
     * Get the current weights source
     * @return string
     */
    public function getWeightsSource(): string
    {
        return $this->weightsSource;
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
            return $entity->user_id;
        }

        return $entity->user_id;
    }

    protected function looksLikeJsonArray(string $s): bool
    {
        $s = trim($s);
        return Str::startsWith($s, '[') && Str::endsWith($s, ']');
    }
}
