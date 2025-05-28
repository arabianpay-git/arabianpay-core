    @extends('layouts.base')

    @section('content')
        <main class="grow content pt-5" id="content" role="content">
            <div class="container-fixed">
                <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                    <div class="flex flex-col justify-center gap-2">
                        <h1 class="text-xl font-medium leading-none text-gray-900">
                            {{ __('Customers Credit Limit') }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="container-fixed">
                <div class="grid gap-5 lg:gap-7.5">
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ __('Credit Limits') }}
                            </h3>
                            <div class="flex flex-wrap gap-2 lg:gap-5 items-center">
                                <div class="flex">
                                    <form method="GET" action="{{ route('creditLimit') }}" class="flex">
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
                                                <th>Limit Arabinpay After</th>
                                                <th>Limit Arabinpay Befor</th>
                                                <th>Simah Limit</th>
                                                <th>Created At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($creditLimits as $item)
                                                <tr>
                                                    <td class="text-center">{{ $loop->iteration }}</td>
                                                    <td>
                                                        <div class="whitespace-nowrap">
                                                            {{ $item->user->first_name }} {{ $item->user->last_name }}
                                                            <br>
                                                            <small class="text-gray-500">
                                                                — {{ $item->user->business_name ?? '-' }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>{{ number_format($item->limit_arabianpay_after, 2) ?? '—' }}</td>
                                                    <td>{{ number_format($item->limit_arabianpay_before, 2) ?? '—' }}</td>
                                                    <td>{{ number_format($item->simah_limit, 2) ?? '—' }}</td>

                                                    <td>{{ $item->created_at->format('d M Y') }}</td>

                                                    <td>
                                                        <div class="flex gap-1">
                                                            <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                                data-modal-toggle="#limit_update_modal"
                                                                data-id="{{ $item->id }}"
                                                                data-before="{{ $item->limit_arabianpay_before }}"
                                                                data-after="{{ $item->limit_arabianpay_after }}"
                                                                data-action="{{ route('customerUpgradeLimit') }}">
                                                                <i class="ki-filled ki-notepad-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach

                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination Footer -->
                                @include('layouts.includes.table-pagination', [
                                    'paginator' => $creditLimits,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <div class="modal" data-modal="true" id="limit_update_modal">
            <div class="modal-content max-w-[600px] top-[5%]">
                <div class="modal-header py-4 px-5">
                    <h5 class="modal-title">User Credit Limit the Customer</h5>
                    <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                        data-modal-dismiss="true">
                        <i class="ki-filled ki-cross"></i>
                    </button>
                </div>
                <div class="modal-body p-0 pb-5">
                    <form action="{{ route('customerUpgradeLimit') }}" method="POST" class="px-5 pt-3">
                        @csrf
                        <input type="hidden" id="credit_limit_id" name="credit_limit_id">

                        <div class="mb-4">
                            <label class="form-label" for="limit_arabianpay_before">Limit Arabianpay Before</label>
                            <input type="text" id="limit_arabianpay_before" name="limit_arabianpay_before" class="input"
                                value="{{ old('limit_arabianpay_before', $customer->limit_arabianpay_after ?? '') }}"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="limit_arabianpay_after">Limit Arabianpay After</label>
                            <input type="text" id="limit_arabianpay_after" name="limit_arabianpay_after" class="input"
                                value="{{ old('limit_arabianpay_after', $customer->limit_arabianpay_after ?? '') }}"
                                required>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">Upgrade</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const buttons = document.querySelectorAll('[data-modal-toggle="#limit_update_modal"]');

                buttons.forEach(button => {
                    button.addEventListener('click', function() {
                        const modal = document.getElementById('limit_update_modal');
                        modal.querySelector('#limit_arabianpay_before').value = this.getAttribute(
                            'data-before');
                        modal.querySelector('#limit_arabianpay_after').value = this.getAttribute(
                            'data-after');
                        modal.querySelector('form').setAttribute('action', this.getAttribute(
                            'data-action'));
                        modal.querySelector('#credit_limit_id').value = this.getAttribute('data-id');
                    });
                });
            });
        </script>
    @endpush
