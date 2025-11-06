<div class="card grow mt-4 bg-white shadow-sm rounded-xl">
    <div class="card-header px-6 pt-5 pb-3 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">{{ translate('Order Activity Logs') }}</h3>
    </div>

    <div class="card-body p-4 sm:p-6">
        @if ($actionLogs->isEmpty())
            <p class="text-gray-500 text-sm">{{ translate('No logs available for this order.') }}</p>
        @else
            <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-1">
                @foreach ($actionLogs as $log)
                    @php
                        // Determine color based on action type
                        $eventColor = match ($log->action_type) {
                            'accept_order' => 'green',
                            'reject_order' => 'red',
                            'update_status', 'delivery_otp_sent' => 'blue',
                            'order_created' => 'gray',
                            default => 'purple',
                        };

                        $timestamp = \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A');

                        $actorId = $log->properties['actor_user_id'] ?? null;
                        $actorType = $log->properties['actor_user_type'] ?? 'System';

                        // Determine actor label
                        if ($actorId === null) {
                            $actorLabel = translate('System');
                        } elseif ($actorType === 'employee') {
                            $actorLabel = translate('Employee');
                        } elseif ($actorType === 'admin') {
                            $actorLabel = translate('Admin');
                        } elseif ($actorType === 'seller' || $actorType === 'merchant') {
                            // Fetch seller/merchant info
                            $seller = $log->seller; // adjust if you use Merchant model
                            if ($seller) {
                                $actorLabel = "{$seller->first_name} {$seller->last_name} ({$seller->business_name})";
                            } else {
                                $actorLabel = translate('Supplier') . " (#{$actorId})";
                            }
                        } else {
                            $actorLabel = translate('User') . " (#{$actorId})";
                        }

                        // Description and metadata
                        $formattedDescription = $log->description ?? translate('No description provided.');
                        $ip = $log->properties['ip_address'] ?? 'N/A';
                        $device = $log->properties['device_type'] ?? 'Unknown';
                        $platform = $log->properties['platform_os'] ?? 'Unknown';
                        $browser = $log->properties['browser_name'] ?? 'Unknown';
                    @endphp

                    <div class="log-card {{ $eventColor }}">
                        <div class="log-top">
                            <div class="log-event">{{ translate(ucwords(str_replace('_', ' ', $log->action_type))) }}
                            </div>
                            <div class="log-time text-xs text-gray-500">{{ $timestamp }}</div>
                        </div>

                        <div class="log-body mt-1 text-sm text-gray-700">
                            {{-- Actor Label --}}
                            <div class="log-user font-semibold">
                                {{ $actorLabel }}
                                <span class="text-xs text-gray-500 font-normal">
                                    ({{ $actorType === 'seller' || $actorType === 'merchant' ? 'Supplier' : ucfirst($actorType) }})
                                </span>
                            </div>

                            {{-- Description --}}
                            <div class="log-desc mt-1">{{ $formattedDescription }}</div>

                            {{-- Metadata --}}
                            <div class="log-meta mt-1 text-xs text-gray-400">
                                IP: {{ $ip }} |
                                Device: {{ $device }} ({{ $platform }}) |
                                Browser: {{ $browser }}
                            </div>
                        </div>
                    </div>
                @endforeach

                @include('layouts.includes.table-pagination', ['paginator' => $actionLogs])
            </div>
        @endif
    </div>
</div>



@push('styles')
    <style>
        .log-card {
            background: #fff;
            border: 1px solid #e6e9ee;
            border-radius: 0.75rem;
            padding: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: transform .12s ease, box-shadow .12s ease;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(15, 23, 42, 0.06);
        }

        .log-card.green {
            border-left: 4px solid #10b981;
        }

        .log-card.red {
            border-left: 4px solid #ef4444;
        }

        .log-card.blue {
            border-left: 4px solid #3b82f6;
        }

        .log-card.gray {
            border-left: 4px solid #9ca3af;
        }

        .log-card.purple {
            border-left: 4px solid #8b5cf6;
        }

        .log-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .log-event {
            font-weight: 600;
            color: #111827;
        }

        .log-body {
            font-size: 0.875rem;
            line-height: 1.2;
        }

        .log-user {
            /* font-weight: 500; <-- Removed to allow different weights for name/type */
            color: #374151;
        }

        .log-desc {
            margin-top: 0.25rem;
            color: #4b5563;
        }

        @media (max-width: 640px) {
            .log-card {
                padding: 0.65rem;
            }
        }
    </style>
@endpush
