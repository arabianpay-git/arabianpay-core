<div class="container-fixed mt-4">
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <h3 class="card-title font-semibold text-base text-gray-900">
                {{ translate('Early Warning System') }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ translate('Real-time monitoring of risk indicators and alerts') }}
            </p>
        </div>

        <div class="card-body p-6">
            <!-- Alert Summary -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                <!-- Device Anomalies -->
                <div class="card border border-red-200 bg-red-50 rounded-xl p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium">{{ translate('Device Anomalies') }}</p>
                            <h3 class="text-xl font-bold">{{ $ewsData['device_anomalies']['total'] }}</h3>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center text-sm font-medium text-danger">
                                {{ $ewsData['device_anomalies']['change'] }}
                                <i class="ki-filled ki-arrow-up ml-1"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Geo/Velocity Alerts -->
                <div class="card border border-yellow-200 bg-yellow-50 rounded-xl p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-600">{{ translate('Geo/Velocity Alerts') }}</p>
                            <h3 class="text-xl font-bold text-yellow-900">{{ $ewsData['alerts']['geo_velocity'] }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Transaction Pattern Alerts -->
                <div class="card border border-orange-200 bg-orange-50 rounded-xl p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-orange-600">{{ translate('Transaction Pattern') }}</p>
                            <h3 class="text-xl font-bold text-orange-900">
                                {{ $ewsData['alerts']['transaction_pattern'] }}</h3>
                        </div>
                    </div>
                </div>

                <!-- High Risk Alerts -->
                <div class="card border border-purple-200 bg-purple-50 rounded-xl p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-600">{{ translate('High Risk Alerts') }}</p>
                            <h3 class="text-xl font-bold text-purple-900">{{ $ewsData['alerts']['high_risk'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">

                <!-- Payment Behavior Trends -->
                <div class="card border border-gray-200 rounded-xl">
                    <div class="card-header p-5 border-b border-gray-100">
                        <h4 class="card-title font-semibold text-gray-900 text-base">
                            {{ translate('Payment Behavior Trends') }}
                        </h4>
                    </div>
                    <div class="card-body p-6">
                        <div id="payment-trend-chart"></div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                            <div>
                                <p class="text-sm text-gray-600">{{ translate('Avg Days Late') }}</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ $ewsData['payment_behavior']['avg_days_late'] }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">{{ translate('Repeat Late Payers') }}</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ $ewsData['payment_behavior']['repeat_late_payers'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Device Anomalies Breakdown -->
                <div class="card border border-gray-200 rounded-xl">
                    <div class="card-header p-5 border-b border-gray-100">
                        <h4 class="card-title font-semibold text-gray-900 text-base">
                            {{ translate('Device Anomalies Breakdown') }}
                        </h4>
                    </div>
                    <div class="card-body p-6">
                        <div id="device-anomalies-chart"></div>
                    </div>
                </div>

                <!-- Alert Type Distribution -->
                <div class="card border border-gray-200 rounded-xl">
                    <div class="card-header p-5 border-b border-gray-100">
                        <h4 class="card-title font-semibold text-gray-900 text-base">
                            {{ translate('Alert Type Distribution') }}
                        </h4>
                    </div>
                    <div class="card-body p-6">
                        <div id="alert-distribution-chart"></div>
                    </div>
                </div>

                <!-- Supplier Concentration -->
                <div class="card border border-gray-200 rounded-xl">
                    <div class="card-header p-5 border-b border-gray-100">
                        <h4 class="card-title font-semibold text-gray-900 text-base">
                            {{ translate('Supplier Concentration Risk') }}
                        </h4>
                    </div>
                    <div class="card-body p-6">
                        <div id="supplier-concentration-chart"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1️⃣ Payment Trend Chart
            new ApexCharts(document.querySelector("#payment-trend-chart"), {
                series: [{
                    name: '{{ translate('Delinquency Rate') }}',
                    data: @json($ewsData['payment_behavior']['delinquency_trend'])
                }],
                chart: {
                    type: 'area',
                    height: 250
                },
                colors: ['#EF4444'],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3
                    }
                },
                xaxis: {
                    categories: Array.from({
                        length: @json(count($ewsData['payment_behavior']['delinquency_trend']))
                    }, (_, i) => `{{ translate('Week') }} ${i + 1}`)
                },
                yaxis: {
                    title: {
                        text: '{{ translate('Delinquency Rate (%)') }}'
                    }
                }
            }).render();

            // 2️⃣ Device Anomalies Chart
            new ApexCharts(document.querySelector("#device-anomalies-chart"), {
                series: Object.values(@json($ewsData['device_anomalies']['breakdown'])),
                chart: {
                    type: 'polarArea',
                    height: 300
                },
                labels: Object.keys(@json($ewsData['device_anomalies']['breakdown'])),
                colors: ['#EF4444', '#F59E0B', '#8B5CF6', '#6B7280'],
                legend: {
                    position: 'bottom'
                },
                stroke: {
                    colors: ['#fff']
                },
                fill: {
                    opacity: 0.8
                }
            }).render();

            // 3️⃣ Alert Type Distribution
            new ApexCharts(document.querySelector("#alert-distribution-chart"), {
                series: [{
                    name: '{{ translate('Alerts') }}',
                    data: Object.values(@json($ewsData['alerts'])).map(v => parseFloat(v) || 0)
                }],
                chart: {
                    type: 'radar',
                    height: 300
                },
                labels: Object.keys(@json($ewsData['alerts'])).map(k => '{{ translate('') }}' + k
                    .replace('_', ' ')),
                colors: ['#3B82F6'],
                stroke: {
                    width: 2
                },
                fill: {
                    opacity: 0.3
                },
                markers: {
                    size: 5
                },
                yaxis: {
                    show: true,
                    min: 0
                },
                legend: {
                    show: false
                }
            }).render();

            // 4️⃣ Supplier Concentration Chart
            new ApexCharts(document.querySelector("#supplier-concentration-chart"), {
                series: [{
                    name: '{{ translate('Exposure %') }}',
                    data: Object.values(@json($ewsData['concentration']['top_suppliers']))
                }],
                chart: {
                    type: 'bar',
                    height: 300
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true
                    }
                },
                colors: ['#3B82F6'],
                dataLabels: {
                    enabled: true,
                    formatter: val => val + '%'
                },
                xaxis: {
                    categories: Object.keys(@json($ewsData['concentration']['top_suppliers']))
                }
            }).render();

        });
    </script>
@endpush
