    @extends('layouts.base')

    @section('content')
        <main class="grow content pt-5" id="content" role="content">
            <div class="container-fixed">
                <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                    <div class="flex flex-col justify-center gap-2">
                        <h1 class="text-xl font-medium leading-none text-gray-900">
                            {{ __('Customers Credit Profile') }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="container-fixed">
                <div class="grid gap-5 lg:gap-7.5">
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ __('Credit Profiles') }}
                            </h3>
                            <div class="flex flex-wrap gap-2 lg:gap-5 items-center">
                                <div class="flex">
                                    <form method="GET" action="{{ route('creditProfile') }}" class="flex">
                                        <label class="input input-sm">
                                            <i class="ki-filled ki-magnifier"></i>
                                            <input name="search" type="text" placeholder="{{ __('Search by user') }}"
                                                value="{{ request('search') }}" />
                                        </label>
                                        <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                                            {{ __('Search') }}
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                        <div class="card-body">
                            <div data-datatable="true" data-datatable-state-save="false" id="refund_requests_table">
                                <div class="scrollable-x-auto">
                                    <table class="table table-auto table-border" data-datatable-table="true">
                                        <thead>
                                            <tr>
                                                <th class="w-[60px] text-center">ID</th>
                                                <th>User Name</th>
                                                <th>Total Credit Limit</th>
                                                <th>Credit Used</th>
                                                <th>Remaning Credit Limit</th>
                                                <th>Repayment History</th>
                                                <th>Credit Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($customers as $item)
                                                <tr>
                                                    <td class="text-center">{{ $item->id }}</td>
                                                    <td>
                                                        <div class="whitespace-nowrap">
                                                            {{ $item->first_name }} {{ $item->last_name }}
                                                            <br>
                                                            <small class="text-gray-500">
                                                                — {{ $item->business_name ?? '-' }}
                                                            </small>
                                                        </div>
                                                    </td>


                                                    <td>{{ number_format($item->creditLimit ?? 0, 2) }}</td>

                                                    <td>{{ number_format($item->total_used ?? 0, 2) }}</td>
                                                    <td>{{ number_format($item->limit_remaining ?? 0, 2) }}
                                                    </td>
                                                    <td>{{ number_format($item->repayment_history ?? 0, 2) }}</td>

                                                    {{-- Static repayment history --}}
                                                    <td>
                                                        @php
                                                            $score = $item->credit_score ?? 0;

                                                            if ($score >= 80) {
                                                                $creditLevel = 'Excellent';
                                                                $badgeColor =
                                                                    'badge badge-sm badge-outline badge-success';
                                                            } elseif ($score >= 60) {
                                                                $creditLevel = 'Fair';
                                                                $badgeColor =
                                                                    'badge badge-sm badge-outline badge-warning';
                                                            } else {
                                                                $creditLevel = 'Poor';
                                                                $badgeColor =
                                                                    'badge badge-sm badge-outline badge-danger';
                                                            }
                                                        @endphp

                                                        <span class="{{ $badgeColor }}">
                                                            {{ $score }}/100 - {{ $creditLevel }}
                                                        </span>
                                                    </td>

                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination Footer -->
                                @include('layouts.includes.table-pagination', ['paginator' => $customers])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    @endsection
