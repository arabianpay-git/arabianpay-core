@php

    $alerts = $alerts ?? [];
    $alertCount = count($alerts);

    $severityColors = [
        'critical' => 'red',
        'high' => 'orange',
        'medium' => 'yellow',
        'low' => 'blue',
        'info' => 'gray',
    ];

    $severityIcons = [
        'critical' => 'ki-shield-cross',
        'high' => 'ki-warning',
        'medium' => 'ki-information',
        'low' => 'ki-notification',
        'info' => 'ki-check-circle',
    ];
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="ki-filled ki-notification text-orange-600 mr-2"></i>
            {{ translate('Risk Alerts') }}
            @if ($alertCount > 0)
                <span class="ml-2 bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $alertCount }}
                </span>
            @endif
        </h3>

        <div class="text-sm text-gray-600">
            @if ($alertCount === 0)
                <span class="text-green-600 font-medium">{{ translate('No active alerts') }}</span>
            @else
                {{ translate('Active alerts requiring attention') }}
            @endif
        </div>
    </div>

    @if ($alertCount === 0)
        <div class="text-center py-8">
            <div class="mx-auto w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-3">
                <i class="ki-filled ki-check-circle text-green-600 text-xl"></i>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-1">{{ translate('No Critical Alerts') }}</h4>
            <p class="text-gray-600 max-w-md mx-auto">
                {{ translate('All monitored risk indicators are within configured thresholds.') }}
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($alerts as $alert)
                @php
                    $severity = $alert['type'] ?? 'info';
                    $color = $severityColors[$severity] ?? 'gray';
                    $icon = $severityIcons[$severity] ?? 'ki-information';
                @endphp

                <div
                    class="border border-{{ $color }}-200 rounded-lg p-4 bg-{{ $color }}-50 hover:bg-{{ $color }}-100 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3">
                            <div class="mt-1 p-2 bg-{{ $color }}-100 rounded-lg">
                                <i class="ki-filled {{ $icon }} text-{{ $color }}-600"></i>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2 mb-1">
                                    <h4 class="font-semibold text-gray-900">{{ $alert['title'] ?? '' }}</h4>
                                    <span
                                        class="bg-{{ $color }}-100 text-{{ $color }}-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                        {{ ucfirst($severity) }}
                                    </span>
                                </div>

                                <p class="text-gray-700 mb-2">{!! $alert['description'] ?? '' !!}</p>

                                @if (!empty($alert['additional_info']))
                                    <div class="mt-3 pt-3 border-t border-{{ $color }}-200">
                                        <button type="button"
                                            class="text-sm text-{{ $color }}-600 hover:text-{{ $color }}-800 font-medium flex items-center"
                                            onclick="toggleAlertDetails(this)" data-alert-id="{{ $loop->index }}">
                                            <i class="ki-filled ki-eye mr-1"></i>
                                            {{ translate('View Details') }}
                                        </button>

                                        <div id="alert-details-{{ $loop->index }}" class="hidden mt-3 space-y-2">
                                            @foreach ($alert['additional_info'] as $key => $value)
                                                @if (is_array($value))
                                                    <div>
                                                        <div class="font-medium text-gray-700 text-sm mb-1">
                                                            {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                        </div>
                                                        @foreach ($value as $subKey => $subValue)
                                                            <div class="text-sm text-gray-600 ml-4">
                                                                @if (is_array($subValue))
                                                                    {{ json_encode($subValue) }}
                                                                @else
                                                                    {{ $subValue }}
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-sm text-gray-700">
                                                        <span
                                                            class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <span class="text-gray-600 ml-2">{!! $value !!}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-sm text-gray-500 whitespace-nowrap ml-4">
                            <i class="ki-filled ki-clock mr-1"></i>
                            {{ $alert['time'] ?? 'Recently' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
    <script>
        function toggleAlertDetails(button) {
            const alertId = button.getAttribute('data-alert-id');
            const detailsDiv = document.getElementById(`alert-details-${alertId}`);

            if (detailsDiv.classList.contains('hidden')) {
                detailsDiv.classList.remove('hidden');
                button.innerHTML =
                    `<i class="ki-filled ki-eye-slash mr-1"></i> ${button.dataset.hideText || 'Hide Details'}`;
            } else {
                detailsDiv.classList.add('hidden');
                button.innerHTML = `<i class="ki-filled ki-eye mr-1"></i> ${button.dataset.viewText || 'View Details'}`;
            }
        }
    </script>
@endpush
