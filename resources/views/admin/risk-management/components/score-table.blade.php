<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th class="w-[60px] text-center">{{ translate('User ID') }}</th>
                <th class="text-center">{{ translate('User Name') }}</th>
                <th class="w-[200px] text-center">{{ translate('CR & ID Validation') }}</th>
                <th class="text-center">{{ translate('POS Revenue') }}</th>
                <th class="text-center">{{ translate('Repayment Delays') }}</th>
                <th class="text-center">{{ translate('Industry Category') }}</th>
                <th class="w-[120px] text-left">{{ translate('Location Risk') }}</th>
                <th class="text-left">{{ translate('Google Reviews') }}</th>
                <th class="text-left">{{ translate('Risk Score') }}</th>
                <th class="text-left">{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($risks as $item)
                <tr>
                    <td class="text-center">{{ $item->id }}</td>
                    <td>
                        @if ($item->flagged)
                            <span class="menu-icon items-start w-[20px]">
                                <i class="ki-filled ki-flag text-lg text-danger"></i>
                            </span>
                        @endif

                        <div class="whitespace-nowrap">
                            @php
                                $type = request('type');
                                $profileRoute =
                                    $type === 'merchant'
                                        ? route('supplierProfile', ['id' => $item->id])
                                        : route('customerProfile', ['id' => $item->id]);
                            @endphp

                            <a href="{{ $profileRoute }}" class="underline">{{ $item->name ?? '-' }}</a>
                            <br>
                            <small class="text-gray-500">— {{ $item->business_name ?? '-' }}</small>
                        </div>

                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ translate('CR Number') }}: {{ $item->cr_number ?? '-' }}<br>
                            <small class="text-gray-500">— {{ translate('ID Number') }}:
                                {{ $item->id_number ?? '-' }}</small><br>
                            <small class="text-gray-500">— {{ translate('ID/CR Match') }}:
                                {{ $item->cr_id_match_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('ID Expiry Score') }}:
                                {{ $item->id_expiry_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('CR Expiry Score') }}:
                                {{ $item->cr_expiry_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('Business Type') }}:
                                {{ $item->business_type_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('Activity Match') }}:
                                {{ $item->activity_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('Total Raw') }}:
                                {{ $item->cr_id_total ?? 0 }}</small><br>
                            <small class="text-green-600 font-semibold">—
                                {{ translate('Weighted Score') }}:
                                {{ $item->cr_id_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ translate('Revenue') }}:
                            {{ number_format((float) ($item->pos_revenue ?? 0)) }}<br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->pos_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ translate('Late Payments') }}:
                            {{ $item->late_payments ?? 0 }}<br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->repayment_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ $item->industry ?? '-' }}<br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->industry_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ $item->location['city'] ?? '-' }}<br>
                            <small class="text-gray-500">
                                — {{ translate('Tier Score') }}:
                                {{ $item->location['tier_score'] ?? 0 }}<br>
                                — {{ translate('Activity') }}:
                                {{ $item->location['activity_score'] ?? 0 }}<br>
                                — {{ translate('Default Rate') }}:
                                {{ $item->location['default_rate_score'] ?? 0 }}
                            </small><br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->location_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        {{ translate('Overall') }}
                        {{ $item->google_rating ?? 0 }}/5
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            @php
                                $score = $item->total_score ?? 0;
                                if ($score >= 80) {
                                    $riskLevel = translate('Low Risk');
                                    $badgeColor = 'badge badge-sm badge-outline badge-success';
                                } elseif ($score >= 60) {
                                    $riskLevel = translate('Medium Risk');
                                    $badgeColor = 'badge badge-sm badge-outline badge-warning';
                                } else {
                                    $riskLevel = translate('High Risk');
                                    $badgeColor = 'badge badge-sm badge-outline badge-danger';
                                }
                            @endphp
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                {{ $score }}/100<br>- {{ $riskLevel }}
                            </span>
                        </div>
                    </td>


                    <td>
                        @if (Auth::user()->user_type == 'admin')
                            <div class="flex gap-1">
                                <!-- Existing Risk Score Edit Button -->
                                <a class="btn btn-sm btn-icon btn-clear btn-primary" data-modal-toggle="#score_modal"
                                    data-id="{{ $item->id }}" data-action="{{ route('risk.scoreUpdate') }}"
                                    data-score="{{ $item->risk_score ?? '' }}" data-reason="{{ $item->reason ?? '' }}"
                                    title="{{ translate('Manual Score Adjustment') }}">
                                    <i class="ki-filled ki-notepad-edit"></i>
                                </a>

                                <!-- New Set Risk Weights Button -->
                                <a class="btn btn-sm btn-icon btn-clear btn-info"
                                    title="{{ translate('Set Risk Weights') }}" data-modal-toggle="#weight_modal"
                                    data-user-id="{{ $item->id }}">
                                    <i class="ki-filled ki-setting-4"></i>
                                </a>
                            </div>
                        @else
                            {{ translate('Unauthorized') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@include('layouts.includes.table-pagination', ['paginator' => $risks])

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scoreButtons = document.querySelectorAll('[data-modal-toggle="#score_modal"]');
            scoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('score_modal');
                    const form = modal.querySelector('form');
                    form.setAttribute('action', this.getAttribute('data-action'));
                    form.querySelector('#user_id').value = this.getAttribute('data-id');
                    form.querySelector('#risk_score').value = this.getAttribute('data-score') || '';
                    form.querySelector('#reason').value = this.getAttribute('data-reason') || '';
                });
            });

            const weightButtons = document.querySelectorAll('[data-modal-toggle="#weight_modal"]');
            weightButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('weight_modal');
                    const userIdInput = modal.querySelector('input[name="user_id"]');
                    if (userIdInput) {
                        userIdInput.value = this.getAttribute('data-user-id');
                    }
                });
            });
        });
    </script>
@endpush
