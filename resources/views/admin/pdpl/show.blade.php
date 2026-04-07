@extends('layouts.base')

@section('content')
    @php
        $statusVal = $dsr->status instanceof \BackedEnum ? $dsr->status->value : (string) $dsr->status;
        $typeVal = $dsr->request_type instanceof \BackedEnum ? $dsr->request_type->value : (string) $dsr->request_type;
        $isOverdue = $dsr->deadline_at?->isPast() && !in_array($statusVal, ['completed', 'rejected', 'cancelled']);
        $daysRemaining = $dsr->deadline_at ? (int) now()->diffInDays($dsr->deadline_at, false) : null;
        $isPending = in_array($statusVal, ['pending', 'under_review']);
        $isApproved = $statusVal === 'approved';
    @endphp

    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        {{-- Header --}}
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center justify-between gap-5">
                <div class="flex items-center gap-3">
                    <a href="{{ route('pdpl.requests.index') }}" class="btn btn-sm btn-icon btn-light">
                        <i class="ki-filled ki-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-medium text-gray-900">
                            Data Subject Request
                        </h1>
                        <span class="text-xs text-gray-400 font-mono">{{ $dsr->uuid }}</span>
                    </div>
                    <x-fintech.status-badge :status="$statusVal" size="base" />
                    @if($isOverdue)
                        <span class="badge badge-danger animate-pulse">OVERDUE</span>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 no-print">
                    @can('sensitive-data.approve')
                        @if($isPending)
                            <button class="btn btn-sm btn-success" data-modal-toggle="#approve_dsr_modal">
                                <i class="ki-filled ki-check"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger" data-modal-toggle="#reject_dsr_modal">
                                <i class="ki-filled ki-cross"></i> Reject
                            </button>
                        @endif
                        @if($isApproved)
                            <button class="btn btn-sm btn-primary" data-modal-toggle="#complete_dsr_modal">
                                <i class="ki-filled ki-verify"></i> Mark Completed
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        {{-- Overdue alert --}}
        @if($isOverdue)
            <div class="container-fixed mb-4">
                <x-fintech.alert-banner type="danger" :dismissible="false" icon="ki-shield-cross">
                    This request is <strong>{{ abs($daysRemaining) }} days past</strong> the PDPL 30-day response deadline.
                    Regulatory penalties may apply. Take action immediately.
                </x-fintech.alert-banner>
            </div>
        @endif

        {{-- Content grid --}}
        <div class="container-fixed">
            <div class="grid lg:grid-cols-3 gap-5 mb-5">

                {{-- Request Details --}}
                <div class="card lg:col-span-2">
                    <div class="card-header">
                        <h3 class="card-title">Request Details</h3>
                    </div>
                    <div class="card-body flex flex-col gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-xs text-gray-400 uppercase">Request Type</span>
                                <p class="font-medium text-gray-900 mt-0.5">
                                    <span class="badge badge-outline badge-info">
                                        <i class="ki-filled ki-file-sheet"></i> {{ ucfirst($typeVal) }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 uppercase">Status</span>
                                <p class="mt-0.5">
                                    <x-fintech.status-badge :status="$statusVal" />
                                </p>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs text-gray-400 uppercase">Description</span>
                            <p class="text-sm text-gray-800 mt-1 p-3 bg-gray-50 rounded-lg">
                                {{ $dsr->description }}
                            </p>
                        </div>

                        @if($dsr->affected_data)
                            <div>
                                <span class="text-xs text-gray-400 uppercase">Affected Data Categories</span>
                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    @foreach($dsr->affected_data as $category)
                                        <span class="badge badge-sm badge-light">{{ $category }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($dsr->admin_notes)
                            <div>
                                <span class="text-xs text-gray-400 uppercase">Admin Notes</span>
                                <p class="text-sm text-gray-700 mt-1 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                    {{ $dsr->admin_notes }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="flex flex-col gap-5">
                    {{-- SLA Card --}}
                    <div class="card {{ $isOverdue ? 'border-2 border-red-300' : '' }}">
                        <div class="card-header">
                            <h3 class="card-title text-sm">SLA Countdown</h3>
                        </div>
                        <div class="card-body text-center py-5">
                            @if(!in_array($statusVal, ['completed', 'rejected', 'cancelled']))
                                @if($daysRemaining !== null && $daysRemaining < 0)
                                    <div class="text-4xl font-bold text-red-600 tabular-nums">{{ abs($daysRemaining) }}</div>
                                    <div class="text-sm text-red-500 font-medium mt-1">days overdue</div>
                                @elseif($daysRemaining !== null)
                                    <div class="text-4xl font-bold {{ $daysRemaining <= 5 ? 'text-amber-600' : 'text-gray-900' }} tabular-nums">{{ $daysRemaining }}</div>
                                    <div class="text-sm text-gray-500 mt-1">days remaining</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-2">
                                    Deadline: {{ $dsr->deadline_at?->format('M d, Y') }}
                                </div>
                            @else
                                <div class="text-lg font-medium text-gray-400">Closed</div>
                                @if($dsr->completed_at)
                                    <div class="text-xs text-gray-400 mt-1">
                                        Completed: {{ $dsr->completed_at->format('M d, Y') }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- People --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-sm">People</h3>
                        </div>
                        <div class="card-body flex flex-col gap-3">
                            <div>
                                <span class="text-2xs text-gray-400 uppercase">Data Subject</span>
                                <p class="text-sm font-medium">{{ $dsr->user?->first_name }} {{ $dsr->user?->last_name }}</p>
                                <p class="text-xs text-gray-400">ID: {{ $dsr->user_id }}</p>
                            </div>
                            <div class="separator"></div>
                            <div>
                                <span class="text-2xs text-gray-400 uppercase">Requested By</span>
                                <p class="text-sm font-medium">{{ $dsr->requester?->first_name }} {{ $dsr->requester?->last_name }}</p>
                                <p class="text-xs text-gray-400">{{ $dsr->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            @if($dsr->reviewer)
                                <div class="separator"></div>
                                <div>
                                    <span class="text-2xs text-gray-400 uppercase">Reviewed By</span>
                                    <p class="text-sm font-medium">{{ $dsr->reviewer->first_name }} {{ $dsr->reviewer->last_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $dsr->reviewed_at?->format('M d, Y H:i') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Approve Modal --}}
    @can('sensitive-data.approve')
    <x-fintech.approval-modal
        id="approve_dsr_modal"
        title="Approve Data Subject Request"
        :action="route('pdpl.requests.approve', $dsr)"
        :entityLabel="ucfirst($typeVal) . ' request for ' . ($dsr->user?->first_name ?? '') . ' ' . ($dsr->user?->last_name ?? '')"
        confirmText="Approve Request"
        confirmClass="btn-success"
    >
        <div class="mb-3">
            <label class="form-label text-sm">Notes (optional)</label>
            <textarea name="admin_notes" rows="2" class="input w-full" placeholder="Add any notes..."></textarea>
        </div>
    </x-fintech.approval-modal>

    {{-- Reject Modal --}}
    <x-fintech.approval-modal
        id="reject_dsr_modal"
        title="Reject Data Subject Request"
        :action="route('pdpl.requests.reject', $dsr)"
        :entityLabel="ucfirst($typeVal) . ' request for ' . ($dsr->user?->first_name ?? '') . ' ' . ($dsr->user?->last_name ?? '')"
        confirmText="Reject Request"
        confirmClass="btn-danger"
        :requireReason="false"
    >
        <div class="mb-3">
            <label class="form-label text-sm">Rejection Reason <span class="text-red-500">*</span></label>
            <textarea name="admin_notes" rows="2" class="input w-full" required placeholder="Why is this request being rejected?"></textarea>
        </div>
    </x-fintech.approval-modal>

    {{-- Complete Modal --}}
    <x-fintech.approval-modal
        id="complete_dsr_modal"
        title="Mark Request as Completed"
        :action="route('pdpl.requests.complete', $dsr)"
        :entityLabel="ucfirst($typeVal) . ' request — describe what was done'"
        confirmText="Mark Completed"
        confirmClass="btn-primary"
    >
        <div class="mb-3">
            <label class="form-label text-sm">Completion Notes</label>
            <textarea name="admin_notes" rows="2" class="input w-full" placeholder="What action was taken to fulfill this request?"></textarea>
        </div>
    </x-fintech.approval-modal>
    @endcan
@endsection
