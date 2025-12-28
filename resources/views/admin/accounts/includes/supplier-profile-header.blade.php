@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@php
    $riskScore = get_risk_score($merchant->user_id);
    $status = strtolower($merchant->status);
    $overallScore = $riskScore['omrs'] ?? 0;

    $riskConfig = match (true) {
        $overallScore >= 80 => ['label' => 'Low Risk', 'color' => 'emerald', 'icon' => 'chart-line-up'],
        $overallScore >= 60 => ['label' => 'Medium Risk', 'color' => 'amber', 'icon' => 'information-2'],
        $overallScore >= 40 => ['label' => 'High Risk', 'color' => 'orange', 'icon' => 'warning'],
        default => ['label' => 'Critical Risk', 'color' => 'rose', 'icon' => 'shield-cross'],
    };

    $statusStyles = [
        'approved' => ['color' => 'success', 'label' => 'Approved'],
        'under_review' => ['color' => 'secondary', 'label' => 'Under Review'],
        'contract_sent' => ['color' => 'primary', 'label' => 'Contract Sent'],
        'active' => ['color' => 'primary', 'label' => 'Active'],
        'pending' => ['color' => 'warning', 'label' => 'Pending'],
        'suspended' => ['color' => 'danger', 'label' => 'Suspended'],
        'blacklisted' => ['color' => 'dark', 'label' => 'Blacklisted'],
    ];

    $currentStatus = $statusStyles[$status] ?? ['color' => 'secondary', 'label' => ucfirst($status)];
    $sellerShop = App\Models\ShopSetting::where('user_id', $merchant->user_id)->select('address')->first();
@endphp


<section class="card bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex flex-col lg:flex-row justify-between items-start gap-6">

        <div class="flex items-center gap-5">
            @php
                use Illuminate\Support\Str;

                $shopLogo = \App\Models\ShopSetting::where('user_id', $merchant->user_id)->first();

                if ($shopLogo && !empty($shopLogo->logo)) {
                    if (Str::startsWith($shopLogo->logo, ['http://', 'https://'])) {
                        $logoPath = supplierMedia($shopLogo->logo);
                    } else {
                        $logoPath = 'https://partners.arabianpay.net' . $shopLogo->logo;
                    }
                } elseif (!empty($merchant->user->profile_photo_path)) {
                    $logoPath = 'https://partners.arabianpay.net/storage/' . $merchant->user->profile_photo_path;
                } else {
                    $logoPath = asset('assets/media/images/ap.png');
                }

                $isDefaultLogo = Str::contains($logoPath, 'assets/media/images/ap.png');
            @endphp

            <div
                class="shrink-0 w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-200 overflow-hidden">

                @if ($isDefaultLogo)
                    <i class="ki-filled ki-shop text-3xl text-slate-400"></i>
                @else
                    <img src="{{ $logoPath }}" alt="Shop Logo" class="w-full h-full object-contain" />
                @endif

            </div>

            <div class="flex flex-col gap-1.5">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $merchant->user?->business_name }}
                    </h1>
                    <span
                        class="badge badge-sm badge-outline badge-{{ $currentStatus['color'] }} px-2.5 py-0.5 font-bold uppercase text-[10px]">
                        {{ $currentStatus['label'] }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-medium">
                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-geolocation text-gray-500 text-sm"> </i>
                        <span class="text-gray-600 font-medium">
                            {{ $sellerShop?->address ? maskedSensitiveText('full_address', $sellerShop->address) : 'N/A' }}
                        </span>
                    </div>


                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-phone text-gray-500 text-sm"> </i>
                        @php
                            $rawPhone = $merchant->user->phone_number;
                            $authPhone = authorizeSensitiveFileOrDeny('phone_number', $rawPhone, '#');
                        @endphp
                        <a class="underline link"
                            href="{{ $authPhone !== '#' ? 'tel:' . $authPhone : 'javascript:void(0)' }}">
                            {{ $rawPhone ? maskedSensitiveText('phone_number', $rawPhone) : 'N/A' }}
                        </a>
                    </div>

                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-sms text-gray-500 text-sm"> </i>
                        @php
                            $rawEmail = $merchant->user->email;
                            $authEmail = authorizeSensitiveFileOrDeny('email_address', $rawEmail, '#');
                        @endphp
                        <a class="underline link"
                            href="{{ $authEmail !== '#' ? 'mailto:' . $authEmail : 'javascript:void(0)' }}">
                            {{ $rawEmail ? maskedSensitiveText('email_address', $rawEmail) : 'N/A' }}
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-medium">
                    <a type="button" onclick="showRiskScoreModal()" data-modal-toggle="#risk-score-modal"
                        class="flex items-center gap-1 text-{{ $riskConfig['color'] }}-600 hover:opacity-75 transition cursor-pointer underline decoration-dotted">
                        <i class="ki-filled ki-{{ $riskConfig['icon'] }} text-sm"></i>
                        {{ $riskConfig['label'] }} ({{ $overallScore }})
                    </a>

                    <div class="flex items-center gap-4 text-slate-500 border-l border-slate-200 pl-4">
                        @if ($merchant->cr_number)
                            <span class="flex items-center gap-1"><i class="ki-filled ki-document text-sm"></i>
                                {{ translate('CR Number') }}:
                                {{ maskedSensitiveText('business_identity', $merchant->cr_number) }}</span>
                        @endif
                        <span class="flex items-center gap-1"><i class="ki-filled ki-calendar text-sm"></i>
                            {{ translate('Joined') }}:
                            {{ $merchant->created_at?->format('M Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
            <button class="btn btn-sm btn-light border-slate-300 flex items-center gap-2"
                data-modal-toggle="#transfer_request">
                <i class="ki-filled ki-arrows-loop"></i> {{ translate('Transfer Request') }}
            </button>

            @can('supplier.manage')
                @include('admin.accounts.components.merchant-status-dropdown', ['merchant' => $merchant])
            @endcan
        </div>
    </div>
</section>

@include('admin.accounts.components.merchant-approve-modal', ['merchant' => $merchant])

{{-- Other Modals --}}
@include('admin.components.transfer-request', [
    'employees' => getEmployees(),
    'model_type' => 'App\Models\Merchant',
    'model_id' => $merchant->id,
])

@include('admin.accounts.components.risk-score-modal', [
    'riskScore' => $riskScore,
    'overallScore' => $overallScore,
    'riskLevel' => $riskConfig,
])
