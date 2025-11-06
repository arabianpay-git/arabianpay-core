<style>
    /* Light backgrounds for metric icons */
    .bg-primary-light {
        background-color: rgba(62, 151, 255, 0.1);
    }

    .bg-success-light {
        background-color: rgba(80, 205, 137, 0.1);
    }

    .bg-danger-light {
        background-color: rgba(241, 65, 108, 0.1);
    }

    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .bg-info-light {
        background-color: rgba(0, 183, 239, 0.1);
    }

    /* Ensure perfect circle for icons */
    .metric-icon-circle {
        width: 48px;
        /* fixed width */
        height: 48px;
        /* fixed height */
        border-radius: 50%;
        /* circle */
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="container-fixed mb-7.5">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach ($metrics as $key => $metric)
            <div class="card transform transition-all duration-300 hover:scale-105 hover:shadow-lg">
                <div class="card-body flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1 uppercase tracking-wide">
                            {{ translate(ucfirst(str_replace('_', ' ', $key))) }}
                        </h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $metric['value'] }}</p>
                        <div class="flex items-center mt-1">
                            <span
                                class="text-xs {{ $metric['trend'] == 'up' ? 'text-success' : 'text-danger' }} flex items-center">
                                <i
                                    class="ki-filled ki-arrow-{{ $metric['trend'] == 'up' ? 'up' : 'down' }} {{ $metric['trend'] == 'up' ? 'text-success' : 'text-danger' }} mr-1"></i>
                                {{ $metric['change'] }}
                            </span>
                            <span class="text-xs text-gray-500 ml-2">vs last month</span>
                        </div>
                    </div>
                    <!-- Proper circular icon -->
                    <div class="metric-icon-circle bg-{{ $metric['color'] }}-light">
                        <i class="{{ $metric['icon'] }} text-{{ $metric['color'] }} text-xl"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
