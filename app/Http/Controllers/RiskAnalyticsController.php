<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\RiskScore;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiskAnalyticsController extends Controller
{
    public function score(Request $request)
    {
        $search = $request->input('search');
        $order = $request->input('order', 'desc');

        $usersQuery = User::whereIn('user_type', ['merchant', 'user'])
            ->with(['merchant.businessType', 'customer.businessType', 'transactions']);

        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('iqama', 'like', "%{$search}%")
                    ->orWhereHas('merchant', function ($q2) use ($search) {
                        $q2->where('business_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q3) use ($search) {
                        $q3->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        $users = $usersQuery->paginate(10);
        $risks = $users->getCollection()->map(function ($user) {
            return $this->calculateRiskForUser($user);
        });

        $paginatedRisks = new LengthAwarePaginator(
            $risks,
            $users->total(),
            $users->perPage(),
            $users->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        if ($order === 'asc') {
            $risks = $risks->sortBy('total_score')->values();
        } else {
            $risks = $risks->sortByDesc('total_score')->values();
        }

        return view('admin.risk-management.score', ['risks' => $paginatedRisks]);
    }

    private function calculateRiskForUser(User $user)
    {
        $userId = $user->id;
        $idNumber = null;

        // Fetch CR & ID data based on user type
        if ($user->user_type === 'merchant') {
            $govData = Merchant::where('user_id', $userId)
                ->select('goverment_data', 'cr_number', 'pos_revenue', 'owner_iqama_number')
                ->first();

            $crData = $govData->goverment_data ?? null;
            $crNumber = $govData->cr_number ?? null;
            $idNumber = $govData->owner_iqama_number ?? null;
        } else {
            $govData = Customer::where('user_id', $userId)
                ->select('cr_data', 'cr_number', 'id_number')
                ->first();

            $crData = $govData->cr_data ?? null;
            $crNumber = $govData->cr_number ?? null;
            $idNumber = $govData->id_number ?? null;
        }

        // Decode CR data JSON if needed
        if (is_string($crData)) {
            $decodedCrData = json_decode($crData, true);
        } elseif (is_array($crData)) {
            $decodedCrData = $crData;
        } else {
            $decodedCrData = null;
        }

        // 1. ID Match score
        $idMatchScore = 0;
        $ownerId = $decodedCrData['parties'][0]['identity']['id'] ?? null;

        if ($idNumber && $ownerId) {
            if ($idNumber === $ownerId) {
                $idMatchScore = 30;
            } elseif (strpos($ownerId, $idNumber) !== false || strpos($idNumber, $ownerId) !== false) {
                $idMatchScore = 15;
            }
        }

        // 2. ID expiry score
        $idExpiryScore = 0;
        if ($user->iqama_expiry) {
            $diff = now()->diffInMonths($user->iqama_expiry, false);
            if ($diff >= 0) {
                $idExpiryScore = 20;
            } elseif ($diff >= -3) {
                $idExpiryScore = 10;
            }
        }

        // 3. CR expiry score
        $crExpiryScore = 0;
        if (!empty($decodedCrData['status']['confirmationDate']['gregorian'])) {
            $expiry = now()->parse($decodedCrData['status']['confirmationDate']['gregorian']);
            $diff = now()->diffInMonths($expiry, false);
            if ($diff >= 0) {
                $crExpiryScore = 20;
            } elseif ($diff >= -3) {
                $crExpiryScore = 10;
            }
        }


        // 5. Business Activity alignment score
        $activityNames = collect($decodedCrData['activities'] ?? [])->pluck('name')->toArray();
        $activityScore = count($activityNames) > 0 ? 15 : 0;


        // POS Revenue Score (dummy example - replace with real data)
        $monthlyPos = $govData->pos_revenue ?? 0;
        $posScore = min($monthlyPos / 50000, 1) * 25;

        // Repayment Delay Score and Industry Risk Score
        if ($user->user_type === 'merchant') {
            $repaymentDelays = $user->transactions()
                ->where('seller_id', $user->id)
                ->where('payment_status', 'late')
                ->count();

            $repaymentScore = match (true) {
                $repaymentDelays === 0 => 20,
                $repaymentDelays <= 2 => 15,
                default => 5,
            };

            $industryRisk = strtolower(optional(optional($user->merchant)->businessType)->risk_level ?? 'medium');
            $map = ['low' => 15, 'medium-low' => 12, 'medium' => 10, 'high' => 8, 'very high' => 5];
            $industryScore = $map[$industryRisk] ?? 10;
            $industry = optional(optional($user->merchant)->businessType)->name ?? '-';
        } else {
            $repaymentDelays = $user->transactions()
                ->where('user_id', $user->id)
                ->where('payment_status', 'late')
                ->count();

            $repaymentScore = match (true) {
                $repaymentDelays === 0 => 20,
                $repaymentDelays <= 2 => 15,
                default => 5,
            };

            $industryRisk = strtolower(optional(optional($user->customer)->businessType)->risk_level ?? 'medium');
            $map = ['low' => 15, 'medium-low' => 12, 'medium' => 10, 'high' => 8, 'very high' => 5];
            $industryScore = $map[$industryRisk] ?? 10;
            $industry = optional(optional($user->customer)->businessType)->name ?? '-';
        }

        // Normalize CR & ID total score (max 100) to 25%
        $crIdRaw = $idMatchScore + $idExpiryScore + $crExpiryScore + $industryScore + $activityScore;
        $crIdScore = min($crIdRaw / 100, 1) * 25;

        // Location Risk Score (max 10)
        $city = strtolower($decodedCrData['headquarterCityName'] ?? '');

        $cityTierMap = [
            'riyadh' => 6,
            'jeddah' => 6,
            'dammam' => 6,
            'makkah' => 4,
            'madinah' => 4,
            'khobar' => 4,
        ];
        $cityTierScore = $cityTierMap[$city] ?? 2;

        $businessCount = cache("business_density_{$city}") ?? 0;
        if ($businessCount >= 500) {
            $economicActivityScore = 4.5;
        } elseif ($businessCount >= 100) {
            $economicActivityScore = 3;
        } else {
            $economicActivityScore = 1;
        }

        $defaultRate = cache("default_rate_{$city}") ?? 0;
        if ($defaultRate <= 5) {
            $defaultRateScore = 4.5;
        } elseif ($defaultRate <= 10) {
            $defaultRateScore = 2;
        } else {
            $defaultRateScore = 0;
        }

        $locationScore = round(($cityTierScore + $economicActivityScore + $defaultRateScore) * (10 / 15), 2);

        $location = [
            'city' => ucwords($city),
            'tier_score' => $cityTierScore,
            'activity_score' => $economicActivityScore,
            'default_rate_score' => $defaultRateScore,
        ];

        // Get Google reviews with (max weight 5)
        // $businessName = $decodedCrData['name'] ?? null;
        $businessName = 'arabianpay';

        $cacheKey = 'google_rating_' . md5(strtolower(trim($businessName)));
        $cachedRating = cache($cacheKey);

        if (!$cachedRating && $businessName) {
            $liveRating = app(self::class)->fetchAndCacheGoogleRating($businessName);
            $googleRating = ['result' => ['rating' => $liveRating]];
        } else {
            $googleRating = $cachedRating ?? ['result' => ['rating' => null]];
        }

        // Initialize flagged and manual_reason
        $flagged = false;
        $manual_reason = null;

        // Check if manual score exists in RiskScore
        $riskScoreRecord = RiskScore::where('user_id', $userId)->first();
        $riskScore = 0;
        if ($riskScoreRecord && $riskScoreRecord->risk_score !== null) {
            $riskScore = $riskScoreRecord->risk_score;
            $flagged = true;
            $manual_reason = $riskScoreRecord->reason;
        }

        // Final total risk score (including manual score)
        $totalScore = round($crIdScore, 2)
            + round($posScore, 2)
            + round($repaymentScore, 2)
            + round($industryScore, 2)
            + round($locationScore, 2)
            + round($riskScore, 2)
            + round($googleRating['result']['rating'] ?? null, 2);

        return (object)[
            'id' => $userId,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'business_name' => $user->business_name,
            'cr_number' => $crNumber,
            'id_number' => $idNumber,
            'cr_id_match_score' => $idMatchScore,
            'id_expiry_score' => $idExpiryScore,
            'cr_expiry_score' => $crExpiryScore,
            'business_type_score' => $industryScore,
            'activity_score' => $activityScore,
            'cr_id_total' => $crIdRaw,
            'cr_id_score' => round($crIdScore, 2),
            'pos_revenue' => $monthlyPos,
            'pos_score' => round($posScore, 2),
            'late_payments' => $repaymentDelays ?? 0,
            'repayment_score' => $repaymentScore,
            'industry' => $industry,
            'industry_score' => $industryScore,
            'location' => $location,
            'location_score' => $locationScore,
            'flagged' => $flagged ?? false,
            'risk_score' => $riskScore ?? 0,
            'reason' => $manual_reason,
            'google_rating' => $googleRating,
            'total_score' => $totalScore,
        ];
    }

    private function fetchAndCacheGoogleRating(string $businessName): ?float
    {
        try {
            $searchResponse = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
                'input' => $businessName,
                'inputtype' => 'textquery',
                'fields' => 'place_id',
                'key' => env('GOOGLE_PLACE_API_KEY'),
            ]);

            if (!$searchResponse->successful()) {
                Log::error("Google FindPlace API failed: " . $searchResponse->body());
                return null;
            }

            $placeId = $searchResponse['candidates'][0]['place_id'] ?? null;

            if (!$placeId) {
                Log::warning("No place_id found for: {$businessName}");
                return null;
            }

            $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'rating',
                'key' => env('GOOGLE_PLACE_API_KEY'),
            ]);

            if (!$detailsResponse->successful()) {
                Log::error("Google Place Details API failed for place_id: {$placeId}. Response: " . $detailsResponse->body());
                return null;
            }

            $rating = $detailsResponse['result']['rating'] ?? null;

            if ($rating !== null) {
                $cacheKey = 'google_rating_' . md5(strtolower(trim($businessName)));
                cache()->put($cacheKey, ['result' => ['rating' => $rating]], now()->addDays(7));
                return $rating;
            } else {
                Log::info("No rating found in Place Details for: {$businessName}");
                return null;
            }
        } catch (\Throwable $e) {
            Log::error('Google rating fetch exception: ' . $e->getMessage());
            return null;
        }
    }


    public function exportCsv(Request $request)
    {
        $users = $this->getUsersWithFilters($request);

        $risks = $users->map(function ($user) {
            return $this->calculateRiskForUser($user);
        });

        $filename = 'risk_scores_' . date('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($risks) {
            $handle = fopen('php://output', 'w');
            // CSV Header
            fputcsv($handle, [
                'ID',
                'Name',
                'Business Name',
                'CR Number',
                'ID Number',
                'CR/ID Match Score',
                'ID Expiry Score',
                'CR Expiry Score',
                'Business Type Score',
                'Activity Score',
                'CR/ID Total',
                'CR/ID Score',
                'POS Revenue',
                'POS Score',
                'Late Payments',
                'Repayment Score',
                'Industry',
                'Industry Score',
                'City',
                'City Tier Score',
                'Economic Activity Score',
                'Default Rate Score',
                'Location Score',
                'Total Score'
            ]);

            foreach ($risks as $risk) {
                fputcsv($handle, [
                    $risk->id,
                    $risk->name,
                    $risk->business_name,
                    $risk->cr_number,
                    $risk->id_number,
                    $risk->cr_id_match_score,
                    $risk->id_expiry_score,
                    $risk->cr_expiry_score,
                    $risk->business_type_score,
                    $risk->activity_score,
                    $risk->cr_id_total,
                    $risk->cr_id_score,
                    $risk->pos_revenue,
                    $risk->pos_score,
                    $risk->late_payments,
                    $risk->repayment_score,
                    $risk->industry,
                    $risk->industry_score,
                    $risk->location['city'] ?? '-',
                    $risk->location['tier_score'] ?? 0,
                    $risk->location['activity_score'] ?? 0,
                    $risk->location['default_rate_score'] ?? 0,
                    $risk->location_score,
                    $risk->total_score,
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

        return $response;
    }

    public function exportPdf(Request $request)
    {
        $users = $this->getUsersWithFilters($request);

        $risks = $users->map(function ($user) {
            return $this->calculateRiskForUser($user);
        });

        $pdf = Pdf::loadView('admin.risk-management.pdf', ['risks' => $risks])
            ->setPaper('A4', 'landscape');


        return $pdf->stream('risk_scores_' . date('Ymd_His') . '.pdf');
    }

    private function getUsersWithFilters(Request $request)
    {
        $search = $request->input('search');

        $usersQuery = User::whereIn('user_type', ['merchant', 'user'])
            ->with(['merchant.businessType', 'customer.businessType', 'transactions']);

        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('iqama', 'like', "%{$search}%")
                    ->orWhereHas('merchant', function ($q2) use ($search) {
                        $q2->where('business_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q3) use ($search) {
                        $q3->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        return $usersQuery->get();
    }

    public function scoreUpdate(Request $request)
    {
        // 0506879195
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'risk_score' => 'required|numeric|min:0|max:100',
            'reason' => 'required|string',
        ]);

        $riskScore = RiskScore::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'risk_score' => $request->risk_score,
                'reason' => $request->reason,
            ]
        );

        session()->forget('otp_verified');

        return redirect()->back()->with('success', 'Risk score updated successfully.');
    }
}
