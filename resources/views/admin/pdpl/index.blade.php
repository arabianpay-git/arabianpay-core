@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        {{-- Header --}}
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        <i class="ki-filled ki-shield-tick text-primary"></i>
                        PDPL Data Subject Requests
                    </h1>
                    <span class="text-sm text-gray-500">
                        Manage data subject rights requests under Saudi PDPL (30-day response deadline)
                    </span>
                </div>
                @can('sensitive-data.approve')
                    <button class="btn btn-sm btn-primary" data-modal-toggle="#create_dsr_modal">
                        <i class="ki-filled ki-plus-square"></i> New Request
                    </button>
                @endcan
            </div>
        </div>

        {{-- Alert banners --}}
        <div class="container-fixed">
            @if($overdueCount > 0)
                <x-fintech.alert-banner type="danger" :count="$overdueCount" class="mb-4" icon="ki-shield-cross">
                    data subject request{{ $overdueCount > 1 ? 's' : '' }} past the PDPL 30-day response deadline. Immediate action required.
                </x-fintech.alert-banner>
            @endif
        </div>

        {{-- KPI Cards --}}
        <div class="container-fixed mb-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-fintech.kpi-card
                    title="Pending Requests"
                    :value="$pendingCount"
                    icon="ki-time"
                    :color="$pendingCount > 0 ? 'warning' : 'success'"
                    subtitle="Awaiting review"
                />
                <x-fintech.kpi-card
                    title="Overdue"
                    :value="$overdueCount"
                    icon="ki-shield-cross"
                    :color="$overdueCount > 0 ? 'danger' : 'success'"
                    subtitle="Past 30-day deadline"
                />
                <x-fintech.kpi-card
                    title="Total Requests"
                    :value="$requests->total()"
                    icon="ki-file-sheet"
                    color="info"
                />
                <x-fintech.kpi-card
                    title="SLA Target"
                    value="30 days"
                    icon="ki-calendar"
                    color="primary"
                    subtitle="PDPL response requirement"
                />
            </div>
        </div>

        {{-- Filters --}}
        <div class="container-fixed mb-5">
            <div class="card">
                <div class="card-body p-4">
                    <form method="get" action="{{ route('pdpl.requests.index') }}" class="flex items-end gap-3 flex-wrap">
                        <div>
                            <label class="form-label text-xs">Status</label>
                            <select name="status" class="select select-sm w-40">
                                <option value="">All</option>
                                @foreach(\App\Enums\DataRequestStatus::cases() as $s)
                                    <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $s->value)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label text-xs">Type</label>
                            <select name="type" class="select select-sm w-40">
                                <option value="">All</option>
                                @foreach(\App\Enums\DataRequestType::cases() as $t)
                                    <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>
                                        {{ ucfirst($t->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="overdue" value="1" class="checkbox checkbox-sm" {{ request('overdue') ? 'checked' : '' }}>
                                <span class="text-xs text-red-600 font-medium">Overdue only</span>
                            </label>
                        </div>
                        <button class="btn btn-sm btn-primary"><i class="ki-filled ki-filter"></i> Filter</button>
                        <a href="{{ route('pdpl.requests.index') }}" class="btn btn-sm btn-light"><i class="ki-filled ki-arrows-circle"></i> Reset</a>
                    </form>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="container-fixed">
            <div class="card card-grid min-w-full">
                <div class="card-header">
                    <h3 class="card-title text-sm font-medium">Requests</h3>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="w-[100px]">ID</th>
                                    <th>Data Subject</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Deadline</th>
                                    <th class="text-center">SLA</th>
                                    <th class="text-center">Created</th>
                                    <th class="text-center w-[100px]">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $dsr)
                                    @php
                                        $statusVal = $dsr->status instanceof \BackedEnum ? $dsr->status->value : (string) $dsr->status;
                                        $typeVal = $dsr->request_type instanceof \BackedEnum ? $dsr->request_type->value : (string) $dsr->request_type;
                                        $isOverdue = $dsr->deadline_at && $dsr->deadline_at->isPast()
                                            && !in_array($statusVal, ['completed', 'rejected', 'cancelled']);
                                        $daysRemaining = $dsr->deadline_at ? (int) now()->diffInDays($dsr->deadline_at, false) : null;
                                    @endphp
                                    <tr class="{{ $isOverdue ? 'bg-red-50' : '' }} hover:bg-gray-50">
                                        <td>
                                            <a href="{{ route('pdpl.requests.show', $dsr) }}" class="text-primary font-medium hover:underline text-xs font-mono">
                                                {{ \Illuminate\Support\Str::limit($dsr->uuid, 8, '') }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-medium text-gray-900 text-sm">
                                                    {{ $dsr->user?->first_name }} {{ $dsr->user?->last_name }}
                                                </span>
                                                <span class="text-xs text-gray-400">{{ $dsr->user?->email }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm badge-outline badge-info">
                                                <i class="ki-filled ki-file-sheet"></i>
                                                {{ ucfirst($typeVal) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <x-fintech.status-badge :status="$statusVal" />
                                        </td>
                                        <td class="text-center text-xs">
                                            {{ $dsr->deadline_at?->format('M d, Y') }}
                                        </td>
                                        <td class="text-center">
                                            @if($daysRemaining !== null && !in_array($statusVal, ['completed', 'rejected', 'cancelled']))
                                                @if($daysRemaining < 0)
                                                    <span class="badge badge-sm badge-danger animate-pulse">
                                                        {{ abs($daysRemaining) }}d overdue
                                                    </span>
                                                @elseif($daysRemaining <= 5)
                                                    <span class="badge badge-sm badge-warning">
                                                        {{ $daysRemaining }}d left
                                                    </span>
                                                @else
                                                    <span class="badge badge-sm badge-light">
                                                        {{ $daysRemaining }}d left
                                                    </span>
                                                @endif
                                            @elseif(in_array($statusVal, ['completed', 'rejected', 'cancelled']))
                                                <span class="text-xs text-gray-400">Closed</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-xs text-gray-500">
                                            {{ $dsr->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('pdpl.requests.show', $dsr) }}" class="btn btn-sm btn-icon btn-light" title="View">
                                                <i class="ki-filled ki-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-10">
                                            <div class="flex flex-col items-center gap-3">
                                                <i class="ki-filled ki-shield-tick text-4xl text-gray-300"></i>
                                                <span class="text-gray-500">No data subject requests found</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4">
                        {{ $requests->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Create DSR Modal --}}
    @can('sensitive-data.approve')
    <x-fintech.approval-modal
        id="create_dsr_modal"
        title="Create Data Subject Request"
        :action="route('pdpl.requests.store')"
        confirmText="Submit Request"
        confirmClass="btn-primary"
    >
        <div class="flex flex-col gap-3">
            <div>
                <label class="form-label text-sm">User ID <span class="text-red-500">*</span></label>
                <input type="number" name="user_id" class="input w-full" required placeholder="Enter user ID">
            </div>
            <div>
                <label class="form-label text-sm">Request Type <span class="text-red-500">*</span></label>
                <select name="request_type" class="select w-full" required>
                    @foreach(\App\Enums\DataRequestType::cases() as $t)
                        <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label text-sm">Description <span class="text-red-500">*</span></label>
                <textarea name="description" rows="3" class="input w-full" required placeholder="Describe the data subject's request..."></textarea>
            </div>
        </div>
    </x-fintech.approval-modal>
    @endcan
@endsection
