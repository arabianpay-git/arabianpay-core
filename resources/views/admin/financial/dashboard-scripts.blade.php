@extends('layouts.base')

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Get charts data
        const monthlyData = JSON.parse('{!! addslashes(json_encode($chartData["monthly_data"])) !!}');
        const accountTypesData = JSON.parse('{!! addslashes(json_encode($chartData["account_types"])) !!}');

        // Initialize Monthly Cash Flow Chart
        const cashFlowCanvas = document.getElementById('monthlyCashFlowChart');
        if (cashFlowCanvas) {
            const ctx1 = cashFlowCanvas.getContext('2d');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: monthlyData.map(item => item.month),
                        datasets: [{
                            label: '{{ translate("Revenue") }}',
                            data: monthlyData.map(item => item.revenue),
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.1
                        }, {
                            label: '{{ translate("Expenses") }}',
                            data: monthlyData.map(item => item.expenses),
                            borderColor: '#e74a3b',
                            backgroundColor: 'rgba(231, 74, 59, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.1
                        }, {
                            label: '{{ translate("Net Income") }}',
                            data: monthlyData.map(item => item.net),
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        layout: {
                            padding: {
                                left: 10,
                                right: 25,
                                top: 25,
                                bottom: 0
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 7
                                }
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 5,
                                    padding: 10,
                                    callback: function(value) {
                                        return value.toFixed(2) + ' SR';
                                    }
                                },
                                grid: {
                                    color: "rgb(234, 236, 244)",
                                    drawBorder: false,
                                    borderDash: [2],
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyColor: "#858796",
                                titleMarginBottom: 10,
                                titleColor: '#6e707e',
                                titleFont: {
                                    size: 14
                                },
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                padding: {
                                    x: 15,
                                    y: 15
                                },
                                displayColors: false,
                                intersect: false,
                                mode: 'index',
                                caretPadding: 10,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' SR';
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                console.error('Could not get 2d context for monthlyCashFlowChart');
            }
        } else {
            console.error('Could not find monthlyCashFlowChart canvas');
        }

        // Initialize Account Types Chart
        const pieCanvas = document.getElementById('accountTypesChart');
        if (pieCanvas) {
            const ctx2 = pieCanvas.getContext('2d');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: accountTypesData.map(item => item.account_type1),
                        datasets: [{
                            data: accountTypesData.map(item => item.count),
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#f4b619', '#c92a2a'],
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                padding: {
                                    x: 15,
                                    y: 15
                                },
                                displayColors: false,
                                caretPadding: 10,
                            }
                        },
                        cutout: '70%'
                    }
                });
            } else {
                console.error('Could not get 2d context for accountTypesChart');
            }
        } else {
            console.error('Could not find accountTypesChart canvas');
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
});
</script>
@endsection

@section('content')
    <!-- Keep your existing content section here -->