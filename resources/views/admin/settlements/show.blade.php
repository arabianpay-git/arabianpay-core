@extends('layouts.base')
@push('styles')
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
@endpush
@section('content')
    @php
        $settlementStatusValue = $settlement->status instanceof \BackedEnum
            ? $settlement->status->value
            : (string) $settlement->status;
    @endphp
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center justify-between gap-5">
                <div class="flex flex-col justify-center gap-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('settlements.index') }}" class="btn btn-sm btn-icon btn-light">
                            <i class="ki-filled ki-arrow-left"></i>
                        </a>
                        <h1 class="text-xl font-medium leading-none text-gray-900">
                            Settlement {{ $settlement->settlement_number }}
                        </h1>
                        @php
                            $textClass = match ($settlementStatusValue) {
                                'draft' => 'text-light',
                                'pending' => 'text-warning',
                                'pending_approval' => 'text-warning',
                                'approved' => 'text-primary',
                                'paid' => 'text-success',
                                'cancelled' => 'text-danger',
                                default => 'text-secondary'
                            };
                        @endphp
                        <span class="text-xs font-medium {{ $textClass }}">
                            {{ ucfirst(str_replace('_', ' ', $settlementStatusValue)) }}
                        </span>
                    </div>
                   

                    <!-- Status Timeline -->
                    <div class="flex items-center gap-2 ml-10 no-print">
                        @php
                            $steps = [
                                ['name' => 'Draft', 'icon' => 'ki-note-2', 'status' => 'draft'],
                                ['name' => 'Approved', 'icon' => 'ki-check-circle', 'status' => 'approved'],
                                ['name' => 'Paid', 'icon' => 'ki-verify', 'status' => 'paid'],
                            ];
                            $currentIndex = match ($settlementStatusValue) {
                                'draft' => 0,
                                'pending' => 0,
                                'pending_approval' => 0,
                                'approved' => 1,
                                'paid' => 2,
                                default => -1,
                            };
                        @endphp

                        @foreach($steps as $index => $step)
                            <div class="flex items-center gap-2">
                                <div class="flex badge items-center gap-1 px-2 py-1 rounded 
                                    {{ $index <= $currentIndex ? 'badge-success' : 'badge-gray-100' }}">
                                    <i class="ki-filled {{ $step['icon'] }} text-xs"></i>
                                    <span class="text-xs font-medium">{{ $step['name'] }}</span>
                                </div>
                                @if($index < count($steps) - 1)
                                    <i class="ki-filled ki-right text-xs {{ $index < $currentIndex ? 'text-white' : 'text-gray-300' }}"></i>
                                @endif
                            </div>
                        @endforeach

                        @if($settlementStatusValue === 'cancelled')
                            <div class="flex items-center gap-1 px-2 py-1 rounded bg-danger text-white">
                                <i class="ki-filled ki-cross-circle text-xs"></i>
                                <span class="text-xs font-medium">Cancelled</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2.5 flex-wrap no-print">
                    <!-- Export Actions -->
                    <button class="btn btn-light btn-sm" onclick="window.print()">
                       <i class="ki-filled ki-printer"></i> Print
                    </button>
                  

                    <!-- Workflow Actions -->
                    @if($settlementStatusValue === 'draft' || $settlementStatusValue === 'pending' || $settlementStatusValue === 'pending_approval')
                        @can('settlement.approve')
                        <form action="{{ route('settlements.approve', $settlement->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="ki-filled ki-check"></i> Approve
                            </button>
                        </form>
                        @endcan
                        @can('settlement.cancel')
                        <button class="btn btn-danger btn-sm" data-modal-toggle="#cancel_settlement_modal">
                            <i class="ki-filled ki-cross"></i> Cancel
                        </button>
                        @endcan
                    @endif

                    @can('settlement.pay')
                    @if($settlementStatusValue === 'approved')
                        <form action="{{ route('settlements.pay', $settlement->id) }}" method="POST" >
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ki-filled ki-dollar"></i> Mark as Paid
                            </button>
                        </form>
                    @endif
                    @endcan
                </div>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="container-fixed">
            <div class="grid grid-cols-3 lg:grid-cols-4 gap-5 mb-5">
                <!-- Supplier Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Supplier Details</h3>
                    </div>
                    <div class="card-body flex flex-col gap-1">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-500">ID</span>
                            <span class="font-medium text-gray-900">#{{ $settlement->supplier_user_id }}</span>
                        </div>
                        <div class="separator"></div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-500 text-pri">Name</span>
                            <span class="font-medium text-gray-900">{{ $settlement->supplier->first_name }} {{ $settlement->supplier->last_name }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-500">Business</span>
                            <span class="font-medium text-gray-900">{{ $settlement->supplier->business_name ?? '-' }}</span>
                        </div>
                        
                         @php
                            $bank = $settlement->supplier->supplierBanks->first() ?? null;
                        @endphp
                        @if($bank)
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-500">Bank Name</span>
                                <span class="font-medium text-gray-900">{{ $bank->bank_name }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-500">Account Name</span>
                                <span class="font-medium text-gray-900">{{ $bank->account_name }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-500">IBAN</span>
                                <span class="font-mono text-xs break-all">{{ $bank->iban }}</span>
                            </div>
                        @else
                            <div class="alert alert-warning text-sm">
                                <i class="ki-filled ki-information-2"></i>
                                No bank details on file
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Financial Summary</h3>
                    </div>
                    <div class="card-body flex flex-col gap-3">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-500">Orders Count</span>
                            <span class="text-2xl font-bold text-primary">{{ $settlement->orders_count }}</span>
                        </div>
                        <div class="separator"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-600">Total Orders</span>
                            <x-fintech.money :amount="$settlement->total_amount" size="base" />
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-600">ArabianPay Fees</span>
                            <x-fintech.money :amount="'-' . $settlement->commission_amount" size="base" color="red" />
                        </div>
                        <div class="separator my-1"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-800 font-bold">Payable</span>
                            <x-fintech.money :amount="$settlement->payable_amount" size="lg" color="green" />
                        </div>
                    </div>
                </div>

                <!-- Timeline Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Timeline</h3>
                    </div>
                    <div class="card-body flex flex-col gap-1 text-sm">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-500">Settlement Period</span>
                            <span class="font-medium">{{ $settlement->start_date->format('M d, Y') }} - {{ $settlement->end_date->format('M d, Y') }}</span>
                        </div>
                        <div class="separator"></div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-500">Created at</span>
                            <span class="font-medium">{{ $settlement->created_at->format('M d, Y H:i') }}</span>
                            <span class="text-xs text-gray-400 text-primary">by {{ $settlement->creator->first_name ?? 'System' }}</span>
                        </div>
                        @if($settlement->approved_at)
                            <div class="separator"></div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-500">Approved at</span>
                                <span class="font-medium">{{ $settlement->approved_at->format('M d, Y H:i') }}</span>
                                <span class="text-xs text-gray-400 text-primary">by {{ $settlement->approver->first_name ?? '-' }}</span>
                            </div>
                        @endif
                        @if($settlement->paid_at)
                            <div class="separator"></div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-gray-500">Paid at</span>
                                <span class="font-medium">{{ $settlement->paid_at->format('M d, Y H:i') }}</span>
                                <span class="text-xs text-gray-400 text-primary">by {{ $settlement->payer->first_name ?? '-' }}</span>
                            </div>
                        @endif
                        
                    </div>
                </div>

               
            </div>

            {{-- [UI-PHASE] Maker-checker governance info --}}
            <div class="mb-5">
                <x-fintech.maker-checker-info
                    :createdBy="$settlement->creator"
                    :createdAt="$settlement->created_at"
                    :approvedBy="$settlement->approver"
                    :approvedAt="$settlement->approved_at"
                    :paidBy="$settlement->payer"
                    :paidAt="$settlement->paid_at"
                />
            </div>

            <!-- Payout Confirmation Section -->
            @if($settlementStatusValue === 'paid' && $settlement->payouts->isNotEmpty())
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Payout Confirmation</h3>
                    <span class="badge badge-success">{{ $settlement->payouts->count() }} Payout(s)</span>
                </div>
                <div class="card-body">
                    <div class="flex flex-col gap-2">
                        @foreach($settlement->payouts as $payout)
                        <div class="flex justify-between items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex flex-col">
                                <span class="font-medium text-gray-900">Payout #{{ $payout->id }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ $payout->created_at->format('M d, Y H:i') }}
                                </span>
                            </div>
                            <span class="badge badge-lg badge-success">
                                <i class="ki-filled ki-verify"></i>
                                {{ number_format($payout->amount, 2) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Included Orders ({{ $settlement->orders_count }})</h3>
                </div>
                <div class="card-body p-0">
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="w-[250px] text-center">Order #</th>
                                    <th>Placed Date</th>
                                    <th>Delivered Date</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">ArabianPay Fees</th>
                                    <th class="text-right">Payable Amount</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($settlement->orders as $order)
                                    <tr>
                                        <td class="text-center font-medium ">
                                            <a href="#" class="text-primary hover:underline">#{{ $order->reference_id }}</a>
                                        </td>
                                        <td>{{ $order->created_at->format('M d, Y') }}</td>
                                        <td>{{ $order->delivered_at}}</td>
                                        <td class="text-right">{{ number_format($order->grand_total, 2) }}</td>
                                        <td class="text-right text-info">{{ number_format($order->commission_amount ?? 0, 2) }}</td>
                                        <td class="text-right font-semibold">{{ number_format($order->grand_total - ($order->commission_amount ?? 0), 2) }}</td>
                                        
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-8 text-gray-500">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <i class="ki-filled ki-basket text-3xl text-gray-300"></i>
                                                <span>No orders in this settlement</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($settlement->orders->isNotEmpty())
                            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                                <tr class="font-bold">
                                    <th colspan="2" class="text-right">Totals:</th>
                                    <th class="text-right">{{ number_format($settlement->total_amount, 2) }}</th>
                                    <th class="text-right text-danger">{{ number_format($settlement->commission_amount, 2) }}</th>
                                    <th class="text-right text-success">{{ number_format($settlement->payable_amount, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
    
    {{-- [UI-PHASE] Cancel modal using fintech approval-modal component --}}
    <x-fintech.approval-modal
        id="cancel_settlement_modal"
        title="Cancel Settlement"
        :action="route('settlements.cancel', $settlement->id)"
        :entityLabel="'Settlement ' . $settlement->settlement_number"
        :amount="$settlement->payable_amount"
        confirmText="Confirm Cancellation"
        confirmClass="btn-danger"
        :requireReason="true"
    >
        <x-fintech.alert-banner type="warning" :dismissible="false">
            Cancelling will release all orders back to the unsettled pool. This action is logged.
        </x-fintech.alert-banner>
    </x-fintech.approval-modal>
@endsection
