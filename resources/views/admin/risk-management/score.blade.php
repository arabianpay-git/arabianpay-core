@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex justify-between gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Risk Score Engine') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-secondary w-auto" data-modal-toggle="#weight_modal">
                        {{ translate('Set Risk Weights') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Risks') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5 items-center">
                            <div class="flex">
                                <form method="GET" action="{{ route('risk.score') }}" class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input name="search" type="text"
                                            placeholder="{{ translate('Search by first name, last name, email, business name, phone number, iqama number') }}"
                                            value="{{ request('search') }}" style="width: 492px;" />
                                    </label>
                                    <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                                        {{ translate('Search') }}
                                    </button>
                                </form>
                            </div>

                            <div class="flex gap-2 lg:gap-3">
                                {{-- <a href="{{ route('risk.exportCsv', request()->only('search')) }}"
                                    class="btn btn-sm btn-outline btn-success flex items-center"
                                    title="{{ translate('Export CSV') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M4 12h16M4 8h16M4 4h16" />
                                    </svg>
                                    {{ translate('Export CSV') }}
                                </a>

                                <a href="{{ route('risk.exportPdf', request()->only('search')) }}"
                                    class="btn btn-sm btn-outline btn-danger flex items-center"
                                    title="{{ translate('Export PDF') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ translate('Export PDF') }}
                                </a> --}}
                            </div>
                        </div>

                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="refund_requests_table">
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
                                                        {{ $item->name ?? '-' }}
                                                        <br>
                                                        <small class="text-gray-500">—
                                                            {{ $item->business_name ?? '-' }}</small>
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
                                                        {{ number_format($item->pos_revenue ?? 0) }}<br>
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
                                                                $badgeColor =
                                                                    'badge badge-sm badge-outline badge-success';
                                                            } elseif ($score >= 60) {
                                                                $riskLevel = translate('Medium Risk');
                                                                $badgeColor =
                                                                    'badge badge-sm badge-outline badge-warning';
                                                            } else {
                                                                $riskLevel = translate('High Risk');
                                                                $badgeColor =
                                                                    'badge badge-sm badge-outline badge-danger';
                                                            }
                                                        @endphp
                                                        <span
                                                            class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                                            {{ $score }}/100<br>- {{ $riskLevel }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if (Auth::user()->user_type == 'admin')
                                                        <div class="flex gap-1">
                                                            <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                                data-modal-toggle="#score_modal"
                                                                data-id="{{ $item->id }}"
                                                                data-action="{{ route('risk.scoreUpdate') }}"
                                                                data-score="{{ $item->risk_score ?? '' }}"
                                                                data-reason="{{ $item->reason ?? '' }}">
                                                                <i class="ki-filled ki-notepad-edit"></i>
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
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $risks])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal" data-modal="true" id="score_modal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">{{ translate('User Risk Management') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-0 pb-5">
                <form action="{{ route('risk.scoreUpdate') }}" method="POST" class="px-5 pt-3">
                    @csrf
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="mb-4">
                        <label class="form-label" for="risk_score">{{ translate('Score') }}</label>
                        <input type="text" id="risk_score" name="risk_score" class="input"
                            value="{{ old('risk_score') }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="reason">{{ translate('Reason') }}</label>
                        <textarea id="reason" name="reason" class="textarea" required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">{{ translate('Upgrade') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('admin.risk-management.components.weight-modal')
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('[data-modal-toggle="#score_modal"]');

            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('score_modal');
                    const form = modal.querySelector('form');

                    // Set form action
                    form.setAttribute('action', this.getAttribute('data-action'));

                    // Fill form fields
                    form.querySelector('#user_id').value = this.getAttribute('data-id');
                    form.querySelector('#risk_score').value = this.getAttribute('data-score') || '';
                    form.querySelector('#reason').value = this.getAttribute('data-reason') || '';
                });
            });
        });
    </script>
@endpush
