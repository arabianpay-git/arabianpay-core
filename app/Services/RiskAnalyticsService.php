<?php

namespace App\Services;

use App\Models\User;
use App\Models\RiskScore;
use App\Models\BusinessType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

class RiskAnalyticsService
{
    public function calculateForUser(User $user): object
    {
        // Fetch CR & ID data based on user type
        if ($user->user_type === 'merchant') {
            $govData = $user->merchant;
            $crData = $govData->goverment_data ?? null;
            $crNumber = $govData->cr_number ?? null;
            $idNumber = $govData->owner_iqama_number ?? null;
        } else {
            $govData = $user->customer;
            $crData = $govData->cr_data ?? null;
            $crNumber = $govData->cr_number ?? null;
            $idNumber = $govData->id_number ?? null;
        }

        // Decode CR data
        $decodedCrData = $this->parseCrData($crData);

        // Calculate scores
        $idMatchScore = $this->calculateIdMatchScore($idNumber, $decodedCrData);
        $idExpiryScore = $this->calculateIdExpiryScore($user);
        $crExpiryScore = $this->calculateCrExpiryScore($decodedCrData);
        $industryScore = $this->calculateIndustryScore($user);
        $activityScore = $this->calculateActivityScore($decodedCrData);

        // Calculate composite scores
        $crIdRaw = $idMatchScore + $idExpiryScore + $crExpiryScore + $industryScore['industry_score'] + $activityScore;
        $crIdScore = min($crIdRaw / 100, 1) * 25;
        $posScore = min(30000 / 50000, 1) * 25;

        // Repayment and industry scores
        $repaymentData = $this->calculateRepaymentScore($user);
        $locationData = $this->calculateLocationScore($decodedCrData);

        // Manual risk adjustments
        $manualRisk = $this->getManualRisk($user->id);

        // Final score calculation
        $totalScore = $crIdScore + $posScore + $repaymentData['score'] +
            $repaymentData['industry_score'] + $locationData['score'] +
            $manualRisk['score'];

        return (object) array_merge([
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'business_name' => $user->business_name,
            'cr_number' => $crNumber,
            'id_number' => $idNumber,
            'cr_id_match_score' => $idMatchScore,
            'id_expiry_score' => $idExpiryScore,
            'cr_expiry_score' => $crExpiryScore,
            'business_type_score' => $industryScore['industry_score'],
            'activity_score' => $activityScore,
            'cr_id_total' => $crIdRaw,
            'cr_id_score' => round($crIdScore, 2),
            'pos_revenue' => 30000, // Placeholder
            'pos_score' => round($posScore, 2),
            'late_payments' => $repaymentData['delays'],
            'repayment_score' => $repaymentData['score'],
            'industry' => $repaymentData['industry'],
            'industry_score' => $repaymentData['industry_score'],
            'location' => $locationData['details'],
            'location_score' => $locationData['score'],
            'flagged' => $manualRisk['flagged'],
            'risk_score' => $manualRisk['score'],
            'reason' => $manualRisk['reason'],
            'total_score' => round($totalScore, 2),
        ], $repaymentData, $locationData);
    }

    public function calculateForUsers(Collection $users): Collection
    {
        return $users->map(fn($user) => $this->calculateForUser($user));
    }

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

        $diff = now()->diffInMonths($expiryDate, false);
        return match (true) {
            $diff >= 0 => 20,
            $diff >= -3 => 10,
            default => 0
        };
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

        $industryData = $this->calculateIndustryScore($user);

        return [
            'delays' => $delays,
            'score' => $score,
            'industry' => $industryData['industry'],
            'industry_score' => $industryData['industry_score'],
        ];
    }

    private function calculateIndustryScore(User $user): array
    {
        $isMerchant = $user->user_type === 'merchant';
        $relation = $isMerchant ? 'merchant' : 'customer';

        $industryRisk = strtolower($user->$relation->businessType->risk_level ?? 'medium');

        $riskMap = [
            'low' => 15,
            'medium-low' => 12,
            'medium' => 10,
            'high' => 8,
            'very high' => 5,
        ];

        $industryScore = $riskMap[$industryRisk] ?? 10;
        $industry = $user->$relation->businessType->name ?? '-';

        return [
            'industry' => $industry,
            'industry_score' => $industryScore,
        ];
    }


    private function calculateLocationScore(array $crData): array
    {
        $city = strtolower($crData['headquarterCityName'] ?? '');

        // City tier score
        $cityTiers = [
            'riyadh' => 6,
            'jeddah' => 6,
            'dammam' => 6,
            'makkah' => 4,
            'madinah' => 4,
            'khobar' => 4
        ];
        $tierScore = $cityTiers[$city] ?? 2;

        // Economic activity score
        $businessCount = Cache::get("business_density_{$city}", 0);
        $activityScore = match (true) {
            $businessCount >= 500 => 4.5,
            $businessCount >= 100 => 3,
            default => 1
        };

        // Default rate score
        $defaultRate = Cache::get("default_rate_{$city}", 0);
        $defaultScore = match (true) {
            $defaultRate <= 5 => 4.5,
            $defaultRate <= 10 => 2,
            default => 0
        };

        $totalScore = round($tierScore + $activityScore + $defaultScore, 2);

        return [
            'score' => $totalScore,
            'details' => [
                'city' => ucwords($city),
                'tier_score' => $tierScore,
                'activity_score' => $activityScore,
                'default_rate_score' => $defaultScore,
            ]
        ];
    }

    private function getManualRisk(int $userId): array
    {
        $riskScore = RiskScore::firstWhere('user_id', $userId);

        if ($riskScore && $riskScore->risk_score !== null) {
            return [
                'score' => $riskScore->risk_score,
                'flagged' => true,
                'reason' => $riskScore->reason
            ];
        }

        return [
            'score' => 0,
            'flagged' => false,
            'reason' => null
        ];
    }
}
