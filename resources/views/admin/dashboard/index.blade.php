@extends('layouts.base')

@section('content')

@push('styles')
<style>
  /* Make the legend items flow horizontally and center them */
  #loanFlowChart .apexcharts-legend,
  #paymentStatusChart .apexcharts-legend,
  #revenueChart .apexcharts-legend,
  #orderStatusChart .apexcharts-legend,
  #settlementChart .apexcharts-legend,
  #defaultRatesChart .apexcharts-legend {
    display: grid !important;
    grid-auto-flow: column;
    grid-auto-columns: max-content;
    justify-content: center;
    align-items: center;
    gap: .15rem;
    padding-bottom: 0.5rem;
  }

  /* Prevent labels from wrapping */
  #loanFlowChart .apexcharts-legend-text,
  #paymentStatusChart .apexcharts-legend-text,
  #revenueChart .apexcharts-legend-text,
  #orderStatusChart .apexcharts-legend-text,
  #settlementChart .apexcharts-legend-text,
  #defaultRatesChart .apexcharts-legend {
    white-space: nowrap !important;
  }
</style>
@endpush



<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed" id="content_container"></div>
    <div class="container-fixed">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-gray-900">
                    Dashboard
                </h1>
                <div class="flex items-center gap-2 text-sm font-normal text-gray-700">
                    Welcome back! Here’s a quick overview of your application’s performance and recent activity.
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="flex">
                    <select class="select select-sm w-40" id="loanRange">
                        <option value="1M" {{ request('date_range') == '1M' ? 'selected' : '' }}>1 Month</option>
                        <option value="3M" {{ request('date_range') == '3M' ? 'selected' : '' }}>3 Months</option>
                        <option value="6M" {{ request('date_range') == '6M' ? 'selected' : '' }}>6 Months</option>
                        <option value="12M" {{ request('date_range') == '12M' || request('date_range') == null ? 'selected' : '' }}>12 Months</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="grid lg:grid-cols-5 gap-y-5 lg:gap-7.5 items-stretch">
                <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-sm font-semibold">Active Loans</h4>
                            <div class="text-2xl font-bold">{{ number_format($loanData['active_loans']) }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-sm font-semibold">Delinquency Rate</h4>
                            <div class="text-2xl font-bold">{{ $loanData['overdue_instalments']['rate'] }}%</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-sm font-semibold">Avg. Credit Score</h4>
                            <div class="text-2xl font-bold">{{ round($riskData['credit_scores'], 1) }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-sm font-semibold">Current Liquidity</h4>
                            <div class="text-2xl font-bold"><span class="icon-saudi_riyal"></span>{{ number_format($financialData['wallet_balances']->last()['balance']) }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:gap-7.5 h-full items-stretch">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-sm font-semibold">Current Liquidity</h4>
                            <div class="text-2xl font-bold"><span class="icon-saudi_riyal"></span>{{ number_format($financialData['wallet_balances']->last()['balance']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <!-- Charts Grid: Organized into logical rows -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
                    <!-- Row 1: Loan Flow (wide), Payment Status, Risk Exposure -->
                    <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                        <div class="card-header">
                            <h3 class="card-title">Loan Flow</h3>
                        </div>

                        <div id="loanFlowChart"></div>
                    </div>
            
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Payment Status</h3>
                        </div>
                        <div id="paymentStatusChart"></div>
                    </div>
            
                    
            
                    <!-- Row 2: Credit Utilization, Revenue Streams (wide), Wallet Balances -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Credit Utilization</h3>
                        </div>
                        <div id="creditUtilizationChart"></div>
                    </div>
            
                    <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                        <div class="card-header">
                            <h3 class="card-title">Revenue Streams</h3>
                        </div>
                        <div id="revenueChart"></div>
                    </div>
            
                    <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                        <div class="card-header">
                            <h3 class="card-title">Wallet Balances</h3>
                        </div>
                        <div id="walletChart"></div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Order Status</h3>
                        </div>
                        <div id="orderStatusChart"></div>
                    </div>
            
                    {{-- <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Default Rates</h3>
                        </div>
                        <div id="defaultRatesChart"></div>
                    </div> --}}

                    <div class="card col-span-3 md:col-span-2 lg:col-span-3">
                        <div class="card-header">
                            <h3 class="card-title">Risk Exposure</h3>
                        </div>
                        <div id="riskExposureChart"></div>
                    </div>

                    <div class="card col-span-1 md:col-span-2 lg:col-span-2">
                        <div class="card-header">
                            <h3 class="card-title">Fulfillment Times</h3>
                        </div>
                        <div id="fulfillmentChart"></div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Settlement Status</h3>
                        </div>
                        <div id="settlementChart"></div>
                    </div>
            
                    <!-- Optional blank card for alignment if needed -->
                    <div class="hidden lg:block"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Category-wise Product Sales -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Category-wise Product Sales</h3>
                        </div>
                        <div id="categorySalesChart"></div>
                    </div>
                
                    <!-- Category-wise Product Stock -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Category-wise Product Stock</h3>
                        </div>
                        <div id="categoryStockChart"></div>
                    </div>
                </div>
            </div>            
        </div>
    </div>

    
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Loan Flow
    new ApexCharts(document.querySelector('#loanFlowChart'), {
        chart: { type: 'line', height: 350 },
        series: [
            { name: 'Disbursed', data: @json($loanData['disbursement_vs_repayment']['disbursed']) },
            { name: 'Repaid',    data: @json($loanData['disbursement_vs_repayment']['repaid']) }
        ],
        xaxis: { categories: @json($loanData['disbursement_vs_repayment']['months']) },
        colors: ['#3B82F6','#10B981']
    }).render();

    // Payment Status
    new ApexCharts(document.querySelector('#paymentStatusChart'), {
        chart: { type: 'donut', height: 350 },
        legend: { position: 'bottom', horizontalAlign: 'center'},
        series: @json($loanData['payment_status']->pluck('count')),
        colors: ['#3B82F6','#EF4444','#10B981','#F97316','#F59E0B'],
        labels: @json($loanData['payment_status']->pluck('status')),
        plotOptions:{ pie:{ donut:{ labels:{ show:true, total:{ show:true } } } } }
    }).render();

    // Risk Exposure
    new ApexCharts(document.querySelector('#riskExposureChart'), {
        chart:{ type:'heatmap', height:350 },
        plotOptions:{ heatmap:{ shadeIntensity:0.5 } },
        dataLabels:{ enabled:false },
        series: @json($riskData['default_rates']),
        colors: ['#10B981','#F59E0B','#EF4444']
    }).render();

    // Credit Utilization
    new ApexCharts(document.querySelector('#creditUtilizationChart'), {
        chart:{ type:'radialBar', height:350 },
        series:[ @json($riskData['credit_utilization']['utilization_percent']) ],
        plotOptions:{ radialBar:{ hollow:{ size:'60%' } } },
        labels:['Credit Used']
    }).render();

    // Revenue Streams
    new ApexCharts(document.querySelector('#revenueChart'), {
        chart:{ type:'bar', height:350, stacked:true },
        series:[
            { name:'Revenue',  data: @json($financialData['revenue_breakdown']['revenue']) },
            { name:'Shipping', data: @json($financialData['revenue_breakdown']['shipping']) },
            { name:'Discounts',data: @json($financialData['revenue_breakdown']['discounts']) }
        ]
    }).render();

    // Wallet Balances
    new ApexCharts(document.querySelector('#walletChart'), {
        chart:{ type:'area', height:350 },
        series:[{ name:'Balance', data: @json($financialData['wallet_balances']->pluck('balance')) }],
        xaxis:{ categories: @json($financialData['wallet_balances']->pluck('month')) }
    }).render();

    // Operational: Order Status
    new ApexCharts(document.querySelector('#orderStatusChart'), {
        chart:{ type:'pie', height:350 },
        legend: { position: 'bottom', horizontalAlign: 'center'},
        series: @json($operationalData['order_statuses']->pluck('count')),
        colors: ['#F59E0B','#10B981','#EF4444','#3B82F6'],
        labels: @json($operationalData['order_statuses']->pluck('status'))
    }).render();

    // // Risk: Default Rates
    // new ApexCharts(document.querySelector('#defaultRatesChart'), {
    //     chart: { type: 'bar', height: 350 },
    //     legend: { position: 'bottom', horizontalAlign: 'center'},
    //     series: @json($riskData['default_rates']),
    //     xaxis: {
    //         categories: @json(array_column($riskData['default_rates'], 'name'))
    //     }
    // }).render();

    // Operational: Fulfillment Times
    new ApexCharts(document.querySelector('#fulfillmentChart'), {
        chart:{ type:'line', height:350 },
        series:[{ name:'Avg Days', data: @json($operationalData['fulfillment_times']['series']) }],
        xaxis:{ categories: @json($operationalData['fulfillment_times']['months']) }
    }).render();

    // Operational: Settlement Status
    new ApexCharts(document.querySelector('#settlementChart'), {
        chart:{ type:'donut', height:350 },
        legend: { position: 'bottom', horizontalAlign: 'center'},
        series: @json($operationalData['settlement_status']->pluck('count')),
        colors: ['#10B981','#EF4444','#F59E0B'],

        labels: @json($operationalData['settlement_status']->pluck('settlement_status'))
    }).render();

    // Category: Sales
    new ApexCharts(document.querySelector("#categorySalesChart"), {
        chart: { type: 'bar', height: 350 },
        xaxis: { categories: @json($categorySales->pluck('name')) },
        series: [{
            name: 'Sales',
            data: @json($categorySales->pluck('sales'))
        }],
        colors: ['#3B82F6']
    }).render();

    // Category: Stock
    new ApexCharts(document.querySelector("#categoryStockChart"), {
        chart: { type: 'bar', height: 350 },
        xaxis: { categories: @json($categoryStock->pluck('name')) },
        series: [{
            name: 'Stock',
            data: @json($categoryStock->pluck('stock'))
        }],
        colors: ['#10B981']
    }).render();

    // date-range filter reload
    document.querySelectorAll('.select').forEach(sel => {
        sel.addEventListener('change', function(){
            window.location = `/admin/dashboard?date_range=${this.value}`;
        });
    });
});
</script>


@endpush
