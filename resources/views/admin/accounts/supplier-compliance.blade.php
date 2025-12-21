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

            .card .relative {
                overflow: visible;
            }

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
                                $compliance = [];

                                $approvals = App\Models\Approval::where('user_id', $merchant->user_id)->get();
                                // Make first approval available as $contract (if any) for start/end dates display
                                $contract = $approvals->first();

                                foreach ($approvals as $approval) {
                                    $compliance[] = [
                                        'title' => translate('Supplier Contract'),
                                        'file' => $approval->contract,
                                        'permission' => 'documents_id_cr_contracts',
                                    ];
                                }

                                $fixedDocuments = [
                                    [
                                        'title' => translate('CR File'),
                                        'file' => supplierMedia($merchant->registration_number_form),
                                        'permission' => 'documents_id_cr_contracts',
                                    ],
                                    [
                                        'title' => translate('VAT Register File'),
                                        'file' => supplierMedia($merchant->vat_register_file),
                                        'permission' => 'documents_id_cr_contracts',
                                    ],
                                    [
                                        'title' => translate('Return Policy File'),
                                        'file' => supplierMedia($merchant->return_policy_file),
                                        'permission' => 'documents_id_cr_contracts',
                                    ],
                                    [
                                        'title' => translate('Delivery Policy File'),
                                        'file' => supplierMedia($merchant->exchange_policy_file),
                                        'permission' => 'documents_id_cr_contracts',
                                    ],
                                    [
                                        'title' => translate('Cancel Policy File'),
                                        'file' => supplierMedia($merchant->cancel_policy_file),
                                        'permission' => 'documents_id_cr_contracts',
                                    ],
                                    [
                                        'title' => translate('ID Image'),
                                        'file' => supplierMedia($merchant->owner_iqama_image),
                                        'permission' => 'national_id_iqama',
                                    ],
                                    [
                                        'title' => translate('Balady Certificate'),
                                        'file' => supplierMedia($merchant->balady_certificate),
                                        'permission' => 'documents_id_cr_contracts',
                                    ],
                                ];

                                $compliance = array_merge($compliance, $fixedDocuments);

                                if ($merchant->user->is_manager) {
                                    $compliance[] = [
                                        'title' => translate('Company Approval Letter for Manager'),
                                        'file' => supplierMedia($merchant->manager_approval),
                                        'permission' => 'documents_id_cr_contracts',
                                    ];
                                }

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
                                    <div class="top-3 left-3 badge-wrapper">
                                        @if ($item['file'])
                                            <span
                                                class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 border-radius shadow-sm">
                                                {{ translate('COMPLIANT') }}
                                            </span>
                                        @else
                                            <span
                                                class="bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 border-radius shadow-sm">
                                                {{ translate('MISSING') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div>
                                                <h4 class="text-lg font-medium text-gray-800 dark:text-white">
                                                    {{ $item['title'] }}</h4>

                                                @if ($item['title'] === translate('Supplier Contract'))
                                                    @php
                                                        $startDate = null;
                                                        $endDate = null;

                                                        if ($contract && $contract->created_at) {
                                                            $startDate = \Carbon\Carbon::parse(
                                                                $contract->created_at,
                                                            )->format('F j, Y, h:i A');
                                                        }

                                                        if ($contract && $contract->contract_end_date) {
                                                            $endDate = \Carbon\Carbon::parse(
                                                                $contract->contract_end_date,
                                                            )->format('F j, Y, h:i A');
                                                        }
                                                    @endphp

                                                    @if ($startDate)
                                                        <p class="text-sm text-gray-600 dark:text-gray-300">Contract Start
                                                            Date:
                                                            {{ maskedSensitiveText('documents_id_cr_contracts', $startDate, 3, 5, 4) }}
                                                        </p>
                                                    @endif
                                                    @if ($endDate)
                                                        <p class="text-sm text-gray-600 dark:text-gray-300">Contract End
                                                            Date:
                                                            {{ maskedSensitiveText('documents_id_cr_contracts', $endDate, 3, 5, 4) }}
                                                        </p>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        @if ($item['file'])
                                            {{-- Use authorizeSensitiveFileOrDeny with actual file path.
                                                 Pass null as fallback so we can show 'Access Restricted' UI. --}}
                                            @php
                                                // $item['file'] should already be a path (supplierMedia() or raw column)
                                                $authorizedPath = authorizeSensitiveFileOrDeny(
                                                    $item['permission'],
                                                    $item['file'],
                                                    null,
                                                );
                                            @endphp

                                            @if ($authorizedPath)
                                                <a href="{{ asset($authorizedPath) }}" target="_blank"
                                                    class="px-3 py-1 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition duration-150">
                                                    {{ translate('View') }}
                                                </a>
                                            @else
                                                <span
                                                    class="text-amber-600 text-sm italic">{{ translate('Access Restricted') }}</span>
                                            @endif
                                        @else
                                            <span
                                                class="text-red-500 text-sm italic">{{ translate('Not uploaded') }}</span>
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
                                        {{ translate('IBAN Certificates') }}</h4>

                                    <div class="space-y-4">
                                        @foreach ($relatedUserIds as $userId)
                                            @php
                                                $user = \App\Models\User::find($userId);
                                                $userBanks = $ibanBanks->where('user_id', $userId);
                                                $hasAnyCert = $userBanks->contains(
                                                    fn($b) => !empty($b->iban_certificate),
                                                );
                                            @endphp

                                            <div
                                                class="border mb-3 border-gray-300 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 shadow-sm transition hover:shadow-md relative">
                                                <div class="top-3 left-3 badge-wrapper">
                                                    <span
                                                        class="text-xs font-semibold px-3 py-1 border-radius shadow-sm {{ $hasAnyCert ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $hasAnyCert ? translate('COMPLIANT') : translate('MISSING') }}
                                                    </span>
                                                </div>

                                                <h5 class="text-md font-semibold text-gray-800 dark:text-white mb-2">
                                                    {{-- If you want the user name masked by permission, use maskedSensitiveText --}}
                                                    {{ maskedSensitiveText('authorized_person_name', trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) }}
                                                    ({{ $user->business_name ?? translate('N/A') }})
                                                </h5>

                                                <div class="space-y-2">
                                                    @forelse ($userBanks as $bank)
                                                        <div
                                                            class="flex justify-between items-center p-2 border rounded-md bg-gray-50 dark:bg-gray-700">
                                                            <span class="font-medium text-gray-800 dark:text-white">
                                                                {{ translate('IBAN') }}:
                                                                {{ $bank->iban ? maskedSensitiveText('iban_bank_account', $bank->iban, 5, 3) : 'N/A' }}
                                                            </span>
                                                            @if ($bank->iban_certificate)
                                                                @php
                                                                    $ibanCertificatePath = supplierMedia(
                                                                        $bank->iban_certificate,
                                                                    );
                                                                    $authorizedIbanPath = authorizeSensitiveFileOrDeny(
                                                                        'iban_bank_account',
                                                                        $ibanCertificatePath,
                                                                        null,
                                                                    );
                                                                @endphp

                                                                @if ($authorizedIbanPath)
                                                                    <a href="{{ asset($authorizedIbanPath) }}"
                                                                        target="_blank"
                                                                        class="px-3 py-1 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition">
                                                                        {{ translate('View') }}
                                                                    </a>
                                                                @else
                                                                    <span
                                                                        class="text-amber-600 text-sm italic">{{ translate('Access Restricted') }}</span>
                                                                @endif
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
