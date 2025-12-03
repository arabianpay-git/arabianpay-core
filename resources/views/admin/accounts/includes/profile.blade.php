<div class="container-fixed">
    <div class="flex flex-col items-center gap-2 lg:gap-3.5 py-4 lg:pt-5 lg:pb-10">
        <div
            class="flex items-center justify-center rounded-full border-2 border-success-clarity size-[100px] shrink-0 bg-light">
            @php
                // 1. First check logo from ShopSetting
                $shopLogo = \App\Models\ShopSetting::where('user_id', $merchant->user_id)->first();

                if ($shopLogo && !empty($shopLogo->logo)) {
                    $logoPath = 'https://partners.arabianpay.net/storage' . $shopLogo->logo;
                }
                // 2. Then check profile_photo_path
                elseif (!empty($merchant->user->profile_photo_path)) {
                    $logoPath = 'https://partners.arabianpay.net/storage/' . $merchant->user->profile_photo_path;
                }
                // 3. Otherwise use default logo
                else {
                    $logoPath = asset('assets/media/images/ap.png');
                }
            @endphp

            <img class="size-[70px]" src="{{ $logoPath }}" alt="Shop Logo" />

        </div>
        <div class="flex items-center gap-1.5">
            <div class="text-lg leading-5 font-semibold text-gray-900">
                {{ $merchant->user?->business_name }}
            </div>
            @php
                $status = strtolower($merchant->status);
            @endphp

            @if ($status === 'approved')
                <span class="badge badge-sm badge-outline badge-success">
                    Approved
                </span>
            @elseif ($status === 'active')
                <svg class="text-primary" fill="none" height="16" viewBox="0 0 15 16" width="15"
                    xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.5425 6.89749L13.5 5.83999C13.4273 5.76877 ... Z" fill="currentColor" />
                </svg>
            @elseif ($status === 'under_review')
                <span class="badge badge-sm badge-outline badge-secondary">
                    Under Review
                </span>
            @elseif ($status === 'contracted')
                <span class="badge badge-sm badge-outline badge-primary">
                    Contracted
                </span>
            @elseif ($status === 'pending')
                <span class="badge badge-sm badge-outline badge-warning">
                    Pending
                </span>
            @elseif ($status === 'suspended')
                <span class="badge badge-sm badge-outline badge-danger">
                    Suspended
                </span>
            @elseif ($status === 'blacklisted')
                <span class="badge badge-sm badge-outline badge-info">
                    Blacklisted
                </span>
            @else
                <span class="badge badge-sm badge-outline badge-outline badge-dark">
                    {{ ucfirst($merchant->status) }}
                </span>
            @endif
        </div>
        <div class="flex flex-wrap justify-center gap-1 lg:gap-4.5 text-sm">
            <div class="flex gap-1.25 items-center">
                <i class="ki-filled ki-abstract-41 text-gray-500 text-sm"> </i>
                <span class="text-gray-600 font-medium">
                    {{ $merchant->user?->first_name }} {{ $merchant->user?->last_name }}
                </span>
            </div>

            <div class="flex gap-1.25 items-center">
                <i class="ki-filled ki-geolocation text-gray-500 text-sm"> </i>
                <span class="text-gray-600 font-medium">
                    {{ $sellerShop->address ?? 'N/A' }}
                </span>
            </div>

            <div class="flex gap-1.25 items-center">
                <i class="ki-filled ki-phone text-gray-500 text-sm"> </i>
                <a class="text-gray-600 font-medium hover:text-primary" href="tel: {{ $merchant->user->phone_number }}">
                    {{ $merchant->user->phone_number }}
                </a>
            </div>

            <div class="flex gap-1.25 items-center">
                <i class="ki-filled ki-sms text-gray-500 text-sm"> </i>
                <a class="text-gray-600 font-medium hover:text-primary" href="mailto: {{ $merchant->user->email }}">
                    {{ $merchant->user->email }}
                </a>
            </div>
        </div>
    </div>
    <div class="flex justify-end mb-4">
        <button class="btn btn-sm btn-light" data-modal-toggle="#transfer_request">
            <i class="ki-filled ki-disconnect">
            </i> Transfer Request
        </button>
    </div>
</div>


@include('admin.components.transfer-request', [
    'employees' => getEmployees(),
    'model_type' => 'App\Models\Merchant',
    'model_id' => $merchant->id,
])
