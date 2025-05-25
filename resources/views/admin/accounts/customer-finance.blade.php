@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    
    <div class="container-fixed" id="content_container"></div>
    <div class="bg-center bg-cover bg-no-repeat hero-bg">

        @include('admin.accounts.includes.customer')

    </div>
    
        @include('admin.accounts.includes.customer-header')
        
    <div class="container-fixed">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
            <div class="col-span-1 lg:col-span-2">
                <div class="card bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white shadow-lg rounded-2xl">
                    <div class="card-body p-6">
                        <h3 class="text-lg font-semibold mb-4">Finance Overview</h3>
        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                            <div class="flex flex-col">
                                <span class="text-sm opacity-80">Total Limit</span>
                                <span class="text-xl font-bold">
                                    <span class="icon-saudi_riyal"></span> {{ number_format($creditLimit->limit_arabianpay_after - $totalOrderAmount, 2) }}
                                </span>
                            </div>
        
                            <div class="flex flex-col">
                                <span class="text-sm opacity-80">Simah Limit</span>
                                <span class="text-xl font-bold"><span class="icon-saudi_riyal"></span> 600</span>
                            </div>
        
                            <div class="flex flex-col">
                                <span class="text-sm opacity-80">Used Limit</span>
                                <span class="text-xl font-bold"><span class="icon-saudi_riyal"></span> {{ number_format($totalOrderAmount, 2) }}</span>
                            </div>
        
                            <div class="flex flex-col">
                                <span class="text-sm opacity-80">User Package</span>
                                <span class="text-xl font-bold">{{ $customer->package->name ?? 'N/A' }}</span>
                            </div>
        
                            <div class="flex flex-col items-start gap-2">
                                <span class="text-sm opacity-80">Package Upgrade</span>
                                
                                <a class="btn btn-sm btn-primary w-auto" data-modal-toggle="#package_modal">Upgrade Now</a>
                            </div>                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-1 ">
                <div class="card bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 text-white shadow-lg rounded-2xl">
                    <div class="card-body p-6">
                        <h3 class="text-lg font-semibold mb-4">Financial Statistics</h3>
        
                        <div class="flex flex-col gap-5">
                            <div class="w-full">
                                <span class="text-sm opacity-80">Total Payment Due Amount</span>
                                <span class="text-xl font-bold">
                                    <span class="icon-saudi_riyal"></span> {{ number_format($totalPaymentDue, 2) }}
                                </span>
                            </div>
                        
                            <div class="w-full">
                                <span class="text-sm opacity-80">Total Payment Due</span>
                                <span class="text-xl font-bold">{{ number_format($lateCount) }}</span>
                            </div>
                        
                            <div class="w-full">
                                <span class="text-sm opacity-80">Total Late Payments</span>
                                <span class="text-xl font-bold">{{ number_format($lateCount) }}</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-1 lg:col-span-3 mt-4">
            <div class="card">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        Log Changes To Limit the Customer
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#team_crew_table" placeholder="Search users" type="text" value="" />
                            </label>
                        </div>
                        <a class="btn btn-sm btn-light" data-modal-toggle="#limit_create_modal">
                            Create New Limit
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            No
                                        </th>
                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Limit Arabinpay After
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Limit Arabinpay Befor
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort asc">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Limit Simah
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>

                                        <th class="">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Created At
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                        
                                        <th class="">
                                            <span class="sort">
                                                <span class="sort-label font-normal text-gray-700">
                                                    Action
                                                </span>
                                                <span class="sort-icon"> </span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($creditLimitLogs as $item)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            
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
                        @include('layouts.includes.table-pagination', ['paginator' => $creditLimitLogs])
                    </div>
                </div>
            </div>
        </div>
        
        <!-- end: grid -->
    </div>
    <!-- End of Container -->
</main>




<div class="modal" data-modal="true" id="package_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">User Package</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <form action="{{ route('updateCustomerPackage', $customer->user_id) }}" method="POST" class="px-5 pt-3">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Select Package</label>
                    <select name="package_id" class="input" required>
                        @foreach($packages as $package)
                            <option value="{{ $package->id }}" {{ $customer->package->id == $package->id ? 'selected' : '' }}>{{ $package->name }}</option>
                        @endforeach
                    </select>
                </div>
            
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Upgrade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal" data-modal="true" id="limit_update_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">User Credit Limit the Customer</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <form action="{{ route('customerUpgradeLimit') }}" method="POST" class="px-5 pt-3">
                @csrf
                <input type="hidden" id="credit_limit_id" name="credit_limit_id">

                <div class="mb-4">
                    <label class="form-label" for="limit_arabianpay_before">Limit Arabianpay Before</label>
                    <input type="text" id="limit_arabianpay_before" name="limit_arabianpay_before" class="input" value="{{ old('limit_arabianpay_before', $customer->limit_arabianpay_after ?? '') }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="limit_arabianpay_after">Limit Arabianpay After</label>
                    <input type="text" id="limit_arabianpay_after" name="limit_arabianpay_after" class="input" value="{{ old('limit_arabianpay_after', $customer->limit_arabianpay_after ?? '') }}" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Upgrade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal" data-modal="true" id="limit_create_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">User Credit Limit the Customer</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <form action="{{ route('createCreditLimit') }}" method="POST" class="px-5 pt-3">
                @csrf
                <input type="hidden" name="user_id" value="{{ $customer->user_id }}">
                <input type="hidden" name="package_id" value="{{ $customer->package_id }}">

                <div class="mb-4">
                    <label class="form-label">Limit Arabianpay Before</label>
                    <input type="text" name="limit_arabianpay_before" class="input" value="{{ old('limit_arabianpay_before', $customer->limit_arabianpay_after ?? '') }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Limit Arabianpay After</label>
                    <input type="text" name="limit_arabianpay_after" class="input" value="{{ old('limit_arabianpay_after', $customer->limit_arabianpay_after ?? '') }}" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('[data-modal-toggle="#limit_update_modal"]');

        buttons.forEach(button => {
            button.addEventListener('click', function () {
                const modal = document.getElementById('limit_update_modal');
                modal.querySelector('#limit_arabianpay_before').value = this.getAttribute('data-before');
                modal.querySelector('#limit_arabianpay_after').value = this.getAttribute('data-after');
                modal.querySelector('form').setAttribute('action', this.getAttribute('data-action'));
                modal.querySelector('#credit_limit_id').value = this.getAttribute('data-id');
            });
        });
    });
</script>
@endpush