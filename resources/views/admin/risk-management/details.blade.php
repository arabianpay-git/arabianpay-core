@extends('layouts.base')

@section('title', translate('Risk Analysis Details -') . ' ' . $user->first_name . ' ' . $user->last_name)

@section('content')
    @push('styles')
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @endpush
    <div class="container-fluid px-4 py-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
            <div class="flex items-center space-x-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ translate('Risk Analysis Details') }}
                    </h1>
                    <p class="text-gray-600">
                        {{ translate('Comprehensive risk assessment for') }}
                        {{ maskedSensitiveText('authorized_person_name', trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) }}

                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3 mt-4 lg:mt-0">

                <a href="{{ route('risk.merchantScore', ['type' => $type]) }}" class="btn btn-sm btn-outline btn-secondary">
                    <i class="ki-filled ki-arrow-left"></i>
                    {{ translate('Back to Risk Management') }}
                </a>

                <!-- Refresh Button -->
                <button onclick="refreshRiskAnalysis()" class="btn btn-sm btn-outline btn-primary" id="refreshBtn"
                    style="margin-right: 5px;">
                    <i class="ki-filled ki-reload"></i>
                    {{ translate('Refresh Analysis') }}
                </button>

                <!-- Print Report -->
                <button onclick="window.print()" class="btn btn-sm btn-outline btn-secondary">
                    <i class="ki-filled ki-printer"></i>
                    {{ translate('Print Report') }}
                </button>
            </div>
        </div>

        <!-- User Info Card -->
        @include('admin.risk-management.components.user-info-card', [
            'user' => $user,
            'type' => $type,
            'riskAnalysis' => $riskAnalysis,
        ])

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <!-- OMRS Overview -->
            <div class="xl:col-span-1">
                @include('admin.risk-management.components.omrs-overview', [
                    'riskAnalysis' => $riskAnalysis,
                ])
            </div>

            <!-- Risk Distribution -->
            <div class="xl:col-span-2">
                @include('admin.risk-management.components.risk-distribution', [
                    'riskAnalysis' => $riskAnalysis,
                ])
            </div>
        </div>

        <!-- Detailed Score Components -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
            <!-- LPS Details -->
            @include('admin.risk-management.components.score-detail-card', [
                'user' => $user,
                'scoreType' => 'lps',
                'scoreData' => $riskAnalysis,
                'title' => translate('Legal & Profile Score'),
                'subtitle' => translate('Legal & Identity Verification'),
                'icon' => 'ki-document',
                'color' => 'blue',
            ])

            <!-- CHS Details -->
            @include('admin.risk-management.components.score-detail-card', [
                'user' => $user,
                'scoreType' => 'chs',
                'scoreData' => $riskAnalysis,
                'title' => translate('Credit History Score'),
                'subtitle' => translate('Credit History from SIMAH'),
                'icon' => 'ki-chart-line',
                'color' => 'green',
            ])

            <!-- BCS Details -->
            @include('admin.risk-management.components.score-detail-card', [
                'user' => $user,
                'scoreType' => 'bcs',
                'scoreData' => $riskAnalysis,
                'title' => translate('Banking & Cashflow Score'),
                'subtitle' => translate('Cashflow from Open Banking'),
                'icon' => 'ki-bank',
                'color' => 'purple',
            ])

            <!-- BPS Details -->
            @include('admin.risk-management.components.score-detail-card', [
                'user' => $user,
                'scoreType' => 'bps',
                'scoreData' => $riskAnalysis,
                'title' => translate('Business Profile Score'),
                'subtitle' => translate('Business Profile Overview'),
                'icon' => 'ki-briefcase',
                'color' => 'orange',
            ])

            <!-- BES Details -->
            @include('admin.risk-management.components.score-detail-card', [
                'user' => $user,
                'scoreType' => 'bes',
                'scoreData' => $riskAnalysis,
                'title' => translate('Behavioral & Experience Score'),
                'subtitle' => translate('Usage & Transaction Behavior'),
                'icon' => 'ki-chart-simple',
                'color' => 'red',
            ])

            <!-- CAF Details -->
            @include('admin.risk-management.components.score-detail-card', [
                'user' => $user,
                'scoreType' => 'caf',
                'scoreData' => $riskAnalysis,
                'title' => translate('Compliance Adjustment Factor'),
                'subtitle' => translate('Regulatory Risk Adjustment'),
                'icon' => 'ki-shield-tick',
                'color' => 'gray',
            ])
        </div>

        <!-- Risk Flags & Notes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Risk Flags -->
            @include('admin.risk-management.components.risk-flags', [
                'riskAnalysis' => $riskAnalysis,
                'alerts' => $riskData['alerts'] ?? [],
            ])

            <!-- Calculation Notes -->
            @include('admin.risk-management.components.calculation-notes', [
                'riskAnalysis' => $riskAnalysis,
            ])
        </div>

        <!-- Weights Information -->
        @include('admin.risk-management.components.weights-info', [
            'riskAnalysis' => $riskAnalysis,
        ])
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                    <i class="ki-filled ki-reload text-blue-600 text-xl animate-spin"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">{{ translate('Refreshing Analysis') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ translate('Please wait while we update the risk analysis...') }}
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function refreshRiskAnalysis() {
            const spinner = document.getElementById('loadingSpinner');
            const refreshBtn = document.getElementById('refreshBtn');

            // Show loading spinner
            spinner.classList.remove('hidden');
            refreshBtn.disabled = true;

            // Reload the page after a short delay to show the loading state
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Auto-refresh every 5 minutes (optional)
        setTimeout(() => {
            document.getElementById('refreshBtn').click();
        }, 300000);

        // Keyboard shortcut for refresh
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshRiskAnalysis();
            }
        });
    </script>
@endpush
