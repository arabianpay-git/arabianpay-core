@php
    function parseUserAgent($userAgent)
    {
        if (!$userAgent) {
            return [
                'browser' => 'Unknown',
                'os' => 'Unknown',
                'device' => 'Unknown',
                'icon' => 'ki-outline ki-screen',
            ];
        }

        $browser = 'Unknown';
        $os = 'Unknown';
        $device = 'Desktop';
        $icon = 'ki-outline ki-screen';

        // 1. Detect Browser & Icons
        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edge') === false) {
            $browser = 'Chrome';
            $icon = 'ki-outline ki-chrome text-primary';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
            $icon = 'ki-outline ki-status text-orange-500';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            $browser = 'Safari';
            $icon = 'ki-outline ki-compass text-info';
        } elseif (strpos($userAgent, 'Edge') !== false || strpos($userAgent, 'Edg') !== false) {
            $browser = 'Edge';
            $icon = 'ki-outline ki-microsoft text-blue-600';
        } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
            $browser = 'Opera';
            $icon = 'ki-outline ki-status text-danger';
        }

        // 2. Detect OS & Icons (Overwrites browser icon if OS is more specific for your UI)
        if (strpos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
            $icon = 'ki-outline ki-microsoft text-info';
        } elseif (strpos($userAgent, 'Macintosh') !== false || strpos($userAgent, 'Mac OS') !== false) {
            $os = 'macOS';
            $icon = 'ki-outline ki-apple text-gray-900';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
            $icon = 'ki-outline ki-setting-2 text-gray-700';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $os = 'Android';
            $device = 'Mobile';
            $icon = 'ki-outline ki-android text-success';
        } elseif (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            $os = 'iOS';
            $device = strpos($userAgent, 'iPad') !== false ? 'Tablet' : 'Mobile';
            $icon = 'ki-outline ki-apple text-gray-900';
        }

        // 3. Adjust Device Icon if not specific OS
        if ($device === 'Mobile' && $os === 'Unknown') {
            $icon = 'ki-outline ki-phone text-gray-500';
        } elseif ($device === 'Tablet') {
            $icon = 'ki-outline ki-tablet text-gray-500';
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'device' => $device,
            'icon' => $icon,
            'raw' => $userAgent,
        ];
    }
@endphp

<div class="modal" data-modal="true" id="login_attempts_modal">
    <div class="modal-content max-w-4xl flex flex-col">
        <div class="modal-header py-4 px-5 border-b border-slate-200">
            <h5 class="modal-title font-bold text-lg text-slate-900">{{ translate('Last 10 Login Attempts') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross text-lg"></i>
            </button>
        </div>

        <div class="modal-body p-0 flex-1 overflow-auto">
            <div class="divide-y divide-slate-100">
                @forelse($lastLoginAttempts as $attempt)
                    @php
                        $parsedAgent = parseUserAgent($attempt->user_agent);
                        $identifier = $attempt->user_id
                            ? $merchant->user->business_name ?? $merchant->user->email
                            : $merchant->user->phone_number ?? 'N/A';
                        $identifier = maskedSensitiveText('phone_number', $identifier);
                    @endphp

                    <div class="p-4 hover:bg-slate-50 transition-colors {{ $loop->first ? 'bg-blue-50' : '' }}">
                        <div class="flex items-start gap-3">
                            <!-- Status Icon -->
                            <div class="shrink-0 mt-0.5">
                                @if ($attempt->is_success)
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <i class="ki-solid ki-check text-emerald-600 text-sm"></i>
                                    </div>
                                @else
                                    <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center">
                                        <i class="ki-solid ki-cross text-rose-600 text-sm"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Main Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-900">
                                            @if ($attempt->is_success)
                                                {{ translate('Successful Login') }}
                                            @else
                                                {{ translate('Failed Login') }}
                                            @endif
                                        </span>

                                        @if ($loop->first)
                                            <span class="badge badge-xs badge-outline badge-primary">
                                                {{ translate('LATEST') }}
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-xs text-slate-500 font-medium">
                                        {{ $attempt->created_at->format('M d, Y H:i:s') }}
                                    </span>
                                </div>

                                <!-- Device & Browser Info -->
                                <div class="flex flex-wrap items-center gap-4 mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <i class="{{ $parsedAgent['icon'] }} text-base"></i>
                                        <span class="text-xs font-medium text-slate-700">
                                            {{ $parsedAgent['browser'] }} • {{ $parsedAgent['os'] }}
                                        </span>
                                        <span class="badge badge-xs badge-outline badge-light">
                                            {{ $parsedAgent['device'] }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <i class="ki-solid ki-earth text-slate-400 text-sm"></i>
                                        <span class="text-xs font-medium text-slate-700">
                                            {{ maskedSensitiveText('phone_number', $attempt->ip_address) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Additional Info -->
                                <div class="text-xs text-slate-600 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ translate('Identifier') }}:</span>
                                        <span class="text-slate-800">{{ $identifier }}</span>
                                    </div>

                                    @if (!$attempt->is_success && $attempt->failure_reason)
                                        <div class="flex items-start gap-2 mt-1">
                                            <span
                                                class="font-medium text-rose-700 shrink-0">{{ translate('Reason') }}:</span>
                                            <span class="text-rose-600 bg-rose-50 px-2 py-1 rounded text-xs">
                                                {{ $attempt->failure_reason }}
                                            </span>
                                        </div>
                                    @endif

                                    @if ($attempt->user_agent)
                                        <div class="mt-2 pt-2 border-t border-slate-100">
                                            <details class="group">
                                                <summary
                                                    class="flex items-center gap-1 text-slate-500 hover:text-slate-700 cursor-pointer list-none">
                                                    <i class="ki-solid ki-information text-xs"></i>
                                                    <span
                                                        class="text-xs font-medium">{{ translate('Show User Agent') }}</span>
                                                    <i
                                                        class="ki-solid ki-arrow-down transform group-open:rotate-180 transition-transform ml-auto text-xs"></i>
                                                </summary>
                                                <div
                                                    class="mt-2 p-2 bg-slate-50 rounded text-xs font-mono text-slate-600 overflow-x-auto">
                                                    {{ $attempt->user_agent }}
                                                </div>
                                            </details>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                            <i class="ki-solid ki-shield-cross text-2xl text-slate-400"></i>
                        </div>
                        <h4 class="text-slate-700 font-medium mb-1">{{ translate('No Login Attempts Found') }}</h4>
                        <p class="text-slate-500 text-sm">
                            {{ translate('There are no login attempts recorded for this merchant.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="modal-footer p-4 border-t border-slate-200 bg-slate-50">
            <div class="flex items-center justify-between text-xs text-slate-600">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span>{{ translate('Successful') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <span>{{ translate('Failed') }}</span>
                    </div>
                </div>

                <div class="text-slate-500">
                    {{ translate('Showing last') }} {{ min(10, $lastLoginAttempts->count()) }}
                    {{ translate('attempts') }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    summary::-webkit-details-marker {
        display: none;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f5f9;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
