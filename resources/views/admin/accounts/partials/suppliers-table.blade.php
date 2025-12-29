<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th>
                    @if (!isset($onboardingStep) || !in_array($onboardingStep, ['basic-info', 'business-revenue', 'business-verification']))
                        <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox"
                            id="select-all-checkbox">
                    @endif
                </th>
                <th class="w-[60px] text-center">{{ translate('ID') }}</th>
                <th>{{ translate('Name') }}</th>
                <th>{{ translate('CR Number') }}</th>
                <th>{{ translate('Business Type') }}</th>
                <th>{{ translate('Assigned To') }}</th>
                <th>{{ translate('Status') }}</th>
                <th>{{ translate('Commission') }}</th>
                <th>{{ translate('Member Since') }}</th>
                <th>{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($merchants as $item)
                @php
                    $isUserOnly = isset($item->is_user_only) && $item->is_user_only;
                @endphp
                <tr>
                    <td>
                        @if (!$isUserOnly)
                            <input class="checkbox checkbox-sm row-checkbox" type="checkbox" name="ids[]"
                                value="{{ $item->id }}">
                        @else
                            {{-- Disabled checkbox for user-only records --}}
                            <input class="checkbox checkbox-sm" type="checkbox" disabled>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($isUserOnly)
                            User-{{ $item->user_id }}
                        @else
                            {{ $item->id }}
                        @endif
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            @if ($isUserOnly)
                                {{-- For user-only records --}}
                                <span class="text-gray-600">Phone: {{ $item->user->phone_number ?? 'N/A' }}</span>
                                <br>
                                <small class="text-gray-500">
                                    @if (isset($item->user->business_name) && $item->user->business_name)
                                        Business: {{ $item->user->business_name }}
                                    @else
                                        — Business Name Not Set —
                                    @endif
                                </small>
                            @else
                                {{-- For merchant records --}}
                                <a href="{{ route('supplierProfile', ['id' => $item->user_id]) }}" class="underline">
                                    {{ $item->user->first_name ?? 'N/A' }} {{ $item->user->last_name ?? 'N/A' }}
                                </a>
                                <br>
                                <small class="text-gray-500">— {{ $item->user?->business_name ?? '—' }}</small>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if ($isUserOnly)
                            N/A
                        @else
                            {{ maskedSensitiveText('business_identity', $item->cr_number) ?: '—' }}
                        @endif
                    </td>
                    <td>
                        @if ($isUserOnly)
                            N/A
                        @else
                            {{ $item->businessType->name ?? 'N/A' }}
                        @endif
                    </td>
                    <td>
                        @if ($isUserOnly)
                            — Not Assigned —
                        @else
                            {{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : __('--Not Assigned--') }}
                        @endif
                    </td>
                    <td>
                        @if ($isUserOnly)
                            <span class="badge badge-sm badge-outline badge-warning">
                                Onboarding
                            </span>
                        @else
                            <span
                                class="badge badge-sm badge-outline
                                    @switch($item->status)
                                        @case('under_review')
                                            badge-info
                                            @break
                                        @case('contract_sent')
                                            badge-primary
                                            @break
                                        @case('active')
                                            badge-success
                                            @break
                                        @case('pending')
                                            badge-warning
                                            @break
                                        @case('approved')
                                            badge-success
                                            @break
                                        @case('suspended')
                                            badge-dark
                                            @break
                                        @case('blacklisted')
                                            badge-danger
                                            @break
                                        @default
                                            badge-secondary
                                    @endswitch
                                ">
                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                            </span>
                        @endif
                    </td>

                    <td class="text-center">
                        <div class="ml-2 flex items-center gap-1">
                            @if ($isUserOnly)
                                —
                            @else
                                <span class="text-sm text-gray-600">{{ $item->approval?->commission ?? '—' }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-center">
                        {{ $item->created_at->format(dateFormat()) }}
                    </td>

                    <td>
                        <div class="flex gap-1">
                            @if ($isUserOnly)
                                {{-- Limited actions for user-only records --}}
                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                    title="{{ translate('View User Profile') }}"
                                    href="{{ route('supplierProfile', ['id' => $item->user_id]) }}">
                                    <i class="ki-filled ki-notepad-edit"></i>
                                </a>
                            @else
                                {{-- Full actions for merchant records --}}
                                <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                    title="{{ translate('View Supplier Profile') }}"
                                    href="{{ route('supplierProfile', ['id' => $item->user_id]) }}">
                                    <i class="ki-filled ki-notepad-edit"></i>
                                </a>

                                <button class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                    title="{{ translate('View Transfer Requests') }}"
                                    data-modal-toggle="#transfer_detail" data-model-id="{{ $item->id }}"
                                    data-model-type="App\Models\Merchant">
                                    <i class="ki-filled ki-disconnect"></i>
                                </button>

                                @if (Auth::user()->user_type == 'admin')
                                    <a target="__blank" class="btn btn-sm btn-icon btn-clear btn-warning"
                                        title="{{ translate('Login as Partner') }}"
                                        href="{{ route('impersonate.redirect', ['id' => $item->user_id]) }}"
                                        onclick="return confirm('Login to this partner account?')">
                                        <i class="ki-filled ki-wrench"></i>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-gray-500">
                        {{ __('No suppliers found.') }}
                        @if (request('onboarding_step'))
                            <br><small class="text-sm">No records found for {{ request('onboarding_step') }}
                                step</small>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('layouts.includes.table-pagination', ['paginator' => $merchants])
