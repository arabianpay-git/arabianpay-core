<?php

namespace App\Services;

use App\Models\BusinessType;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiskDashboardService
{
    protected $riskAnalyticsService;

    protected $userId = null;

    protected $userType = null;

    // Risk thresholds configuration
    protected $thresholds = [
        'critical_dpd' => 60,
        'large_exposure' => 500000,
        'high_npl' => 4.0,
        'high_utilization' => 75,
        'low_capital' => 50000,
        'multiple_activities' => 20,
        'total_credit_limit' => 20000,
        'charge_off_target' => 2.5,
    ];

    protected $dateRange;

    public function __construct(RiskAnalyticsService $riskAnalyticsService)
    {
        $this->riskAnalyticsService = $riskAnalyticsService;
    }

    public function getDashboardData($filters = [])
    {
        // Extract user_id from filters if provided
        $this->userId = $filters['user_id'] ?? null;

        // If user_id provided, get user type
        if ($this->userId) {
            $user = User::find($this->userId);
            $this->userType = $user->user_type ?? null;
        }

        $this->dateRange = $this->getDateRange($filters);

        return [
            'portfolio' => $this->getPortfolioData(),
            'pipeline' => $this->getPipelineData(),
            'ews' => $this->getEwsData(),
            'risk_scores' => $this->getRiskScores(),
            'alerts' => $this->buildRiskAlerts(),
            'flags' => $this->buildRiskFlags(),
            'date_range' => [
                'current' => [
                    'from' => $this->dateRange['from']->format(dateFormat()),
                    'to' => $this->dateRange['to']->format(dateFormat()),
                ],
                'previous' => [
                    'from' => $this->dateRange['previous_from']->format(dateFormat()),
                    'to' => $this->dateRange['previous_to']->format(dateFormat()),
                ],
            ],
            'user_specific' => ! is_null($this->userId),
            'user_type' => $this->userType,
        ];
    }

    public function getPortfolioData()
    {
        $exposure = $this->calculateExposure();
        $utilization = $this->calculateUtilization();
        $dpdBuckets = $this->getDpdBuckets();
        $nplRatio = $this->calculateNplRatio();
        $cor = $this->calculateChargeOffRate();
        $vintageCurves = $this->getVintageCurves();

        return [
            'exposure' => [
                'total' => '<span class="icon-saudi_riyal"></span> '.number_format($exposure['total'] / 1000000, 2).'M',
                'change' => $this->formatChange($exposure['change']),
                'trend' => $exposure['change'] > 0 ? 'up' : 'down',
                'breakdown' => $exposure['breakdown'],
            ],
            'utilization' => [
                'rate' => $utilization['rate'].'%',
                'change' => $this->formatChange($utilization['change']),
                'trend' => $utilization['change'] > 0 ? 'up' : 'down',
                'available' => '<span> '.number_format($utilization['available'] / 1000000, 2).'M',
                'used' => '<span> '.number_format($utilization['used'] / 1000000, 2).'M',
            ],
            'vintage_curves' => $vintageCurves,
            'dpd_buckets' => $dpdBuckets,
            'npl_ratio' => [
                'current' => $nplRatio['current'].'%',
                'previous' => $nplRatio['previous'].'%',
                'change' => $this->formatChange($nplRatio['change']),
                'trend' => $nplRatio['change'] > 0 ? 'up' : 'down',
            ],
            'cor' => [
                'current' => $cor['current'].'%',
                'target' => $cor['target'].'%',
                'status' => $cor['current'] > $cor['target'] ? 'above_target' : 'below_target',
            ],
        ];
    }

    public function getPipelineData()
    {
        $slaMetrics = $this->calculateSlaMetrics();
        $approvalRates = $this->getApprovalRates();
        $exceptionRate = $this->getExceptionRate();
        $statusDistribution = $this->getStatusDistribution();
        $applicationTrend = $this->getApplicationTrend();
        $processingTimes = $this->getProcessingTimes();
        $underwritingStats = $this->getUnderwritingStats();

        return [
            'sla_metrics' => $slaMetrics,
            'approval_rates' => $approvalRates,
            'exception_rate' => $exceptionRate,
            'status_distribution' => $statusDistribution,
            'application_trend' => $applicationTrend,
            'processing_times' => $processingTimes,
            'underwriting_stats' => $underwritingStats,
            'summary' => $this->getPipelineSummary(),
        ];
    }

    protected function getPipelineSummary()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $totalApplications = $query->count();

        $activeApplications = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['under_review', 'contract_sent', 'pending']);

        if ($this->userId && $this->userType === 'merchant') {
            $activeApplications->where('id', $this->userId);
        }

        $activeApplications = $activeApplications->count();

        $completedApplications = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'rejected', 'suspended', 'blacklisted']);

        if ($this->userId && $this->userType === 'merchant') {
            $completedApplications->where('id', $this->userId);
        }

        $completedApplications = $completedApplications->count();

        $avgProcessingTime = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'rejected']);

        if ($this->userId && $this->userType === 'merchant') {
            $avgProcessingTime->where('id', $this->userId);
        }

        $avgProcessingTime = $avgProcessingTime->avg(DB::raw('TIMESTAMPDIFF(HOUR, created_at, updated_at)'));

        return [
            'total_applications' => $totalApplications,
            'active_applications' => $activeApplications,
            'completed_applications' => $completedApplications,
            'completion_rate' => $totalApplications > 0 ? round(($completedApplications / $totalApplications) * 100) : 0,
            'avg_processing_time' => $this->formatHours($avgProcessingTime ?? 0),
            'sla_compliance_rate' => $this->calculateOverallSlaCompliance(),
        ];
    }

    protected function getApplicationTrend()
    {
        $endDate = $this->dateRange['to'];
        $startDate = $endDate->copy()->subDays(29);

        $query = Merchant::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $trendData = $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as applications'),
            DB::raw('SUM(CASE WHEN status IN ("approved", "active") THEN 1 ELSE 0 END) as approved'),
            DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $applications = [];
        $approved = [];
        $rejected = [];

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format(dateFormat());
            $dayData = $trendData->firstWhere('date', $dateStr);

            $labels[] = $currentDate->format('M d');
            $applications[] = $dayData ? (int) $dayData->applications : 0;
            $approved[] = $dayData ? (int) $dayData->approved : 0;
            $rejected[] = $dayData ? (int) $dayData->rejected : 0;

            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Applications',
                    'data' => $applications,
                ],
                [
                    'name' => 'Approved',
                    'data' => $approved,
                ],
                [
                    'name' => 'Rejected',
                    'data' => $rejected,
                ],
            ],
        ];
    }

    protected function getProcessingTimes()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'rejected']);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $processingData = $query->select(
            DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_total_time'),
            DB::raw('AVG(CASE WHEN status IN ("approved", "active") THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) ELSE NULL END) as avg_approval_time'),
            DB::raw('AVG(CASE WHEN status = "rejected" THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) ELSE NULL END) as avg_rejection_time'),
            DB::raw('MAX(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as max_processing_time'),
            DB::raw('MIN(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as min_processing_time')
        )
            ->first();

        return [
            'avg_total' => $this->formatHours($processingData->avg_total_time ?? 0),
            'avg_approval' => $this->formatHours($processingData->avg_approval_time ?? 0),
            'avg_rejection' => $this->formatHours($processingData->avg_rejection_time ?? 0),
            'max' => $this->formatHours($processingData->max_processing_time ?? 0),
            'min' => $this->formatHours($processingData->min_processing_time ?? 0),
        ];
    }

    protected function getUnderwritingStats()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $stats = $query->select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "under_review" THEN 1 ELSE 0 END) as under_review'),
            DB::raw('SUM(CASE WHEN status = "contract_sent" THEN 1 ELSE 0 END) as contract_sent'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_documents'),
            DB::raw('AVG(CASE WHEN status = "under_review" THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) ELSE NULL END) as avg_review_time')
        )
            ->first();

        return [
            'total_pending' => $stats->under_review + $stats->contract_sent + $stats->pending_documents,
            'under_review' => $stats->under_review,
            'contract_sent' => $stats->contract_sent,
            'pending_documents' => $stats->pending_documents,
            'avg_review_time' => $this->formatHours($stats->avg_review_time ?? 0),
        ];
    }

    protected function calculateOverallSlaCompliance()
    {
        $slaMetrics = $this->calculateSlaMetrics();

        $totalCompliance = (
            $slaMetrics['underwriting']['compliance'] +
            $slaMetrics['approval']['compliance'] +
            $slaMetrics['disbursement']['compliance']
        );

        return round($totalCompliance / 3);
    }

    protected function getSlaTrendData()
    {
        $trendData = [
            'labels' => [],
            'underwriting' => [],
            'approval' => [],
            'disbursement' => [],
        ];

        $days = 30;

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('M d');
            $trendData['labels'][] = $date;

            // Define the start and end of this day
            $dayStart = Carbon::now()->subDays($i)->startOfDay();
            $dayEnd = Carbon::now()->subDays($i)->endOfDay();

            // Underwriting compliance for this day
            $underwritingQuery = Merchant::whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted']);

            if ($this->userId && $this->userType === 'merchant') {
                $underwritingQuery->where('id', $this->userId);
            }

            $totalProcessed = $underwritingQuery->count();

            $compliantUnderwriting = Merchant::whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted'])
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) * 0.6 <= 6');

            if ($this->userId && $this->userType === 'merchant') {
                $compliantUnderwriting->where('id', $this->userId);
            }

            $compliantUnderwriting = $compliantUnderwriting->count();

            $trendData['underwriting'][] = $totalProcessed > 0
                ? round(($compliantUnderwriting / $totalProcessed) * 100)
                : 100;

            // Approval compliance for this day
            $compliantApproval = Merchant::whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted'])
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) * 0.3 <= 4');

            if ($this->userId && $this->userType === 'merchant') {
                $compliantApproval->where('id', $this->userId);
            }

            $compliantApproval = $compliantApproval->count();

            $trendData['approval'][] = $totalProcessed > 0
                ? round(($compliantApproval / $totalProcessed) * 100)
                : 100;

            // Disbursement compliance for this day
            $compliantDisbursement = Merchant::whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted'])
                ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) * 0.1 <= 8');

            if ($this->userId && $this->userType === 'merchant') {
                $compliantDisbursement->where('id', $this->userId);
            }

            $compliantDisbursement = $compliantDisbursement->count();

            $trendData['disbursement'][] = $totalProcessed > 0
                ? round(($compliantDisbursement / $totalProcessed) * 100)
                : 100;
        }

        return $trendData;
    }

    public function getEwsData()
    {
        return [
            'payment_behavior' => $this->getPaymentBehavior(),
            'device_anomalies' => $this->getDeviceAnomalies(),
            'alerts' => $this->getAlertDistribution(),
            'concentration' => $this->getConcentrationRisks(),
        ];
    }

    public function getRiskScores()
    {
        $portfolioRisk = $this->calculatePortfolioRisk();
        $pipelineRisk = $this->calculatePipelineRisk();
        $ewsRisk = $this->calculateEwsRisk();

        $overallRisk = round(($portfolioRisk + $pipelineRisk + $ewsRisk) / 3);

        return [
            'portfolio_risk' => $portfolioRisk,
            'pipeline_risk' => $pipelineRisk,
            'ews_risk' => $ewsRisk,
            'overall_risk' => $overallRisk,
            'risk_level' => $this->getRiskLevel($overallRisk),
        ];
    }

    public function buildRiskAlerts()
    {
        $alerts = [];

        // If user-specific view, only show relevant alerts
        if ($this->userId) {
            if ($this->userType === 'merchant') {
                // Merchant-specific alerts
                $merchant = Merchant::where('user_id', $this->userId)->first();
                if ($merchant) {
                    // 1. Merchant CR Expiry within 30 days
                    $expiringCRs = $this->getExpiringCommercialRegistrations();
                    if ($expiringCRs->contains('id', $this->userId)) {
                        $alerts[] = $this->createExpiringCRAlert($expiringCRs->where('id', $this->userId));
                    }

                    // 2. Low Capital Merchant
                    $lowCapitalMerchants = $this->getLowCapitalMerchants();
                    if ($lowCapitalMerchants->contains('id', $this->userId)) {
                        $alerts[] = $this->createLowCapitalAlert($lowCapitalMerchants->where('id', $this->userId));
                    }

                    // 3. High Activity Concentration Risk
                    $highActivityMerchants = $this->getHighActivityMerchants();
                    if ($highActivityMerchants->contains('id', $this->userId)) {
                        $alerts[] = $this->createHighActivityAlert($highActivityMerchants->where('id', $this->userId));
                    }
                }
            }

            // 4. High Individual Exposure (for any user type)
            $highExposureAccounts = $this->getHighExposureAccounts();
            if ($highExposureAccounts->contains('user_id', $this->userId)) {
                $alerts[] = $this->createHighExposureAlert($highExposureAccounts->where('user_id', $this->userId));
            }

            // 5. Critical DPD Accounts (for any user type)
            $criticalDPDAccounts = $this->getCriticalDPDAccounts();
            if (isset($criticalDPDAccounts[$this->userId])) {
                $alerts[] = $this->createCriticalDPDAlert(collect([$this->userId => $criticalDPDAccounts[$this->userId]]));
            }

            // For user-specific view, skip global alerts like NPL Ratio and Utilization
        } else {
            // Global alerts (no user_id specified)

            // 1. Critical: Merchant CR Expiry within 30 days
            $expiringCRs = $this->getExpiringCommercialRegistrations();
            if ($expiringCRs->isNotEmpty()) {
                $alerts[] = $this->createExpiringCRAlert($expiringCRs);
            }

            // 2. Low Capital Merchants
            $lowCapitalMerchants = $this->getLowCapitalMerchants();
            if ($lowCapitalMerchants->isNotEmpty()) {
                $alerts[] = $this->createLowCapitalAlert($lowCapitalMerchants);
            }

            // 3. High Activity Concentration Risk
            $highActivityMerchants = $this->getHighActivityMerchants();
            if ($highActivityMerchants->isNotEmpty()) {
                $alerts[] = $this->createHighActivityAlert($highActivityMerchants);
            }

            // 4. High Individual Exposure
            $highExposureAccounts = $this->getHighExposureAccounts();
            if ($highExposureAccounts->isNotEmpty()) {
                $alerts[] = $this->createHighExposureAlert($highExposureAccounts);
            }

            // 5. NPL Ratio Alert
            $nplRatio = $this->calculateNplRatio();
            if ($nplRatio['current'] > $this->thresholds['high_npl']) {
                $alerts[] = $this->createNPLAlert($nplRatio);
            }

            // 6. High Utilization Alert
            $utilization = $this->calculateUtilization();
            if ($utilization['rate'] > $this->thresholds['high_utilization']) {
                $alerts[] = $this->createUtilizationAlert($utilization);
            }

            // 7. Critical DPD Accounts
            $criticalDPDAccounts = $this->getCriticalDPDAccounts();
            if ($criticalDPDAccounts->isNotEmpty()) {
                $alerts[] = $this->createCriticalDPDAlert($criticalDPDAccounts);
            }
        }

        // Default info alert if no critical alerts
        if (empty($alerts)) {
            $alerts[] = $this->createNoAlertsInfo();
        }

        return $alerts;
    }

    public function buildRiskFlags()
    {
        $flags = [];

        // If user-specific view, show user-specific flags
        if ($this->userId) {
            if ($this->userType === 'merchant') {
                // Merchant-specific flag
                $merchant = Merchant::where('user_id', $this->userId)->first();
                if ($merchant) {
                    $riskScore = $this->calculateMerchantRiskScore($merchant, Carbon::today());
                    $highestRiskMerchant = [
                        'merchant' => $merchant,
                        'risk_score' => $riskScore['score'],
                        'factors' => $riskScore['factors'],
                    ];
                    $flags[] = $this->createHighestRiskFlag($highestRiskMerchant);
                }
            }
        } else {
            // Global flags (no user_id specified)

            // 1. Highest Risk Merchant
            $highestRiskMerchant = $this->getHighestRiskMerchant();
            if ($highestRiskMerchant) {
                $flags[] = $this->createHighestRiskFlag($highestRiskMerchant);
            }

            // 2. Industry Concentration Risk
            $industryConcentration = $this->getIndustryConcentration();
            if ($industryConcentration['is_high']) {
                $flags[] = $this->createIndustryConcentrationFlag($industryConcentration);
            }

            // 3. Geographic Concentration Risk
            $geoConcentration = $this->getGeographicConcentration();
            if ($geoConcentration['is_high']) {
                $flags[] = $this->createGeographicConcentrationFlag($geoConcentration);
            }
        }

        return $flags;
    }

    // ==================== CORE CALCULATION METHODS ====================

    protected function calculateExposure()
    {
        $query = SchedulePayment::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $currentExposure = $query->sum('instalment_amount');

        $previousQuery = SchedulePayment::whereBetween('created_at', [$this->dateRange['previous_from'], $this->dateRange['previous_to']]);

        if ($this->userId) {
            $previousQuery->where('user_id', $this->userId);
        }

        $previousExposure = $previousQuery->sum('instalment_amount');

        $change = $previousExposure > 0 ?
            round((($currentExposure - $previousExposure) / $previousExposure) * 100, 1) : 0;

        $breakdownQuery = SchedulePayment::join('users', 'schedule_payments.user_id', '=', 'users.id')
            ->whereBetween('schedule_payments.created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $breakdownQuery->where('schedule_payments.user_id', $this->userId);
        }

        $breakdown = $breakdownQuery->select(
            DB::raw('CASE WHEN users.user_type = "merchant" THEN "Business Loans" ELSE "Personal Loans" END as loan_type'),
            DB::raw('SUM(instalment_amount) as total_amount')
        )
            ->groupBy('loan_type')
            ->pluck('total_amount', 'loan_type')
            ->toArray();

        return [
            'total' => $currentExposure,
            'change' => $change,
            'breakdown' => array_merge([
                'Personal Loans' => $breakdown['Personal Loans'] ?? 0,
                'Business Loans' => $breakdown['Business Loans'] ?? 0,
                'Asset Finance' => 0,
                'Micro Loans' => 0,
            ]),
        ];
    }

    protected function calculateUtilization()
    {
        $totalLimit = $this->thresholds['total_credit_limit'];

        $query = SchedulePayment::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->where('payment_status', '!=', 'paid');

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $usedAmount = $query->sum('instalment_amount');

        $previousQuery = SchedulePayment::whereBetween('created_at', [$this->dateRange['previous_from'], $this->dateRange['previous_to']])
            ->where('payment_status', '!=', 'paid');

        if ($this->userId) {
            $previousQuery->where('user_id', $this->userId);
        }

        $previousUsedAmount = $previousQuery->sum('instalment_amount');

        $rate = $totalLimit > 0 ? round(($usedAmount / $totalLimit) * 100, 1) : 0;
        $previousRate = $totalLimit > 0 ? round(($previousUsedAmount / $totalLimit) * 100, 1) : 0;
        $change = round($rate - $previousRate, 1);

        return [
            'rate' => $rate,
            'change' => $change,
            'available' => $totalLimit - $usedAmount,
            'used' => $usedAmount,
        ];
    }

    protected function getDpdBuckets()
    {
        $query = SchedulePayment::whereBetween('due_date', [$this->dateRange['from']->subDays(90), $this->dateRange['to']]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $dpdData = $query->select(
            DB::raw('CASE 
                    WHEN payment_status = "paid" OR payment_status = "current" THEN "Current"
                    WHEN late_days BETWEEN 1 AND 30 THEN "1-30"
                    WHEN late_days BETWEEN 31 AND 60 THEN "31-60" 
                    WHEN late_days BETWEEN 61 AND 90 THEN "61-90"
                    ELSE "90+"
                END as dpd_bucket'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('dpd_bucket')
            ->pluck('count', 'dpd_bucket')
            ->toArray();

        $total = array_sum($dpdData);

        if ($total > 0) {
            return [
                'labels' => ['Current', '1-30', '31-60', '61-90', '90+'],
                'data' => [
                    round(($dpdData['Current'] ?? 0) / $total * 100),
                    round(($dpdData['1-30'] ?? 0) / $total * 100),
                    round(($dpdData['31-60'] ?? 0) / $total * 100),
                    round(($dpdData['61-90'] ?? 0) / $total * 100),
                    round(($dpdData['90+'] ?? 0) / $total * 100),
                ],
                'colors' => ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#DC2626'],
                'counts' => $dpdData,
            ];
        }

        return [
            'labels' => ['Current', '1-30', '31-60', '61-90', '90+'],
            'data' => [100, 0, 0, 0, 0],
            'colors' => ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#DC2626'],
            'counts' => ['Current' => 0],
        ];
    }

    protected function calculateNplRatio()
    {
        $query = SchedulePayment::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $totalLoans = $query->count();

        $nplQuery = SchedulePayment::where('late_days', '>=', 90)
            ->whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $nplQuery->where('user_id', $this->userId);
        }

        $nplLoans = $nplQuery->count();

        $previousQuery = SchedulePayment::whereBetween('created_at', [$this->dateRange['previous_from'], $this->dateRange['previous_to']]);

        if ($this->userId) {
            $previousQuery->where('user_id', $this->userId);
        }

        $previousTotalLoans = $previousQuery->count();

        $previousNplQuery = SchedulePayment::where('late_days', '>=', 90)
            ->whereBetween('created_at', [$this->dateRange['previous_from'], $this->dateRange['previous_to']]);

        if ($this->userId) {
            $previousNplQuery->where('user_id', $this->userId);
        }

        $previousNplLoans = $previousNplQuery->count();

        $currentRatio = $totalLoans > 0 ? round(($nplLoans / $totalLoans) * 100, 1) : 0;
        $previousRatio = $previousTotalLoans > 0 ? round(($previousNplLoans / $previousTotalLoans) * 100, 1) : 0;
        $change = round($currentRatio - $previousRatio, 1);

        return [
            'current' => $currentRatio,
            'previous' => $previousRatio,
            'change' => $change,
            'npl_count' => $nplLoans,
            'total_count' => $totalLoans,
        ];
    }

    protected function calculateChargeOffRate()
    {
        $query = SchedulePayment::where('payment_status', 'charged_off')
            ->whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $chargedOffAmount = $query->sum('instalment_amount');

        $avgQuery = SchedulePayment::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $avgQuery->where('user_id', $this->userId);
        }

        $averagePortfolio = $avgQuery->avg('instalment_amount');

        $currentRate = $averagePortfolio > 0 ? round(($chargedOffAmount / $averagePortfolio) * 100, 1) : 0;

        return [
            'current' => $currentRate,
            'target' => $this->thresholds['charge_off_target'],
            'charged_off_amount' => $chargedOffAmount,
        ];
    }

    protected function getVintageCurves()
    {
        $query = SchedulePayment::whereBetween('created_at', [$this->dateRange['from']->subMonths(12), $this->dateRange['to']]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $vintageData = $query->select(
            DB::raw("CONCAT(YEAR(created_at), ' Q', QUARTER(created_at)) as cohort"),
            DB::raw('AVG(CASE WHEN late_days > 0 THEN 1 ELSE 0 END) as delinquency_rate'),
            DB::raw('COUNT(*) as total_loans')
        )
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->get();

        $cohorts = [];
        foreach ($vintageData as $data) {
            $cohorts[$data->cohort] = [
                round($data->delinquency_rate * 100, 1),
                round($data->delinquency_rate * 120, 1), // Projected
                round($data->delinquency_rate * 150, 1),
                round($data->delinquency_rate * 180, 1),
                round($data->delinquency_rate * 200, 1),
            ];
        }

        $series = [];
        foreach ($cohorts as $name => $data) {
            $series[] = [
                'name' => $name,
                'data' => $data,
            ];
        }

        return [
            'labels' => ['Month 1', 'Month 3', 'Month 6', 'Month 9', 'Month 12'],
            'series' => $series,
        ];
    }

    // ==================== ALERT-SPECIFIC METHODS ====================

    protected function getExpiringCommercialRegistrations()
    {
        $today = Carbon::today();

        $query = Merchant::with(['user', 'businessType'])
            ->whereHas('user', function ($q) {
                $q->where('user_type', 'merchant');
            });

        // If user-specific, filter by user_id
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        return $query->get()
            ->filter(function ($merchant) use ($today) {
                $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
                if (! $crData || ! isset($crData['status']['confirmationDate']['gregorian'])) {
                    return false;
                }

                $expiryDate = Carbon::parse($crData['status']['confirmationDate']['gregorian']);

                return $expiryDate->diffInDays($today, false) >= -30 && $expiryDate->diffInDays($today, false) < 0;
            });
    }

    protected function getLowCapitalMerchants()
    {
        $query = Merchant::with(['user', 'businessType'])
            ->whereHas('user', function ($q) {
                $q->where('user_type', 'merchant');
            });

        // If user-specific, filter by user_id
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        return $query->get()
            ->filter(function ($merchant) {
                $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
                if (! $crData || ! isset($crData['capital']['contributionCapital']['cashCapital'])) {
                    return false;
                }

                $capital = $crData['capital']['contributionCapital']['cashCapital'];

                return $capital < $this->thresholds['low_capital'];
            });
    }

    protected function getHighActivityMerchants()
    {
        $query = Merchant::with(['user', 'businessType'])
            ->whereHas('user', function ($q) {
                $q->where('user_type', 'merchant');
            });

        // If user-specific, filter by user_id
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        return $query->get()
            ->filter(function ($merchant) {
                $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
                if (! $crData || ! isset($crData['activities'])) {
                    return false;
                }

                $activityCount = count($crData['activities']);

                return $activityCount > $this->thresholds['multiple_activities'];
            });
    }

    protected function getHighExposureAccounts()
    {
        $query = SchedulePayment::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->select('user_id', DB::raw('SUM(instalment_amount) as total_exposure'))
            ->groupBy('user_id')
            ->having('total_exposure', '>', $this->thresholds['large_exposure'])
            ->with(['user']);

        // If user-specific, filter by user_id
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        return $query->get();
    }

    protected function getCriticalDPDAccounts()
    {
        $today = Carbon::today();

        $query = SchedulePayment::whereIn('payment_status', ['due', 'late'])
            ->whereRaw('DATEDIFF(?, due_date) > ?', [$today, $this->thresholds['critical_dpd']])
            ->with(['user']);

        // If user-specific, filter by user_id
        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        return $query->get()
            ->groupBy('user_id');
    }

    // ==================== ALERT CREATION METHODS ====================

    protected function createExpiringCRAlert(Collection $expiringCRs)
    {
        $merchantNames = $expiringCRs->take(3)->map(function ($merchant) {
            $name = $merchant->user->business_name ?? $merchant->user->name;

            return "<a href='".route('supplierProfile', $merchant->id)."' class='underline'>".e($name).'</a>';
        })->implode(', ');

        $remainingCount = $expiringCRs->count() - 3;
        $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : '';

        return [
            'type' => 'critical',
            'title' => "{$expiringCRs->count()} Merchant CRs Expiring Soon",
            'description' => "Commercial Registrations expiring within 30 days. Affected merchants: {$merchantNames}{$additionalText}",
            'time' => 'Just now',
            'icon' => 'ki-filled ki-calendar-8',
            'additional_info' => [
                'affected_merchants_count' => $expiringCRs->count(),
                'expiry_threshold' => '30 days',
                'top_affected_merchants' => $expiringCRs->map(function ($merchant) {
                    $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
                    $expiryDate = $crData ? Carbon::parse($crData['status']['confirmationDate']['gregorian']) : null;

                    return [
                        'name' => $merchant->user->business_name ?? $merchant->user->name,
                        'link' => route('supplierProfile', $merchant->id),
                        'days_until_expiry' => $expiryDate ? abs(Carbon::today()->diffInDays($expiryDate, false)) : 'N/A',
                        'cr_number' => $crData['crNumber'] ?? 'N/A',
                    ];
                })->toArray(),
            ],
        ];
    }

    protected function createLowCapitalAlert(Collection $lowCapitalMerchants)
    {
        $merchantNames = $lowCapitalMerchants->take(3)->map(function ($merchant) {
            $name = $merchant->user->business_name ?? $merchant->user->name;

            return "<a href='".route('supplierProfile', $merchant->id)."' class='underline'>".e($name).'</a>';
        })->implode(', ');

        $remainingCount = $lowCapitalMerchants->count() - 3;
        $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : '';

        return [
            'type' => 'high',
            'title' => "{$lowCapitalMerchants->count()} Low Capital Merchants",
            'description' => 'Merchants with capital below <span>  '.number_format($this->thresholds['low_capital']).". High risk of default. Affected: {$merchantNames}{$additionalText}",
            'time' => 'Recently',
            'icon' => 'ki-filled ki-dollar',
            'additional_info' => [
                'low_capital_count' => $lowCapitalMerchants->count(),
                'capital_threshold' => '<span class="icon-saudi_riyal">  '.number_format($this->thresholds['low_capital']),
                'top_low_capital_merchants' => $lowCapitalMerchants->map(function ($merchant) {
                    $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
                    $capital = $crData['capital']['contributionCapital']['cashCapital'] ?? 0;

                    return [
                        'name' => $merchant->user->business_name ?? $merchant->user->name,
                        'link' => route('supplierProfile', $merchant->id),
                        'capital' => '<span class="icon-saudi_riyal">  '.number_format($capital),
                        'cr_number' => $crData['crNumber'] ?? 'N/A',
                    ];
                })->toArray(),
            ],
        ];
    }

    protected function createHighActivityAlert(Collection $highActivityMerchants)
    {
        $merchantNames = $highActivityMerchants->take(2)->map(function ($merchant) {
            $name = $merchant->user->business_name ?? $merchant->user->name;

            return "<a href='".route('supplierProfile', $merchant->id)."' class='underline'>".e($name).'</a>';
        })->implode(', ');

        $remainingCount = $highActivityMerchants->count() - 2;
        $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : '';

        return [
            'type' => 'medium',
            'title' => 'Activity Concentration Risk',
            'description' => "{$highActivityMerchants->count()} merchants with excessive business activities (>{$this->thresholds['multiple_activities']}). Potential focus risk: {$merchantNames}{$additionalText}",
            'time' => 'Recently',
            'icon' => 'ki-filled ki-category',
            'additional_info' => [
                'high_activity_count' => $highActivityMerchants->count(),
                'activity_threshold' => $this->thresholds['multiple_activities'].' activities',
                'top_high_activity_merchants' => $highActivityMerchants->map(function ($merchant) {
                    $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
                    $activityCount = $crData ? count($crData['activities']) : 0;

                    return [
                        'name' => $merchant->user->business_name ?? $merchant->user->name,
                        'link' => route('supplierProfile', $merchant->id),
                        'activity_count' => $activityCount,
                        'cr_number' => $crData['crNumber'] ?? 'N/A',
                    ];
                })->toArray(),
            ],
        ];
    }

    protected function createHighExposureAlert(Collection $highExposureAccounts)
    {
        $accountNames = $highExposureAccounts->take(3)->map(function ($account) {
            $name = $account->user->business_name ?? $account->user->name;

            return "<a href='".route('customerProfile', $account->user_id)."' class='underline'>".e($name).'</a>';
        })->implode(', ');

        $remainingCount = $highExposureAccounts->count() - 3;
        $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : '';

        return [
            'type' => 'high',
            'title' => 'High Individual Exposure Accounts',
            'description' => "{$highExposureAccounts->count()} accounts with exposure > <span>  ".number_format($this->thresholds['large_exposure']).". High concentration risk: {$accountNames}{$additionalText}",
            'time' => 'Just now',
            'icon' => 'ki-filled ki-chart-line',
            'additional_info' => [
                'high_exposure_count' => $highExposureAccounts->count(),
                'exposure_threshold' => '<span>  '.number_format($this->thresholds['large_exposure']),
                'top_high_exposure_accounts' => $highExposureAccounts->map(function ($account) {
                    return [
                        'name' => $account->user->business_name ?? $account->user->name,
                        'link' => route('customerProfile', $account->user_id),
                        'exposure' => '<span>  '.number_format($account->total_exposure),
                        'user_type' => $account->user->user_type,
                    ];
                })->toArray(),
            ],
        ];
    }

    protected function createNPLAlert(array $nplRatio)
    {
        return [
            'type' => 'critical',
            'title' => 'NPL Ratio Exceeding Threshold',
            'description' => "Current NPL ratio of {$nplRatio['current']}% exceeds target of {$this->thresholds['high_npl']}%. Requires immediate portfolio review.",
            'time' => 'Just now',
            'icon' => 'ki-filled ki-chart-line',
            'additional_info' => [
                'current_npl' => $nplRatio['current'].'%',
                'target_npl' => $this->thresholds['high_npl'].'%',
                'variance' => round($nplRatio['current'] - $this->thresholds['high_npl'], 1).'%',
                'trend' => $nplRatio['change'] > 0 ? 'Increasing' : 'Decreasing',
                'change' => $this->formatChange($nplRatio['change']),
            ],
        ];
    }

    protected function createUtilizationAlert(array $utilization)
    {
        return [
            'type' => 'high',
            'title' => 'High Portfolio Utilization',
            'description' => "Utilization rate of {$utilization['rate']}% approaching capacity limits. Available: {$utilization['available']}",
            'time' => 'Recently',
            'icon' => 'ki-filled ki-chart-pie-4',
            'additional_info' => [
                'current_utilization' => $utilization['rate'].'%',
                'available_capacity' => $utilization['available'],
                'used_capacity' => $utilization['used'],
                'trend' => $utilization['change'] > 0 ? 'Increasing' : 'Decreasing',
                'change' => $this->formatChange($utilization['change']),
            ],
        ];
    }

    protected function createCriticalDPDAlert(Collection $criticalDPDAccounts)
    {
        $today = Carbon::today();
        $accountNames = $criticalDPDAccounts->take(3)->map(function ($payments, $userId) {
            $user = $payments->first()->user;
            $name = $user->business_name ?? $user->name;

            return "<a href='".route('customerProfile', $userId)."' class='underline'>".e($name).'</a>';
        })->implode(', ');

        $remainingCount = $criticalDPDAccounts->count() - 3;
        $additionalText = $remainingCount > 0 ? " and {$remainingCount} more" : '';

        return [
            'type' => 'critical',
            'title' => "{$criticalDPDAccounts->count()} Accounts Exceeded {$this->thresholds['critical_dpd']} DPD",
            'description' => "Requires immediate attention — high risk of default. Affected accounts: {$accountNames}{$additionalText}",
            'time' => 'Just now',
            'icon' => 'ki-filled ki-information-2',
            'additional_info' => [
                'affected_accounts_count' => $criticalDPDAccounts->count(),
                'dpd_threshold' => $this->thresholds['critical_dpd'].' days',
                'top_affected_accounts' => $criticalDPDAccounts->map(function ($payments, $userId) use ($today) {
                    $user = $payments->first()->user;
                    $maxDPD = $payments->max(function ($payment) use ($today) {
                        return abs($today->diffInDays($payment->due_date, false));
                    });

                    return [
                        'name' => $user->business_name ?? $user->name,
                        'link' => route('customerProfile', $userId),
                        'max_dpd' => $maxDPD.' days',
                        'total_exposure' => '<span>  '.number_format($payments->sum('instalment_amount')),
                    ];
                })->toArray(),
            ],
        ];
    }

    protected function createNoAlertsInfo()
    {
        $today = Carbon::today();

        return [
            'type' => 'info',
            'title' => 'No Critical Risk Alerts',
            'description' => 'All monitored risk indicators are within configured thresholds',
            'time' => $today->format('M d, Y H:i'),
            'icon' => 'ki-filled ki-check-circle',
            'additional_info' => [
                'monitoring_status' => 'All systems normal',
                'last_checked' => $today->format('Y-m-d H:i:s'),
                'active_monitors' => 'CR Expiry, Capital Adequacy, Activity Concentration, Exposure Limits, NPL Ratio, Utilization, DPD',
            ],
        ];
    }

    // ==================== FLAG-SPECIFIC METHODS ====================

    protected function getHighestRiskMerchant()
    {
        $today = Carbon::today();

        $query = Merchant::with(['user', 'businessType'])
            ->whereHas('user', function ($q) {
                $q->where('user_type', 'merchant');
            });

        // If user-specific, filter by user_id
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        return $query->get()
            ->map(function ($merchant) use ($today) {
                $riskScore = $this->calculateMerchantRiskScore($merchant, $today);

                return [
                    'merchant' => $merchant,
                    'risk_score' => $riskScore['score'],
                    'factors' => $riskScore['factors'],
                ];
            })
            ->sortByDesc('risk_score')
            ->first();
    }

    protected function calculateMerchantRiskScore($merchant, $today)
    {
        $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;
        $factors = [];
        $riskScore = 0;

        // Capital adequacy (30% weight)
        $capital = $crData['capital']['contributionCapital']['cashCapital'] ?? 0;
        $capitalScore = $capital < 50000 ? 80 : ($capital < 100000 ? 40 : 20);
        $riskScore += $capitalScore * 0.3;
        $factors['capital_adequacy'] = $capitalScore;

        // Activity concentration (25% weight)
        $activityCount = $crData ? count($crData['activities']) : 0;
        $activityScore = $activityCount > 20 ? 70 : ($activityCount > 10 ? 40 : 20);
        $riskScore += $activityScore * 0.25;
        $factors['activity_concentration'] = $activityScore;

        // CR expiry (25% weight)
        if ($crData && isset($crData['status']['confirmationDate']['gregorian'])) {
            $expiryDate = Carbon::parse($crData['status']['confirmationDate']['gregorian']);
            $daysToExpiry = $expiryDate->diffInDays($today, false);
            $expiryScore = $daysToExpiry > -30 ? 90 : ($daysToExpiry > -90 ? 50 : 20);
            $riskScore += $expiryScore * 0.25;
            $factors['cr_expiry'] = $expiryScore;
        }

        // Business type risk (20% weight)
        $businessTypeRisk = strtolower($merchant->businessType->risk_level ?? 'medium');
        $typeScore = match ($businessTypeRisk) {
            'very high' => 90,
            'high' => 70,
            'medium' => 40,
            'medium-low' => 25,
            'low' => 15,
            default => 40
        };
        $riskScore += $typeScore * 0.2;
        $factors['business_type_risk'] = $typeScore;

        return [
            'score' => $riskScore,
            'factors' => $factors,
        ];
    }

    protected function getIndustryConcentration()
    {
        $query = Merchant::with(['businessType'])
            ->whereHas('user', function ($q) {
                $q->where('user_type', 'merchant');
            });

        // If user-specific, only return data for that user
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $industryConcentration = $query->get()
            ->groupBy('business_type_id')
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(3);

        $totalMerchants = $query->count();

        $concentrationPercentage = $industryConcentration->sum() > 0 ?
            round(($industryConcentration->sum() / $totalMerchants) * 100) : 0;

        return [
            'is_high' => $concentrationPercentage > 60,
            'percentage' => $concentrationPercentage,
            'industries' => $industryConcentration->map(function ($count, $typeId) {
                $type = BusinessType::find($typeId);

                return [
                    'name' => $type->name ?? 'Unknown',
                    'count' => $count,
                    'risk_level' => $type->risk_level ?? 'medium',
                ];
            })->values()->toArray(),
            'total_merchants' => $totalMerchants,
        ];
    }

    protected function getGeographicConcentration()
    {
        $query = Merchant::with(['user'])
            ->whereHas('user', function ($q) {
                $q->where('user_type', 'merchant');
            });

        // If user-specific, only return data for that user
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $geoConcentration = $query->get()
            ->filter(function ($merchant) {
                $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;

                return $crData && isset($crData['headquarterCityName']);
            })
            ->groupBy(function ($merchant) {
                $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;

                return $crData['headquarterCityName'] ?? 'Unknown';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(3);

        $totalMerchants = $query->count();

        $concentrationPercentage = $geoConcentration->sum() > 0 ?
            round(($geoConcentration->sum() / $totalMerchants) * 100) : 0;

        return [
            'is_high' => $concentrationPercentage > 70,
            'percentage' => $concentrationPercentage,
            'cities' => $geoConcentration->map(function ($count, $city) use ($totalMerchants) {
                return [
                    'city' => $city,
                    'count' => $count,
                    'percentage' => round(($count / $totalMerchants) * 100, 1).'%',
                ];
            })->toArray(),
            'total_merchants' => $totalMerchants,
        ];
    }

    // ==================== FLAG CREATION METHODS ====================

    protected function createHighestRiskFlag($highestRiskMerchant)
    {
        $merchant = $highestRiskMerchant['merchant'];
        $name = $merchant->user->business_name ?? $merchant->user->name;
        $crData = $merchant->goverment_data ? json_decode($merchant->goverment_data, true) : null;

        return [
            'type' => 'highest_risk',
            'title' => 'Highest Risk Merchant Identified',
            'description' => "Merchant <a href='".route('supplierProfile', $merchant->id)."' class='underline'>".e($name).'</a> has the highest risk score ('.round($highestRiskMerchant['risk_score']).'/100). Requires immediate review.',
            'icon' => 'ki-filled ki-shield-cross',
            'tags' => ['High Risk', 'Priority Review', round($highestRiskMerchant['risk_score']).'/100'],
            'additional_info' => [
                'merchant_name' => $name,
                'risk_score' => round($highestRiskMerchant['risk_score']),
                'risk_factors' => $highestRiskMerchant['factors'],
                'capital_adequacy' => '<span>  '.number_format($crData['capital']['contributionCapital']['cashCapital'] ?? 0),
                'business_activities' => $crData ? count($crData['activities']) : 0,
                'business_type_risk' => ucwords($merchant->businessType->risk_level ?? 'medium'),
                'cr_number' => $crData['crNumber'] ?? 'N/A',
                'cr_expiry' => $crData ? Carbon::parse($crData['status']['confirmationDate']['gregorian'])->format('M d, Y') : 'N/A',
            ],
        ];
    }

    protected function createIndustryConcentrationFlag($industryConcentration)
    {
        $industryNames = collect($industryConcentration['industries'])->take(3)->map(function ($industry) {
            return "{$industry['name']} ({$industry['count']})";
        })->implode(', ');

        return [
            'type' => 'concentration_risk',
            'title' => 'Industry Concentration Risk',
            'description' => "Top 3 industries represent {$industryConcentration['percentage']}% of portfolio. Industries: {$industryNames}",
            'icon' => 'ki-filled ki-chart-pie-4',
            'tags' => ['Concentration', 'Diversification', $industryConcentration['percentage'].'%'],
            'additional_info' => [
                'total_merchants' => $industryConcentration['total_merchants'],
                'concentration_percentage' => $industryConcentration['percentage'].'%',
                'top_industries' => $industryConcentration['industries'],
            ],
        ];
    }

    protected function createGeographicConcentrationFlag($geoConcentration)
    {
        $cityNames = collect($geoConcentration['cities'])->take(3)->map(function ($city) {
            return "{$city['city']} ({$city['count']})";
        })->implode(', ');

        return [
            'type' => 'geographic_risk',
            'title' => 'Geographic Concentration Risk',
            'description' => "Top 3 cities represent {$geoConcentration['percentage']}% of portfolio. Cities: {$cityNames}",
            'icon' => 'ki-filled ki-geolocation',
            'tags' => ['Geography', 'Concentration', $geoConcentration['percentage'].'%'],
            'additional_info' => [
                'total_merchants' => $geoConcentration['total_merchants'],
                'concentration_percentage' => $geoConcentration['percentage'].'%',
                'top_cities' => $geoConcentration['cities'],
            ],
        ];
    }

    // ==================== PIPELINE & EWS METHODS ====================

    protected function getStatusDistribution()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $statusCounts = $query->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($statusCounts);

        if ($total > 0) {
            return [
                'under_review' => [
                    'percentage' => round(($statusCounts['under_review'] ?? 0) / $total * 100),
                    'count' => $statusCounts['under_review'] ?? 0,
                ],
                'contract_sent' => [
                    'percentage' => round(($statusCounts['contract_sent'] ?? 0) / $total * 100),
                    'count' => $statusCounts['contract_sent'] ?? 0,
                ],
                'active' => [
                    'percentage' => round(($statusCounts['active'] ?? 0) / $total * 100),
                    'count' => $statusCounts['active'] ?? 0,
                ],
                'pending' => [
                    'percentage' => round(($statusCounts['pending'] ?? 0) / $total * 100),
                    'count' => $statusCounts['pending'] ?? 0,
                ],
                'approved' => [
                    'percentage' => round(($statusCounts['approved'] ?? 0) / $total * 100),
                    'count' => $statusCounts['approved'] ?? 0,
                ],
                'rejected' => [
                    'percentage' => round(($statusCounts['rejected'] ?? 0) / $total * 100),
                    'count' => $statusCounts['rejected'] ?? 0,
                ],
                'suspended' => [
                    'percentage' => round(($statusCounts['suspended'] ?? 0) / $total * 100),
                    'count' => $statusCounts['suspended'] ?? 0,
                ],
                'blacklisted' => [
                    'percentage' => round(($statusCounts['blacklisted'] ?? 0) / $total * 100),
                    'count' => $statusCounts['blacklisted'] ?? 0,
                ],
                'total' => $total,
            ];
        }

        $emptyStatus = [
            'percentage' => 0,
            'count' => 0,
        ];

        return array_fill_keys(['under_review', 'contract_sent', 'active', 'pending', 'approved', 'rejected', 'suspended', 'blacklisted'], $emptyStatus) + ['total' => 0];
    }

    protected function calculateSlaMetrics()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted']);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $approvalTimeline = $query->select(
            DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_processing_time'),
            DB::raw('COUNT(*) as total_processed')
        )
            ->first();

        $avgProcessingHours = $approvalTimeline->avg_processing_time ?? 0;

        $underwritingCompliance = $this->calculateUnderwritingCompliance();
        $approvalCompliance = $this->calculateApprovalCompliance();
        $disbursementCompliance = $this->calculateDisbursementCompliance();

        // Get SLA trend data for the last 30 days
        $slaTrend = $this->getSlaTrendData();

        return [
            'underwriting' => [
                'current' => $this->formatHours($avgProcessingHours * 0.6),
                'target' => '6h',
                'compliance' => $underwritingCompliance,
                'trend' => $slaTrend['underwriting'],
            ],
            'approval' => [
                'current' => $this->formatHours($avgProcessingHours * 0.3),
                'target' => '4h',
                'compliance' => $approvalCompliance,
                'trend' => $slaTrend['approval'],
            ],
            'disbursement' => [
                'current' => $this->formatHours($avgProcessingHours * 0.1),
                'target' => '8h',
                'compliance' => $disbursementCompliance,
                'trend' => $slaTrend['disbursement'],
            ],
            'trend_data' => $slaTrend,
        ];
    }

    protected function getApprovalRates()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $approvalStats = $query->select(
            DB::raw('COUNT(*) as total_applications'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved'),
            DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as declined'),
            DB::raw('SUM(CASE WHEN status IN ("under_review", "contract_sent", "pending") THEN 1 ELSE 0 END) as pending'),
            DB::raw('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active'),
            DB::raw('SUM(CASE WHEN status = "suspended" THEN 1 ELSE 0 END) as suspended'),
            DB::raw('SUM(CASE WHEN status = "blacklisted" THEN 1 ELSE 0 END) as blacklisted')
        )
            ->first();

        $total = $approvalStats->total_applications ?: 1;

        return [
            'approved' => [
                'percentage' => round(($approvalStats->approved / $total) * 100),
                'count' => $approvalStats->approved,
            ],
            'declined' => [
                'percentage' => round(($approvalStats->declined / $total) * 100),
                'count' => $approvalStats->declined,
            ],
            'pending' => [
                'percentage' => round(($approvalStats->pending / $total) * 100),
                'count' => $approvalStats->pending,
            ],
            'active' => [
                'percentage' => round(($approvalStats->active / $total) * 100),
                'count' => $approvalStats->active,
            ],
            'suspended' => [
                'percentage' => round(($approvalStats->suspended / $total) * 100),
                'count' => $approvalStats->suspended,
            ],
            'blacklisted' => [
                'percentage' => round(($approvalStats->blacklisted / $total) * 100),
                'count' => $approvalStats->blacklisted,
            ],
            'total_applications' => $approvalStats->total_applications,
        ];
    }

    protected function getExceptionRate()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $totalApplications = $query->count();

        $exceptionQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->where(function ($query) {
                $query->whereIn('status', ['suspended', 'blacklisted'])
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->whereHas('schedulePayments', function ($paymentQuery) {
                            $paymentQuery->where('late_days', '>', 30)
                                ->whereIn('payment_status', ['due', 'late']);
                        });
                    });
            });

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $exceptionQuery->where('id', $this->userId);
        }

        $exceptionApplications = $exceptionQuery->count();

        $currentRate = $totalApplications > 0 ? round(($exceptionApplications / $totalApplications) * 100, 1) : 0;

        // Previous period calculation
        $previousQuery = Merchant::whereBetween('created_at', [$this->dateRange['previous_from'], $this->dateRange['previous_to']]);

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $previousQuery->where('id', $this->userId);
        }

        $previousTotal = $previousQuery->count();

        $previousExceptionsQuery = Merchant::whereBetween('created_at', [$this->dateRange['previous_from'], $this->dateRange['previous_to']])
            ->where(function ($query) {
                $query->whereIn('status', ['suspended', 'blacklisted'])
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->whereHas('schedulePayments', function ($paymentQuery) {
                            $paymentQuery->where('late_days', '>', 30)
                                ->whereIn('payment_status', ['due', 'late']);
                        });
                    });
            });

        // Filter by user if specified
        if ($this->userId && $this->userType === 'merchant') {
            $previousExceptionsQuery->where('id', $this->userId);
        }

        $previousExceptions = $previousExceptionsQuery->count();

        $previousRate = $previousTotal > 0 ? round(($previousExceptions / $previousTotal) * 100, 1) : 0;
        $change = round($currentRate - $previousRate, 1);

        return [
            'current' => $currentRate.'%',
            'change' => $this->formatChange($change),
            'trend' => $change < 0 ? 'down' : 'up',
            'breakdown' => $this->getExceptionBreakdown(),
            'count' => $exceptionApplications,
            'total_count' => $totalApplications,
        ];
    }

    protected function getPaymentBehavior()
    {
        $query = SchedulePayment::whereBetween('due_date', [$this->dateRange['from']->subDays(60), $this->dateRange['to']]);

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $delinquencyTrend = $query->select(
            DB::raw('WEEK(due_date) as week'),
            DB::raw('SUM(CASE WHEN late_days > 0 THEN 1 ELSE 0 END) as late_count'),
            DB::raw('COUNT(*) as total_count')
        )
            ->groupBy('week')
            ->orderBy('week')
            ->get()
            ->map(function ($item) {
                return $item->total_count > 0 ? round(($item->late_count / $item->total_count) * 100, 1) : 0;
            })
            ->take(7)
            ->toArray();

        $avgQuery = SchedulePayment::whereBetween('due_date', [$this->dateRange['from'], $this->dateRange['to']])
            ->where('late_days', '>', 0);

        if ($this->userId) {
            $avgQuery->where('user_id', $this->userId);
        }

        $avgDaysLate = $avgQuery->avg('late_days') ?? 0;

        $repeatQuery = SchedulePayment::whereBetween('due_date', [$this->dateRange['from'], $this->dateRange['to']])
            ->where('late_days', '>', 0);

        if ($this->userId) {
            $repeatQuery->where('user_id', $this->userId);
        }

        $repeatLatePayers = $repeatQuery->distinct('user_id')
            ->count('user_id');

        return [
            'delinquency_trend' => count($delinquencyTrend) > 0 ? $delinquencyTrend : [12, 15, 18, 14, 16, 20, 22],
            'avg_days_late' => round($avgDaysLate, 1),
            'repeat_late_payers' => $repeatLatePayers,
        ];
    }

    protected function getDeviceAnomalies()
    {
        // Simulated data - replace with actual device anomaly detection
        $totalAnomalies = rand(40, 60);
        $previousTotal = rand(35, 45);
        $change = $previousTotal > 0 ? round((($totalAnomalies - $previousTotal) / $previousTotal) * 100) : 0;

        return [
            'total' => $totalAnomalies,
            'change' => $this->formatChange($change),
            'breakdown' => [
                'Device Change' => 25,
                'Location Mismatch' => 12,
                'IP Suspicious' => 6,
                'Other' => 4,
            ],
        ];
    }

    protected function getAlertDistribution()
    {
        $today = Carbon::today();
        $thirtyDaysAgo = $today->copy()->subDays(30);
        $sevenDaysAgo = $today->copy()->subDays(7);

        // 1. Geo Velocity Alerts
        $geoVelocityQuery = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$sevenDaysAgo, $today]);

        if ($this->userId) {
            $geoVelocityQuery->where('orders.user_id', $this->userId);
        }

        $geoVelocityAlerts = $geoVelocityQuery->select(
            'users.id',
            'users.business_name',
            DB::raw('COUNT(DISTINCT orders.shipping_city) as distinct_cities'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw('MAX(orders.created_at) as latest_order'),
            DB::raw('MIN(orders.created_at) as earliest_order')
        )
            ->whereNotNull('orders.shipping_city')
            ->groupBy('users.id', 'users.business_name')
            ->having('distinct_cities', '>', 1)
            ->having('order_count', '>=', 2)
            ->get()
            ->filter(function ($user) {
                $timeSpan = Carbon::parse($user->earliest_order)
                    ->diffInHours(Carbon::parse($user->latest_order));

                $citiesPerDay = $user->distinct_cities / max(1, $timeSpan / 24);

                return $citiesPerDay > 0.5;
            })
            ->count();

        // 2. Transaction Pattern Alerts
        $transactionQuery = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$thirtyDaysAgo, $today]);

        if ($this->userId) {
            $transactionQuery->where('orders.user_id', $this->userId);
        }

        $transactionPatternAlerts = $transactionQuery->select(
            'users.id',
            DB::raw('AVG(orders.grand_total) as avg_order_value'),
            DB::raw('STDDEV(orders.grand_total) as std_order_value'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('MAX(orders.grand_total) as max_order_value')
        )
            ->groupBy('users.id')
            ->having('total_orders', '>=', 3)
            ->get()
            ->filter(function ($user) use ($sevenDaysAgo) {

                // ensure numeric types
                $avg = (float) ($user->avg_order_value ?? 0);
                $std = (float) ($user->std_order_value ?? 0);

                if ($std === 0) {
                    return false;
                } // no variation → no anomaly check

                $recentOrdersQuery = Order::where('user_id', $user->id)
                    ->where('created_at', '>=', $sevenDaysAgo);

                if ($this->userId) {
                    $recentOrdersQuery->where('user_id', $this->userId);
                }

                $recentOrders = $recentOrdersQuery->pluck('grand_total')
                    ->map(fn ($v) => (float) $v);

                if ($recentOrders->count() === 0) {
                    return false;
                }

                $recentAvg = (float) $recentOrders->avg();
                $maxRecent = (float) $recentOrders->max();

                $threshold = $avg + (2.5 * $std);

                $anomalies = 0;
                if ($maxRecent > $threshold) {
                    $anomalies++;
                }
                if ($recentAvg > ($avg * 1.5)) {
                    $anomalies++;
                }
                if ($recentOrders->count() > ($user->total_orders / 4)) {
                    $anomalies++;
                }

                return $anomalies >= 2;
            })
            ->count();

        // 3. Behavioral Alerts
        $behavioralQuery = DB::table('schedule_payments')
            ->join('users', 'schedule_payments.user_id', '=', 'users.id')
            ->whereBetween('schedule_payments.due_date', [$thirtyDaysAgo, $today]);

        if ($this->userId) {
            $behavioralQuery->where('schedule_payments.user_id', $this->userId);
        }

        $behavioralAlerts = $behavioralQuery->select(
            'users.id',
            DB::raw('SUM(CASE WHEN payment_status = "paid" AND late_days = 0 THEN 1 ELSE 0 END) as on_time_payments'),
            DB::raw('SUM(CASE WHEN payment_status = "paid" AND late_days > 0 THEN 1 ELSE 0 END) as late_payments'),
            DB::raw('SUM(CASE WHEN payment_status IN ("due", "late") THEN 1 ELSE 0 END) as outstanding_payments'),
            DB::raw('COUNT(*) as total_payments'),
            DB::raw('AVG(late_days) as avg_days_late')
        )
            ->groupBy('users.id')
            ->having('total_payments', '>=', 3)
            ->get()
            ->filter(function ($user) {

                $currentOnTimeRate = $user->total_payments > 0
                    ? ($user->on_time_payments / $user->total_payments) * 100
                    : 100;

                $historicalPaymentsQuery = SchedulePayment::where('user_id', $user->id)
                    ->where('due_date', '<', Carbon::now()->subDays(30));

                if ($this->userId) {
                    $historicalPaymentsQuery->where('user_id', $this->userId);
                }

                $historicalPayments = $historicalPaymentsQuery->select(
                    DB::raw('SUM(CASE WHEN payment_status = "paid" AND late_days = 0 THEN 1 ELSE 0 END) as historical_on_time'),
                    DB::raw('COUNT(*) as historical_total')
                )
                    ->first();

                $historicalOnTimeRate = $historicalPayments->historical_total > 0
                    ? ($historicalPayments->historical_on_time / $historicalPayments->historical_total) * 100
                    : 100;

                return $historicalOnTimeRate - $currentOnTimeRate > 20 ||
                    $currentOnTimeRate < 60 ||
                    $user->avg_days_late > 15;
            })
            ->count();

        // 4. High Risk Alerts
        $highRiskQuery = Merchant::where('status', 'active')
            ->join('users', 'merchants.id', '=', 'users.id')
            ->with(['businessType', 'schedulePayments' => function ($q) use ($thirtyDaysAgo) {
                $q->where('due_date', '>=', $thirtyDaysAgo);
            }])
            ->select('merchants.*', 'users.business_name', 'users.email');

        if ($this->userId && $this->userType === 'merchant') {
            $highRiskQuery->where('merchants.id', $this->userId);
        }

        $highRiskAlerts = $highRiskQuery->get()
            ->filter(function ($merchant) {

                $riskFactors = [];
                $totalScore = 0;

                // 1. Utilization
                $utilization = (float) ($merchant->credit_limit_utilization ?? 0);
                if ($utilization > 90) {
                    $riskFactors[] = 'Very High Utilization';
                    $totalScore += 30;
                } elseif ($utilization > 75) {
                    $riskFactors[] = 'High Utilization';
                    $totalScore += 20;
                } elseif ($utilization > 50) {
                    $totalScore += 10;
                }

                // 2. Payment History
                $latePayments = $merchant->schedulePayments->where('late_days', '>', 0)->count();
                $totalPayments = $merchant->schedulePayments->count();

                if ($totalPayments > 0) {
                    $lateRate = ($latePayments / $totalPayments) * 100;
                    if ($lateRate > 50) {
                        $riskFactors[] = 'Poor Payment History';
                        $totalScore += 25;
                    } elseif ($lateRate > 25) {
                        $riskFactors[] = 'Concerning Payment Pattern';
                        $totalScore += 15;
                    }
                }

                // 3. Business Activity
                $recentOrdersQuery = Order::where('user_id', $merchant->id)
                    ->where('created_at', '>=', Carbon::now()->subDays(30));

                if ($this->userId) {
                    $recentOrdersQuery->where('user_id', $this->userId);
                }

                $recentOrders = $recentOrdersQuery->count();

                if ($recentOrders === 0) {
                    $riskFactors[] = 'Account Inactivity';
                    $totalScore += 20;
                } elseif ($recentOrders > 50) {
                    $riskFactors[] = 'Unusually High Activity';
                    $totalScore += 15;
                }

                // 4. Business Type
                $businessRisk = strtolower($merchant->businessType->risk_level ?? 'medium');
                if ($businessRisk === 'very high') {
                    $totalScore += 15;
                } elseif ($businessRisk === 'high') {
                    $totalScore += 10;
                }

                // 5. Order Value Concentration
                $orderStatsQuery = Order::where('user_id', $merchant->id)
                    ->where('created_at', '>=', Carbon::now()->subDays(30));

                if ($this->userId) {
                    $orderStatsQuery->where('user_id', $this->userId);
                }

                $orderStats = $orderStatsQuery->select(
                    DB::raw('AVG(grand_total) as avg_value'),
                    DB::raw('STDDEV(grand_total) as std_value')
                )
                    ->first();

                $avgValue = (float) ($orderStats->avg_value ?? 0);
                $stdValue = (float) ($orderStats->std_value ?? 0);

                if ($stdValue > 0 && $stdValue < ($avgValue * 0.1)) {
                    $riskFactors[] = 'Concentrated Order Values';
                    $totalScore += 10;
                }

                return $totalScore >= 40 && count($riskFactors) >= 2;
            })
            ->count();

        return [
            'geo_velocity' => $geoVelocityAlerts,
            'transaction_pattern' => $transactionPatternAlerts,
            'behavioral' => $behavioralAlerts,
            'high_risk' => $highRiskAlerts,
        ];
    }

    protected function getConcentrationRisks()
    {
        // Get top 5 suppliers by total order value
        $supplierQuery = Order::join('users', 'orders.seller_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$this->dateRange['from'], $this->dateRange['to']]);

        if ($this->userId) {
            $supplierQuery->where('orders.seller_id', $this->userId);
        }

        $supplierConcentration = $supplierQuery->select(
            'users.business_name',
            DB::raw('SUM(orders.grand_total) as total_value')
        )
            ->groupBy('users.business_name')
            ->orderByDesc('total_value')
            ->get();

        $topSuppliers = [];
        $otherTotal = 0;

        // Total sum of all suppliers
        $grandTotal = $supplierConcentration->sum('total_value');

        foreach ($supplierConcentration as $index => $supplier) {
            $name = trim($supplier->business_name) ?: 'Unknown Supplier';
            $percentage = $grandTotal > 0 ? round(($supplier->total_value / $grandTotal) * 100) : 0;

            if ($index < 5) {
                // Top 5 suppliers
                $topSuppliers[$name] = $percentage;
            } else {
                // All others
                $otherTotal += $percentage;
            }
        }

        if ($otherTotal > 0) {
            $topSuppliers['Others'] = $otherTotal;
        }

        return [
            'top_suppliers' => $topSuppliers,
            'merchant_risk' => [
                'high' => 8,
                'medium' => 15,
                'low' => 27,
            ],
        ];
    }

    // ==================== RISK SCORE CALCULATION METHODS ====================

    protected function calculatePortfolioRisk()
    {
        $nplRatio = $this->calculateNplRatio()['current'];
        $utilization = $this->calculateUtilization()['rate'];

        $riskScore = min(100, ($nplRatio * 2) + ($utilization * 0.8));

        return min(100, max(0, $riskScore));
    }

    protected function calculatePipelineRisk(): float
    {
        $exceptionRateData = $this->getExceptionRate();

        // If getExceptionRate() returns ['percentage' => float, 'count' => int], extract percentage
        $exceptionRate = (float) ($exceptionRateData['percentage'] ?? 0);

        $approvalRates = $this->getApprovalRates();
        $approvedRate = (float) ($approvalRates['approved'] ?? 0);

        $riskScore = min(100, $exceptionRate * 1.5 + (100 - $approvedRate));

        return min(100, max(0, $riskScore));
    }

    protected function calculateEwsRisk()
    {
        $paymentBehavior = $this->getPaymentBehavior();
        $delinquencyTrend = array_sum($paymentBehavior['delinquency_trend']) / count($paymentBehavior['delinquency_trend']);

        $riskScore = min(100, $delinquencyTrend * 2 + $paymentBehavior['avg_days_late'] * 5);

        return min(100, max(0, $riskScore));
    }

    protected function getRiskLevel($score)
    {
        return match (true) {
            $score >= 80 => 'Critical',
            $score >= 60 => 'High',
            $score >= 40 => 'Medium',
            $score >= 20 => 'Low',
            default => 'Very Low'
        };
    }

    // ==================== HELPER METHODS ====================

    protected function getDateRange($filters)
    {
        $fromDate = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : Carbon::now()->subDays(30);
        $toDate = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::now();

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'previous_from' => $fromDate->copy()->subDays($fromDate->diffInDays($toDate)),
            'previous_to' => $fromDate->copy()->subDay(),
        ];
    }

    protected function formatChange($change)
    {
        return $change > 0 ? '+'.$change.'%' : $change.'%';
    }

    protected function formatHours($hours)
    {
        return round($hours, 1).'h';
    }

    protected function getExceptionBreakdown()
    {
        $suspendedQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->where('status', 'suspended');

        if ($this->userId && $this->userType === 'merchant') {
            $suspendedQuery->where('id', $this->userId);
        }

        $suspendedCount = $suspendedQuery->count();

        $blacklistedQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->where('status', 'blacklisted');

        if ($this->userId && $this->userType === 'merchant') {
            $blacklistedQuery->where('id', $this->userId);
        }

        $blacklistedCount = $blacklistedQuery->count();

        $delinquentQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereHas('user', function ($query) {
                $query->whereHas('schedulePayments', function ($paymentQuery) {
                    $paymentQuery->where('late_days', '>', 30)
                        ->whereIn('payment_status', ['due', 'late']);
                });
            });

        if ($this->userId && $this->userType === 'merchant') {
            $delinquentQuery->where('id', $this->userId);
        }

        $delinquentCount = $delinquentQuery->count();

        $documentQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->where(function ($query) {
                $query->whereNull('goverment_data')
                    ->orWhere('cr_number', '')
                    ->orWhere('vat_register_file', '')
                    ->orWhere('balady_certificate', '')
                    ->orWhere('owner_iqama_number', '');
            });

        if ($this->userId && $this->userType === 'merchant') {
            $documentQuery->where('id', $this->userId);
        }

        $documentIssueCount = $documentQuery->count();

        $totalExceptions = $suspendedCount + $blacklistedCount + $delinquentCount + $documentIssueCount;

        if ($totalExceptions > 0) {
            return [
                'Suspended Accounts' => round(($suspendedCount / $totalExceptions) * 100),
                'Blacklisted Accounts' => round(($blacklistedCount / $totalExceptions) * 100),
                'Payment Delinquency' => round(($delinquentCount / $totalExceptions) * 100),
                'Document Issues' => round(($documentIssueCount / $totalExceptions) * 100),
            ];
        }

        return [
            'Suspended Accounts' => 0,
            'Blacklisted Accounts' => 0,
            'Payment Delinquency' => 0,
            'Document Issues' => 0,
        ];
    }

    protected function calculateUnderwritingCompliance()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted']);

        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $totalProcessed = $query->count();

        $compliantQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted'])
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) * 0.6 <= 6');

        if ($this->userId && $this->userType === 'merchant') {
            $compliantQuery->where('id', $this->userId);
        }

        $compliantUnderwriting = $compliantQuery->count();

        return $totalProcessed > 0 ? round(($compliantUnderwriting / $totalProcessed) * 100) : 100;
    }

    protected function calculateApprovalCompliance()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted']);

        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $totalProcessed = $query->count();

        $compliantQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted'])
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) * 0.3 <= 4');

        if ($this->userId && $this->userType === 'merchant') {
            $compliantQuery->where('id', $this->userId);
        }

        $compliantApproval = $compliantQuery->count();

        return $totalProcessed > 0 ? round(($compliantApproval / $totalProcessed) * 100) : 100;
    }

    protected function calculateDisbursementCompliance()
    {
        $query = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted']);

        if ($this->userId && $this->userType === 'merchant') {
            $query->where('id', $this->userId);
        }

        $totalProcessed = $query->count();

        $compliantQuery = Merchant::whereBetween('created_at', [$this->dateRange['from'], $this->dateRange['to']])
            ->whereIn('status', ['approved', 'active', 'suspended', 'blacklisted'])
            ->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) * 0.1 <= 8');

        if ($this->userId && $this->userType === 'merchant') {
            $compliantQuery->where('id', $this->userId);
        }

        $compliantDisbursement = $compliantQuery->count();

        return $totalProcessed > 0 ? round(($compliantDisbursement / $totalProcessed) * 100) : 100;
    }

    // ==================== ALERTS METHODS ====================
    public function getAllAlerts($filters = [])
    {
        $this->dateRange = $this->getDateRange($filters);

        $alerts = $this->buildRiskAlerts();
        $flags = $this->buildRiskFlags();

        // Combine and format all alerts with full details
        $allAlerts = collect([]);

        // Process regular alerts
        foreach ($alerts as $alert) {
            $allAlerts->push($this->formatAlertForDetailPage($alert, 'alert'));
        }

        // Process risk flags
        foreach ($flags as $flag) {
            $allAlerts->push($this->formatAlertForDetailPage($flag, 'flag'));
        }

        // Sort by severity and time
        return $allAlerts->sortByDesc(function ($alert) {
            $severityWeight = match ($alert['severity_level']) {
                'critical' => 100,
                'high' => 80,
                'medium' => 60,
                'low' => 40,
                'info' => 20,
                default => 0
            };

            return $severityWeight;
        })->values()->all();
    }

    protected function formatAlertForDetailPage($alert, $type = 'alert')
    {
        $baseAlert = [
            'id' => uniqid(),
            'type' => $type,
            'severity' => $alert['type'] ?? 'info',
            'title' => $alert['title'] ?? '',
            'description' => $alert['description'] ?? '',
            'icon' => $alert['icon'] ?? 'ki-filled ki-information-2',
            'created_at' => $alert['time'] ?? now()->format('M d, Y H:i'),
            'additional_info' => $alert['additional_info'] ?? [],
        ];

        // Add severity-specific styling
        $baseAlert['severity_level'] = $this->determineSeverityLevel($baseAlert['severity']);
        $baseAlert['severity_color'] = $this->getSeverityColor($baseAlert['severity_level']);
        $baseAlert['severity_badge'] = $this->getSeverityBadge($baseAlert['severity_level']);

        return $baseAlert;
    }

    protected function determineSeverityLevel($severity)
    {
        return match ($severity) {
            'critical', 'highest_risk' => 'critical',
            'high', 'high_risk' => 'high',
            'medium', 'concentration_risk' => 'medium',
            'low' => 'low',
            default => 'info'
        };
    }

    protected function getSeverityColor($severityLevel)
    {
        return match ($severityLevel) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray'
        };
    }

    protected function getSeverityBadge($severityLevel)
    {
        return match ($severityLevel) {
            'critical' => 'Critical',
            'high' => 'High Priority',
            'medium' => 'Medium Priority',
            'low' => 'Low Priority',
            default => 'Information'
        };
    }

    // ==================== USER SPECIFIC METHODS ====================

    /**
     * Get user-specific dashboard data
     */
    public function getUserDashboardData($userId, $filters = [])
    {
        $filters['user_id'] = $userId;
        $this->userId = $filters['user_id'] ?? null;

        // If user_id provided, get user type
        if ($this->userId) {
            $user = User::find($this->userId);
            $this->userType = $user->user_type ?? null;
        }

        $this->dateRange = $this->getDateRange($filters);

        return [
            'risk_scores' => $this->getRiskScores(),
            'alerts' => $this->buildRiskAlerts(),
            'flags' => $this->buildRiskFlags(),
            'user_specific' => ! is_null($this->userId),
            'user_type' => $this->userType,
        ];
    }

    /**
     * Check if current dashboard is user-specific
     */
    public function isUserSpecific()
    {
        return ! is_null($this->userId);
    }

    /**
     * Get current user ID if set
     */
    public function getCurrentUserId()
    {
        return $this->userId;
    }

    /**
     * Get current user type if set
     */
    public function getCurrentUserType()
    {
        return $this->userType;
    }
}
