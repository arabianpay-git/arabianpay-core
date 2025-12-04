@php
    $scoreType = $scoreType ?? '';
    $scoreData = $scoreData ?? [];
    $title = $title ?? '';
    $icon = $icon ?? 'ki-document';
    $color = $color ?? 'blue';

    $score = $scoreData[$scoreType] ?? 0;
    $components = $scoreData['components'][$scoreType] ?? [];
    $notes = $scoreData['notes'][$scoreType] ?? '';
    $flags = $scoreData['flags'] ?? [];

    $colorClasses = [
        'blue' => [
            'bg' => 'bg-blue-500',
            'text' => 'text-blue-600',
            'light' => 'bg-blue-50',
            'border' => 'border-blue-200',
        ],
        'green' => [
            'bg' => 'bg-green-500',
            'text' => 'text-green-600',
            'light' => 'bg-green-50',
            'border' => 'border-green-200',
        ],
        'purple' => [
            'bg' => 'bg-purple-500',
            'text' => 'text-purple-600',
            'light' => 'bg-purple-50',
            'border' => 'border-purple-200',
        ],
        'orange' => [
            'bg' => 'bg-orange-500',
            'text' => 'text-orange-600',
            'light' => 'bg-orange-50',
            'border' => 'border-orange-200',
        ],
        'red' => [
            'bg' => 'bg-red-500',
            'text' => 'text-red-600',
            'light' => 'bg-red-50',
            'border' => 'border-red-200',
        ],
        'gray' => [
            'bg' => 'bg-gray-500',
            'text' => 'text-gray-600',
            'light' => 'bg-gray-50',
            'border' => 'border-gray-200',
        ],
    ];

    $currentColor = $colorClasses[$color] ?? $colorClasses['blue'];

    // Determine score status
    if ($score >= 70) {
        $scoreStatus = translate('Good');
        $statusColor = 'text-green-600 bg-green-100';
    } elseif ($score >= 50) {
        $scoreStatus = translate('Fair');
        $statusColor = 'text-yellow-600 bg-yellow-100';
    } else {
        $scoreStatus = translate('Poor');
        $statusColor = 'text-red-600 bg-red-100';
    }

    // CHS specific data
    $hasSimahReport = isset($components['raw_report']) && !empty($components['raw_report']);
    $simahData = $hasSimahReport ? $components['raw_report'] : null;
    $simahScoreData = $simahData['data']['score'][0] ?? null;
    $simahScoreCard = $simahScoreData['scoreCard'] ?? null;
    $simahReasonCodes = $simahScoreData['reasonCodes'] ?? [];
    $simahError = $simahScoreData['error'] ?? null;
    $summaryInfo = $simahData['data']['summaryInfo'] ?? null;
    $prevEnquiries = $simahData['data']['prevEnquiries'] ?? [];
    $reportDetails = $simahData['data']['reportDetails'] ?? null;
    $demographics = $simahData['data']['providedDemographicsInfo'] ?? null;

    // Check if it's a non-scorable case
$isNonScorable = $simahScoreCard['scoreCardCode'] ?? null === 'NS' || $score === 0;
@endphp

<div class="bg-white rounded-lg shadow-sm border {{ $currentColor['border'] }} p-5">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
            <div class="p-2 {{ $currentColor['light'] }} rounded-lg">
                <i class="ki-filled {{ $icon }} {{ $currentColor['text'] }} text-lg"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900">{{ translate($title) }}</h4>
                <p class="text-sm text-gray-500">{{ translate('Credit History from SIMAH') }}</p>
            </div>
        </div>

        <div class="text-right">
            <div class="text-2xl font-bold {{ $currentColor['text'] }} mb-1">
                {{ round($score, 1) }}
            </div>
            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                {{ $scoreStatus }}
            </span>
        </div>
    </div>

    <!-- SIMAH Report Status -->
    @if ($scoreType === 'chs')
        <div class="mb-4">
            @if ($hasSimahReport)
                @if ($isNonScorable)
                    <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="ki-filled ki-cross-circle text-red-500"></i>
                            <div>
                                <span class="font-medium text-red-700">{{ translate('Non-Scorable') }}</span>
                                <p class="text-xs text-red-600 mt-1">
                                    {{ $simahError ?? translate('No credit history available') }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center space-x-2">
                            <i class="ki-filled ki-check-circle text-green-500"></i>
                            <div>
                                <span
                                    class="font-medium text-green-700">{{ translate('SIMAH Report Available') }}</span>
                                <p class="text-xs text-green-600 mt-1">
                                    {{ translate('Credit score retrieved successfully') }}</p>
                            </div>
                        </div>
                        <a href="#" onclick="showFullSimahReport()"
                            class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 rounded">
                            {{ translate('View Full Report') }}
                        </a>
                    </div>
                @endif
            @else
                <div class="flex items-center space-x-2 text-sm text-yellow-600 bg-yellow-50 p-3 rounded-lg">
                    <i class="ki-filled ki-information-2"></i>
                    <div>
                        <span class="font-medium">{{ translate('No SIMAH Report') }}</span>
                        <p class="text-xs mt-1">{{ translate('Using default credit history score') }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- SIMAH Report Details -->
    @if ($scoreType === 'chs' && $hasSimahReport)
        <div class="space-y-4 mb-4">
            <!-- Report Summary -->
            <div class="grid grid-cols-2 gap-3">
                @if ($reportDetails)
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-xs text-gray-500">{{ translate('Report Date') }}</div>
                        <div class="font-medium text-sm">{{ $reportDetails['reportDate'] ?? 'N/A' }}</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-xs text-gray-500">{{ translate('Product Type') }}</div>
                        <div class="font-medium text-sm">{{ $reportDetails['productType'] ?? 'N/A' }}</div>
                    </div>
                @endif

                @if ($summaryInfo)
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-xs text-gray-500">{{ translate('Active Credit') }}</div>
                        <div class="font-medium text-sm">{{ $summaryInfo['summActiveCreditInstruments'] ?? 0 }}</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-xs text-gray-500">{{ translate('Defaults') }}</div>
                        <div class="font-medium text-sm">{{ $summaryInfo['summDefaults'] ?? 0 }}</div>
                    </div>
                @endif
            </div>

            <!-- Score Card Details -->
            @if ($simahScoreCard)
                <div class="border border-gray-200 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-800 mb-2">{{ translate('Score Card') }}</h5>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium {{ $isNonScorable ? 'text-red-600' : 'text-blue-600' }}">
                                {{ $simahScoreCard['scoreCardDescEn'] ?? 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Code: {{ $simahScoreCard['scoreCardCode'] ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-600">
                                {{ translate('Range') }}:
                                <span class="font-medium">
                                    {{ $simahScoreData['minimumScore'] ?? 0 }} -
                                    {{ $simahScoreData['maximumScore'] ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Reason Codes -->
            @if (!empty($simahReasonCodes))
                <div class="border border-gray-200 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-800 mb-3">{{ translate('Reason Codes') }}</h5>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($simahReasonCodes as $code)
                            <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ $code }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Components -->
    @if (!empty($components) && is_array($components))
        <div class="space-y-3 mb-4">
            @foreach ($components as $key => $value)
                @if (
                    !in_array($key, ['weights', 't_business_months', 'raw_report', 'score_card', 'reason_codes', 'max_dpd_12m']) &&
                        is_numeric($value))
                    @php
                        $componentScore = round($value, 1);
                        if ($componentScore >= 70) {
                            $compColor = 'text-green-600';
                            $compBg = 'bg-green-50';
                        } elseif ($componentScore >= 50) {
                            $compColor = 'text-yellow-600';
                            $compBg = 'bg-yellow-50';
                        } else {
                            $compColor = 'text-red-600';
                            $compBg = 'bg-red-50';
                        }
                    @endphp

                    <div class="flex items-center justify-between p-2 {{ $compBg }} rounded">
                        <span class="text-sm font-medium text-gray-700 capitalize">
                            {{ translate(str_replace('_', ' ', $key)) }}:
                        </span>
                        <span class="text-sm font-semibold {{ $compColor }}">
                            {{ $componentScore }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <!-- Additional Info for LPS -->
    @if ($scoreType === 'lps' && isset($components['t_business_months']))
        <div class="border-t border-gray-200 pt-3 mb-3">
            <div class="text-sm text-gray-600">
                <strong>{{ translate('Business Tenure') }}:</strong>
                {{ round($components['t_business_months'] / 12, 1) }} {{ translate('years') }}
                ({{ number_format($components['t_business_months'], 2) }} {{ translate('months') }})
            </div>
        </div>
    @endif

    <!-- Additional Info for BPS -->
    @if ($scoreType === 'bps')
        @php
            // 1. Try Merchant first
            $merchant = \App\Models\Merchant::where('user_id', $user->id)->first();

            // 2. If no merchant, try Customer
            if (!$merchant) {
                $merchant = \App\Models\Customer::where('user_id', $user->id)->first();
            }

            // 3. Get business category ids (array)
            $categoryIds = [];
            if ($merchant && $merchant->business_category_id) {
                // Ensure always array format
                $categoryIds = is_array($merchant->business_category_id)
                    ? $merchant->business_category_id
                    : json_decode($merchant->business_category_id, true);
            }

            // 4. Fetch the category names
            $categories = [];
            if (!empty($categoryIds)) {
                $categories = \App\Models\BusinessCategory::whereIn('id', $categoryIds)->pluck('name')->toArray();
            }
        @endphp

        <div class="border-t border-gray-200 pt-3 mb-3">
            <div class="text-sm text-gray-600 space-y-1">

                <div>
                    <strong>{{ translate('Business Sectors') }}:</strong>

                    @if (!empty($categories))
                        <span class="text-gray-800 font-medium">
                            {{ implode(', ', $categories) }}
                        </span>
                    @else
                        <span class="text-gray-500">{{ translate('Not provided') }}</span>
                    @endif
                </div>

                <div>
                    <strong>{{ translate('Region Sectors') }}:</strong>
                    <span class="text-gray-500">{{ $user->city->name ?? translate('Not provided') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Notes -->
    @if ($notes)
        <div class="border-t border-gray-200 pt-3">
            <details class="text-sm">
                <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">
                    {{ translate('Calculation Notes') }}
                </summary>
                <p class="mt-2 text-gray-600 text-xs bg-gray-50 p-2 rounded">
                    {{ translate($notes) }}
                </p>
            </details>
        </div>
    @endif
</div>
