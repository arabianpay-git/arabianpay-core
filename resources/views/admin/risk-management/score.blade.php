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
                            @include('admin.risk-management.components.score-table')
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
