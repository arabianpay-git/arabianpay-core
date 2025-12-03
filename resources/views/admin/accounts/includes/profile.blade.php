<style>
    .logo {
        height: 100px !important;
        width: 100px !important;
        border-radius: 100%;
    }
</style>

<div class="container-fixed">
    <div class="flex items-start justify-between gap-4 py-4 lg:py-6">

        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center rounded-full border-2 border-success-clarity shrink-0 bg-light">
                @php
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
                @endphp


                <img class="logo" src="{{ $logoPath }}" alt="Shop Logo" />
            </div>

            <div class="flex flex-col">
                <div class="flex items-center gap-1.5">
                    <div class="text-xl leading-6 font-bold text-gray-900">
                        {{ $merchant->user?->business_name }}
                    </div>
                    @php
                        $status = strtolower($merchant->status);

                        $statusMap = [
                            'approved' => ['success', 'Approved'],
                            'under_review' => ['secondary', 'Under Review'],
                            'contract_sent' => ['primary', 'Contract Sent'],
                            'active' => ['primary', 'Active'],
                            'pending' => ['warning', 'Pending'],
                            'suspended' => ['danger', 'Suspended'],
                            'blacklisted' => ['info', 'Blacklisted'],
                        ];
                    @endphp

                    @if ($status === 'active')
                        <svg class="text-primary" fill="none" height="16" width="15" viewBox="0 0 15 16">
                            <path d="M14.5425 6.89749L13.5 5.83999..." fill="currentColor" />
                        </svg>
                    @elseif(isset($statusMap[$status]))
                        <span class="badge badge-sm badge-outline badge-{{ $statusMap[$status][0] }}">
                            {{ $statusMap[$status][1] }}
                        </span>
                    @else
                        <span class="badge badge-sm badge-outline badge-dark">
                            {{ ucwords(str_replace('_', ' ', $merchant->status)) }}
                        </span>
                    @endif

                </div>

                <div class="flex items-center mt-1 text-sm">
                    <i class="ki-filled ki-abstract-41 text-gray-500 text-sm me-1"> </i>
                    <span class="text-gray-600 font-medium">
                        {{ $merchant->user?->first_name }} {{ $merchant->user?->last_name }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center justify-start gap-3 text-sm pt-4 contact-info-list">
                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-geolocation text-gray-500 text-sm"> </i>
                        <span class="text-gray-600 font-medium">
                            {{ $sellerShop->address ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-phone text-gray-500 text-sm"> </i>
                        <a class="text-gray-600 font-medium hover:text-primary"
                            href="tel: {{ $merchant->user->phone_number }}">
                            {{ $merchant->user->phone_number }}
                        </a>
                    </div>

                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-sms text-gray-500 text-sm"> </i>
                        <a class="text-gray-600 font-medium hover:text-primary"
                            href="mailto: {{ $merchant->user->email }}">
                            {{ $merchant->user->email }}
                        </a>
                    </div>

                    <div class="flex gap-1.25 items-center separator">
                        <i class="ki-filled ki-calendar text-gray-500 text-sm"> </i>
                        {{ $merchant->user->created_at->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="shrink-0 pt-4">
            <button class="btn btn-sm btn-light" data-modal-toggle="#transfer_request">
                <i class="ki-filled ki-disconnect"></i> Transfer Request
            </button>
        </div>
    </div>

</div>


@include('admin.components.transfer-request', [
    'employees' => getEmployees(),
    'model_type' => 'App\Models\Merchant',
    'model_id' => $merchant->id,
])
