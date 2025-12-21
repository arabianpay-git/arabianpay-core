@php
    $user = $user ?? null;
    $type = $type ?? 'customer';
    $riskAnalysis = $riskAnalysis ?? [];

    $profileRoute =
        $type === 'merchant'
            ? route('supplierProfile', ['id' => $user->id])
            : route('customerProfile', ['id' => $user->id]);

    $businessName = $user->business_name ?? translate('Not provided');
    $email = $user->email ?? translate('Not provided');
    $phone = $user->phone_number ?? translate('Not provided');
    $createdAt = $user->created_at ? $user->created_at->format('M d, Y') : translate('Unknown');
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start space-x-4">
            <!-- User Avatar -->
            <div class="flex-shrink-0">
                <div
                    class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                    {{ hasSensitivePermission('authorized_person_name')
                        ? substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)
                        : '**' }}

                </div>
            </div>

            <!-- User Details -->
            <div class="flex-1">
                <h2 class="text-xl font-semibold text-gray-900 mb-1">
                    {{ maskedSensitiveText('authorized_person_name', trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) }}
                </h2>


                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 mb-3">
                    <div class="flex items-center space-x-2">
                        <i class="ki-filled ki-sms text-gray-400"></i>
                        <span>
                            @php
                                $maskedEmail = maskedSensitiveText('email_address', $email ?? '-');
                            @endphp
                            <a class="underline link" href="mailto:{{ $maskedEmail }}">{{ $maskedEmail }}</a>
                        </span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="ki-filled ki-call text-gray-400"></i>

                        <span>
                            @php
                                $maskedPhone = maskedSensitiveText('phone_number', $phone ?? '-');
                            @endphp
                            <a class="underline link" href="tel:{{ $maskedPhone }}">{{ $maskedPhone }}</a>
                        </span>

                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="ki-filled ki-calendar text-gray-400"></i>
                        <span>{{ translate('Joined') }} {{ $createdAt }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="ki-filled ki-building mr-1"></i>
                        {{ maskedSensitiveText('business_identity', $businessName ?? '-') }}
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="ki-filled ki-profile-circle mr-1"></i>
                        {{ translate(ucfirst($type)) }}
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <i class="ki-filled ki-verify mr-1"></i>
                        {{ translate('User ID') }}: {{ $user->id }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-2 mt-4 lg:mt-0">
            <a href="{{ $profileRoute }}" class="btn btn-sm btn-outline btn-primary" target="_blank">
                <i class="ki-filled ki-user-square mr-1"></i>
                {{ translate('View Profile') }}
            </a>

            @if (Auth::user()->user_type == 'admin')
                <button class="btn btn-sm btn-outline btn-secondary" data-modal-toggle="#risk_weight_modal"
                    data-user-id="{{ $user->id }}">
                    <i class="ki-filled ki-setting-4 mr-1"></i>
                    {{ translate('Adjust Weights') }}
                </button>
            @endif
        </div>
    </div>
</div>

@include('admin.risk-management.components.risk-weight-modal')
