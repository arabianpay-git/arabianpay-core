@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .risk-tab-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100px;
            }

            .risk-tab-buttons {
                display: flex;
                gap: 10px;
            }

            .risk-tab-buttons a {
                padding: 8px 20px;
                border-radius: 8px;
                font-weight: 600;
                text-decoration: none;
                color: #4B5563;
                background-color: #F3F4F6;
                transition: all 0.2s ease;
                box-shadow: none;
            }

            .risk-tab-buttons a:hover {
                background-color: #E5E7EB;
                color: #1F2937;
            }

            .risk-tab-buttons a.active {
                background-color: #3B82F6;
                color: #FFFFFF;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }
        </style>
    @endpush
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex justify-between gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Risk Score Engine') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">

                    <!-- Tabs for Merchant / Supplier -->
                    <div class="card-header flex-wrap gap-2">
                        <ul class="risk-tab-buttons">
                            <li>
                                <a href="{{ route('risk.score', array_merge(request()->query(), ['type' => 'merchant'])) }}"
                                    class="{{ request('type') === 'merchant' ? 'active' : '' }}">
                                    {{ translate('Supplier') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('risk.score', array_merge(request()->query(), ['type' => 'user'])) }}"
                                    class="{{ request('type') === 'user' || !request('type') ? 'active' : '' }}">
                                    {{ translate('Merchant') }}
                                </a>
                            </li>
                        </ul>

                        <div class="flex flex-wrap gap-2 lg:gap-5 items-center mt-3">
                            <div class="flex">
                                <form method="GET" action="{{ route('risk.score') }}" class="flex">
                                    <!-- Preserve type parameter in search -->
                                    <input type="hidden" name="type" value="{{ request('type') }}">
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
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="refund_requests_table">
                            @include('admin.risk-management.components.score-table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
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
