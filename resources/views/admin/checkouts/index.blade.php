@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        Checkouts
                    </h1>
                    <span class="text-sm text-gray-500">Manage loan checkouts and investment pool assignments</span>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="container-fixed mb-5">
            <div class="card">
                <div class="card-body p-4">
                    <form method="get" action="{{ route('checkouts.index') }}" class="flex items-end gap-3 flex-wrap">
                        <div>
                            <label class="form-label text-xs">Search</label>
                            <input type="text" name="search" class="input input-sm w-48" placeholder="UUID or customer..." value="{{ request('search') }}">
                        </div>
                        <div>
                            <label class="form-label text-xs">Payment Status</label>
                            <select name="payment_status" class="select select-sm w-36">
                                <option value="">All</option>
                                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="overdue" {{ request('payment_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-primary"><i class="ki-filled ki-filter"></i> Filter</button>
                        <a href="{{ route('checkouts.index') }}" class="btn btn-sm btn-light"><i class="ki-filled ki-arrows-circle"></i> Reset</a>
                    </form>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="container-fixed">
            <div class="card card-grid min-w-full">
                <div class="card-header">
                    <h3 class="card-title text-sm font-medium">
                        All Checkouts
                        <span class="badge badge-sm badge-light ms-1">{{ $checkouts->total() }}</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th>UUID</th>
                                    <th>Customer</th>
                                    <th class="text-center">Investment Pool</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-center">Instalments</th>
                                    <th class="text-center">Created</th>
                                    <th class="text-center w-[80px]">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($checkouts as $checkout)
                                    <tr class="hover:bg-gray-50">
                                        <td>
                                            <a href="{{ route('checkouts.show', $checkout->id) }}" class="text-primary font-mono text-xs hover:underline">
                                                {{ \Illuminate\Support\Str::limit($checkout->uuid, 12, '...') }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($checkout->user)
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-gray-900 text-sm">{{ $checkout->user->first_name }} {{ $checkout->user->last_name }}</span>
                                                    <span class="text-xs text-gray-400">{{ $checkout->user->business_name ?? '' }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($checkout->investmentPool)
                                                <span class="badge badge-sm badge-outline badge-primary">
                                                    {{ $checkout->investmentPool->name ?? 'Pool #' . $checkout->pool_id }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-sm badge-light">{{ $checkout->orders->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $totalInstalments = $checkout->schedulePayments->count();
                                                $paidInstalments = $checkout->schedulePayments->filter(
                                                    fn($sp) => ($sp->payment_status instanceof \BackedEnum ? $sp->payment_status->value : $sp->payment_status) === 'paid'
                                                )->count();
                                            @endphp
                                            <span class="text-sm {{ $paidInstalments === $totalInstalments && $totalInstalments > 0 ? 'text-green-600' : 'text-gray-700' }}">
                                                {{ $paidInstalments }}/{{ $totalInstalments }}
                                            </span>
                                        </td>
                                        <td class="text-center text-xs text-gray-500">
                                            {{ $checkout->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('checkouts.show', $checkout->id) }}" class="btn btn-sm btn-icon btn-light" title="View">
                                                <i class="ki-filled ki-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-10">
                                            <div class="flex flex-col items-center gap-3">
                                                <i class="ki-filled ki-basket text-4xl text-gray-300"></i>
                                                <span class="text-gray-500">No checkouts found</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-4">
                        {{ $checkouts->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
