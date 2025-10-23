@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }

            /* Badge small tweaks so it looks consistent and not clipped */
            .card .relative {
                overflow: visible;
            }

            /* optional: slightly increase z-index for badges on stacked elements */
            .badge-wrapper {
                z-index: 12;
                pointer-events: none;
            }

            .border-radius {
                border-radius: 5px;
            }
        </style>

        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            @include('admin.accounts.includes.profile')
        </div>

        @include('admin.accounts.includes.header')

        <div class="container-fixed">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ translate('Supplier Compliance') }}</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                <div class="col-span-1 lg:col-span-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ translate('Compliance Documents') }}</h3>
                        </div>
                        <div class="card-body space-y-6">

                            @php
                                // ------------------------------
                                // 1. Global compliance documents
                                // ------------------------------
                                $compliance = [];

                                $approvals = App\Models\Approval::where('user_id', $merchant->user_id)->get();
                                foreach ($approvals as $approval) {
                                    $compliance[] = [
                                        'title' => translate('Supplier Contract'),
                                        'file' => $approval->contract,
                                    ];
                                }

                                $fixedDocuments = [
                                    [
                                        'title' => translate('CR File'),
                                        'file' => supplierMedia($merchant->registration_number_form),
                                    ],
                                    [
                                        'title' => translate('VAT Register File'),
                                        'file' => supplierMedia($merchant->vat_register_file),
                                    ],
                                    [
                                        'title' => translate('Return Policy File'),
                                        'file' => supplierMedia($merchant->return_policy_file),
                                    ],
                                    [
                                        'title' => translate('Delivery Policy File'),
                                        'file' => supplierMedia($merchant->exchange_policy_file),
                                    ],
                                    [
                                        'title' => translate('Cancel Policy File'),
                                        'file' => supplierMedia($merchant->cancel_policy_file),
                                    ],
                                    [
                                        'title' => translate('ID Image'),
                                        'file' => supplierMedia($merchant->owner_iqama_image),
                                    ],
                                    [
                                        'title' => translate('Balady Certificate'),
                                        'file' => supplierMedia($merchant->balady_certificate),
                                    ],
                                ];

                                $compliance = array_merge($compliance, $fixedDocuments);

                                if ($merchant->is_manager) {
                                    $compliance[] = [
                                        'title' => translate('Company Approval Letter for Manager'),
                                        'file' => supplierMedia($merchant->manager_approval),
                                    ];
                                }

                                // ------------------------------
                                // 2. IBAN Certificates
                                // ------------------------------
                                $mainUserId = $merchant->user->main_user_id ?: $merchant->user_id;

                                $relatedUserIds = \App\Models\User::where(function ($q) use ($mainUserId) {
                                    $q->where('id', $mainUserId)->orWhere('main_user_id', $mainUserId);
                                })->pluck('id');

                                $ibanBanks = App\Models\SupplierBank::whereIn('user_id', $relatedUserIds)->get();
                            @endphp

                            {{-- Render global compliance documents --}}
                            @foreach ($compliance as $item)
                                <div
                                    class="relative border p-4 mt-2 rounded-xl shadow-sm bg-white dark:bg-gray-800 hover:shadow-lg transition-all">

                                    {{-- Badge (top-left) --}}
                                    <div class="top-3 left-3 badge-wrapper">
                                        @if ($item['file'])
                                            <span
                                                class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 border-radius shadow-sm">
                                                COMPLIANT
                                            </span>
                                        @else
                                            <span
                                                class="bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 border-radius shadow-sm">
                                                MISSING
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div>
                                                <h4 class="text-lg font-medium text-gray-800 dark:text-white">
                                                    {{ $item['title'] }}</h4>

                                                {{-- Show contract dates if Supplier Contract --}}
                                                @if ($item['title'] === translate('Supplier Contract'))
                                                    @php
                                                        $startDate =
                                                            $contract && $contract->created_at
                                                                ? Carbon\Carbon::parse($contract->created_at)->format(
                                                                    'F j, Y, h:i A',
                                                                )
                                                                : null;

                                                        $endDate =
                                                            $contract && $contract->contract_end_date
                                                                ? Carbon\Carbon::parse(
                                                                    $contract->contract_end_date,
                                                                )->format('F j, Y, h:i A')
                                                                : null;
                                                    @endphp

                                                    @if ($startDate)
                                                        <p class="text-sm text-gray-600 dark:text-gray-300">Contract Start
                                                            Date: {{ $startDate }}</p>
                                                    @endif

                                                    @if ($endDate)
                                                        <p class="text-sm text-gray-600 dark:text-gray-300">Contract End
                                                            Date: {{ $endDate }}</p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        @if ($item['file'])
                                            <a href="{{ asset($item['file']) }}" target="_blank"
                                                class="px-3 py-1 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition duration-150">
                                                {{ translate('View') }}
                                            </a>
                                        @else
                                            <span class="text-red-500 text-sm italic">{{ translate('Not uploaded') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            {{-- Render IBAN certificates --}}
                            @if ($ibanBanks->isEmpty())
                                <div
                                    class="border border-red-300 p-4 mt-4 rounded-xl shadow-sm bg-white dark:bg-gray-800 text-center">
                                    <span
                                        class="text-red-500 text-sm italic">{{ translate('No IBAN Certificates uploaded') }}</span>
                                </div>
                            @else
                                <div
                                    class="border border-gray-200 dark:border-gray-700 p-5 mt-4 rounded-xl shadow-sm bg-gray-50 dark:bg-gray-900 transition hover:shadow-md">
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                        {{ translate('IBAN Certificates') }}
                                    </h4>

                                    <div class="space-y-4">
                                        @foreach ($relatedUserIds as $userId)
                                            @php
                                                $user = \App\Models\User::find($userId);
                                                $userBanks = $ibanBanks->where('user_id', $userId);
                                            @endphp

                                            <div
                                                class="border mb-3 border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm transition hover:shadow-md relative">
                                                {{-- Badge for the user-card as a whole:
                                                     If any bank for this user has certificate, show COMPLIANT,
                                                     otherwise MISSING. --}}
                                                @php
                                                    $hasAnyCert = $userBanks->contains(function ($b) {
                                                        return !empty($b->iban_certificate);
                                                    });
                                                @endphp
                                                <div class="top-3 left-3 badge-wrapper">
                                                    @if ($hasAnyCert)
                                                        <span
                                                            class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 border-radius shadow-sm">
                                                            COMPLIANT
                                                        </span>
                                                    @else
                                                        <span
                                                            class="bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 border-radius shadow-sm">
                                                            MISSING
                                                        </span>
                                                    @endif
                                                </div>

                                                <h5 class="text-md font-semibold text-gray-800 dark:text-white mb-2">
                                                    {{ $user->first_name }} {{ $user->last_name }}
                                                    ({{ $user->business_name ?? translate('N/A') }})
                                                </h5>

                                                <div class="space-y-2">
                                                    @forelse ($userBanks as $bank)
                                                        <div
                                                            class="flex justify-between items-center p-2 border rounded-md bg-gray-50 dark:bg-gray-700">
                                                            <span class="font-medium text-gray-800 dark:text-white">
                                                                {{ translate('IBAN') }}: {{ $bank->iban ?? 'N/A' }}
                                                            </span>
                                                            @if ($bank->iban_certificate)
                                                                <a href="{{ asset(supplierMedia($bank->iban_certificate)) }}"
                                                                    target="_blank"
                                                                    class="px-3 py-1 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition">
                                                                    {{ translate('View') }}
                                                                </a>
                                                            @else
                                                                <span
                                                                    class="text-red-500 text-sm italic">{{ translate('Not uploaded') }}</span>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="text-red-500 text-sm italic">
                                                            {{ translate('Not uploaded') }}</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
