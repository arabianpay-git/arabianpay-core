@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            /* Ensure legend wraps nicely on small screens */
            .chart-legend {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 1rem;
            }

            .chart-legend li {
                display: flex;
                align-items: center;
                margin: 0.25rem 0.75rem;
            }

            .chart-legend span {
                display: inline-block;
                width: 12px;
                height: 12px;
                margin-right: 0.5rem;
            }
        </style>
    @endpush

    <main class="grow content pt-5" id="content" role="content">
        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex items-center justify-between gap-2.5">
                <h1 class="text-xl font-medium text-gray-900">
                    {{ translate('Onboarding Funnel Report') }}
                </h1>
                <div class="flex">
                    <select class="select select-sm w-40" id="loanRange">
                        <option value="1M" {{ request('date_range') == '1M' ? 'selected' : '' }}>
                            {{ translate('1 Month') }}</option>
                        <option value="3M" {{ request('date_range') == '3M' ? 'selected' : '' }}>
                            {{ translate('3 Months') }}</option>
                        <option value="6M" {{ request('date_range') == '6M' ? 'selected' : '' }}>
                            {{ translate('6 Months') }}</option>
                        <option value="12M"
                            {{ request('date_range') == '12M' || request('date_range') == null ? 'selected' : '' }}>
                            {{ translate('12 Months') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Chart Card -->
        <div class="container-fixed">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ translate('Monthly Onboarding Funnel Overview') }}</h3>
                </div>
                <div class="card-body">
                    <canvas id="onboardingFunnelChart"></canvas>
                    <!-- Legend will be rendered here -->
                    <ul class="chart-legend" id="chartLegend"></ul>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let chart;

        function renderChart(funnelData) {
            if (!Array.isArray(funnelData)) {
                console.error("renderChart expects an array but got:", funnelData);
                return;
            }

            const ctx = document.getElementById('onboardingFunnelChart').getContext('2d');

            const labels = funnelData.map(i => i.month);
            const submitted = funnelData.map(i => i.applications_submitted);
            const verified = funnelData.map(i => i.verified);
            const approved = funnelData.map(i => i.approved);
            const kycPassed = funnelData.map(i => i.kyc_passed);
            const convRate = funnelData.map(i => i.conversion_rate);

            const gradient = (color1, color2) => {
                const g = ctx.createLinearGradient(0, 0, 0, 300);
                g.addColorStop(0, color1);
                g.addColorStop(1, color2);
                return g;
            };

            if (chart) chart.destroy();

            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: '{{ translate('Applications Submitted') }}',
                            data: submitted,
                            backgroundColor: gradient('rgba(59,130,246,0.8)', 'rgba(59,130,246,0.2)'),
                            borderColor: '#3B82F6',
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 40
                        },
                        {
                            label: '{{ translate('Verified') }}',
                            data: verified,
                            backgroundColor: gradient('rgba(20,184,166,0.8)', 'rgba(20,184,166,0.2)'),
                            borderColor: '#14B8A6',
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 40
                        },
                        {
                            label: '{{ translate('Approved') }}',
                            data: approved,
                            backgroundColor: gradient('rgba(34,197,94,0.8)', 'rgba(34,197,94,0.2)'),
                            borderColor: '#22C55E',
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 40
                        },
                        {
                            label: '{{ translate('KYC Passed') }}',
                            data: kycPassed,
                            backgroundColor: gradient('rgba(245,158,11,0.8)', 'rgba(245,158,11,0.2)'),
                            borderColor: '#F59E0B',
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 40
                        },
                        {
                            label: '{{ translate('Conversion Rate (%)') }}',
                            data: convRate,
                            type: 'line',
                            borderColor: '#8B5CF6',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.4,
                            yAxisID: 'y1',
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#8B5CF6',
                            pointRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: '{{ translate('Month') }}',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '{{ translate('Count') }}',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: v => Number(v).toFixed(0)
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            min: 0,
                            max: 100,
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: '{{ translate('Conversion Rate (%)') }}',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: v => v.toFixed(2) + '%'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    return ctx.dataset.type === 'line' ?
                                        `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)}%` :
                                        `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(0)}`;
                                }
                            }
                        },
                        legend: {
                            display: false // We'll use custom legend
                        }
                    }
                }
            });

            // Custom legend rendering
            const legendContainer = document.getElementById('chartLegend');
            legendContainer.innerHTML = chart.data.datasets.map(ds => {
                const color = ds.borderColor || ds.backgroundColor;
                return `<li><span style="background:${color};border-radius:2px;"></span>${ds.label}</li>`;
            }).join('');
        }

        function fetchData(range) {
            fetch(`{{ route('onboardingFunnelReport') }}?date_range=${range}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                    return res.json();
                })
                .then(data => {
                    console.log("Fetched Funnel Data:", data);
                    renderChart(data);
                })
                .catch(error => {
                    console.error("Error fetching funnel data:", error);
                    alert("Failed to fetch data, please try again.");
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const defaultData = @json($funnelData);
            renderChart(defaultData);

            document.getElementById('loanRange').addEventListener('change', function() {
                fetchData(this.value);
            });
        });
    </script>
@endpush
