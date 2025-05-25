@extends('layouts.base')

@section('content')

@push('styles')
<style>
    .tooltip-container {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }
    
    .tooltip-container .tooltip-text {
        visibility: hidden;
        opacity: 0;
        width: max-content;
        max-width: max-content;
        background-color: #000;
        color: #fff;
        text-align: center;
        border-radius: 0.375rem; /* rounded-md */
        padding: 4px 8px;
        position: absolute;
        bottom: 125%; /* above the icon */
        left: 50%;
        transform: translateX(-50%);
        transition: opacity 0.2s ease-in-out;
        z-index: 50;
        white-space: nowrap;
    }
    
    .tooltip-container:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
    .tooltip-icon{
        padding: 5px
    }
</style>
@endpush
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>
    <!-- End of Container -->
    <!-- Container -->
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Detailed Purchases
                </h1>
            </div>
        </div>
    </div>
    <!-- End of Container -->
    <!-- Container -->
    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">
                        Detailed Purchases
                    </h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input data-datatable-search="#team_crew_table" placeholder="Search users" type="text" value="" />
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th class="w-[60px] text-center">No</th>
                                        <th class="text-left">Order Code</th>
                                        <th class="text-center">Order Date</th>
                                        <th class="text-center">
                                            <div class="flex flex-col items-center space-y-1">
                                                <div class="font-normal text-gray-700 flex items-center gap-1">
                                                    Total Amount
                                        
                                                    <!-- Tooltip Trigger -->
                                                    <div class="tooltip-container">
                                                        <span class="tooltip-icon inline-block w-4 h-4 rounded-full bg-gray-300 text-xs text-gray-800 flex items-center justify-center">?</span>
                                        
                                                        <!-- Tooltip Text -->
                                                        <div class="tooltip-text">
                                                            Includes tax calculated per product
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </th>
                                        
                                        <th class="text-center">Total Invoice Without Tax</th>
                                        <th class="text-center">
                                            <div class="flex flex-col items-center space-y-1">
                                                <div class="font-normal text-gray-700 flex items-center gap-1">
                                                    System Commission
                                        
                                                    <!-- Tooltip Trigger -->
                                                    <div class="tooltip-container">
                                                        <span class="tooltip-icon inline-block w-4 h-4 rounded-full bg-gray-300 text-xs text-gray-800 flex items-center justify-center">?</span>
                                        
                                                        <!-- Tooltip Text -->
                                                        <div class="tooltip-text">
                                                            Value representing the commission amount calculated as a percentage of the grand total.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </th>

                                        <th class="text-center">
                                            <div class="flex flex-col items-center space-y-1">
                                                <div class="font-normal text-gray-700 flex items-center gap-1">
                                                    Commission Tax
                                        
                                                    <!-- Tooltip Trigger -->
                                                    <div class="tooltip-container">
                                                        <span class="tooltip-icon inline-block w-4 h-4 rounded-full bg-gray-300 text-xs text-gray-800 flex items-center justify-center">?</span>
                                        
                                                        <!-- Tooltip Text -->
                                                        <div class="tooltip-text">
                                                            Total amount after applying the commission and adding the applicable commission tax.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </th>

                                        <th class="text-center">Net Invoice</th>
                                        <th class="text-center">Tax</th>
                                        <th class="text-center">Supplier Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $index => $order)
                                      @php
                                        // Unpack everything you calculated in the controller
                                        $calc = $order->calculated;
                                      @endphp
                                  
                                      <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ $order->tracking ?? '-' }}</td>
                                        <td class="text-center">{{ $order->created_at->format('d M Y') }}</td>
                                  
                                        {{-- Total Amount (base + commission + commission tax) --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['totalAmount'], 2) }}
                                        </td>
                                  
                                        {{-- Total Invoice Without Tax (subTotal + shipping – discount) --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['subTotal'] + $calc['shipping'] - $calc['discount'], 2) }}
                                        </td>
                                  
                                        {{-- System Commission --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['commissionAmount'], 2) }} ({{ $calc['commissionPct'] }}%)
                                        </td>
                                  
                                        {{-- Commission Tax --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['commissionTaxAmt'], 2) }} ({{ $calc['commissionTaxPct'] }}%)
                                        </td>
                                  
                                        {{-- Net Invoice (base only) --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['base'], 2) }}
                                        </td>
                                  
                                        {{-- Tax --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['tax'], 2) }}
                                        </td>
                                  
                                        {{-- Supplier Due (base – total paid out) --}}
                                        <td class="text-right">
                                          <span class="icon-saudi_riyal"></span>
                                          {{ number_format($calc['totalSuplierDue'], 2) }}
                                        </td>
                                      </tr>
                                    @endforeach
                                  </tbody>
                                  
                                    
                            </table>
                             
                        </div>
                        <!-- Pagination Footer -->
                        @include('layouts.includes.table-pagination', ['paginator' => $orders])
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</main>

@endsection
