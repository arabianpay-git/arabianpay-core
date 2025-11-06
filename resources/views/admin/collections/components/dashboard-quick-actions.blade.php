@push('styles')
    <style>
        :root {
            --qa-bg: #f8fafc;
            /* card background */
            --qa-border: #e6eefc;
            /* subtle border */
            --qa-primary: #2563eb;
            /* primary */
            --qa-primary-100: #dbeafe;
            /* light primary bg */
            --qa-primary-200: #bfdbfe;
            /* hover primary bg */
            --qa-danger: #ef4444;
            /* badge */
            --qa-text: #111827;
            /* heading text */
            --qa-muted: #6b7280;
            /* muted text */
        }


        .quick-actions-card .card-header {
            border-bottom: 1px solid #e6e6e6;
            padding-bottom: 1rem;
        }

        /* Grid (we keep Tailwind grid but ensure fallback) */
        .quick-actions-grid {
            display: grid;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .quick-actions-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Item */
        .quick-action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            border-radius: 0.75rem;
            background-color: #ffffff;
            border: 1px solid #eef2f7;
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
            position: relative;
            overflow: visible;
            min-height: 9rem;
        }

        .quick-action-item:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 20px rgba(2, 6, 23, 0.06);
            border-color: rgba(37, 99, 235, 0.14);
            /* primary tint */
        }

        /* Icon wrapper */
        .quick-action-icon-wrapper {
            position: relative;
            margin-bottom: 0.75rem;
        }

        .quick-action-icon {
            width: 4rem;
            /* w-16 */
            height: 4rem;
            /* h-16 */
            border-radius: 0.75rem;
            /* rounded-2xl */
            background-color: var(--qa-primary-100);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color .25s ease, transform .25s ease;
            will-change: transform;
        }

        .quick-action-item:hover .quick-action-icon {
            background-color: var(--qa-primary-200);
            transform: scale(1.03);
        }

        .quick-action-icon i {
            color: var(--qa-primary);
            font-size: 1.25rem;
            /* ~text-2xl */
            transition: transform .25s ease, color .25s ease;
        }

        .quick-action-item:hover .quick-action-icon i {
            transform: scale(1.08);
            color: var(--qa-primary);
        }

        /* Badge */
        .quick-action-badge {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            width: 1.5rem;
            height: 1.5rem;
            background-color: var(--qa-danger);
            color: #fff;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.70rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        /* Title & desc */
        .quick-action-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--qa-text);
            margin-bottom: 0.35rem;
            text-align: center;
        }

        .quick-action-desc {
            font-size: 0.875rem;
            color: var(--qa-muted);
            text-align: center;
            line-height: 1.25;
        }

        /* action reveal */
        .quick-action-cta {
            margin-top: 0.5rem;
            opacity: 0;
            transition: opacity .25s ease, transform .25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--qa-primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .quick-action-item:hover .quick-action-cta {
            opacity: 1;
            transform: translateY(0);
        }

        /* Make anchor fill block for keyboard UX */
        .quick-action-link {
            display: block;
            text-decoration: none;
        }
    </style>
@endpush

<div class="container-fixed mb-7.5">
    <div class="card quick-actions-card">
        <div class="card-header">
            <h3 class="card-title font-semibold text-base text-gray-900">
                {{ translate('Quick Actions') }}
            </h3>
            <p class="text-sm text-gray-500 mt-1">{{ translate('Frequently used collection tasks') }}</p>
        </div>

        <div class="card-body p-6">
            <div class="quick-actions-grid">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="quick-action-link" aria-label="{{ $action['title'] }}">
                        <div class="quick-action-item" role="button" tabindex="0">
                            {{-- Icon + Badge --}}
                            <div class="quick-action-icon-wrapper">
                                <div class="quick-action-icon" aria-hidden="true">
                                    <i class="{{ $action['icon'] }}"></i>
                                </div>

                                @if (isset($action['count']) && $action['count'] > 0)
                                    <span class="quick-action-badge" aria-hidden="true">
                                        {{ $action['count'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Title --}}
                            <div class="quick-action-title">{{ $action['title'] }}</div>

                            {{-- Description --}}
                            <div class="quick-action-desc">{{ $action['description'] ?? '' }}</div>

                            {{-- CTA reveal --}}
                            <div class="quick-action-cta" aria-hidden="true">
                                <span>{{ translate('Take action') }}</span>
                                <i class="ki-filled ki-arrow-right" style="font-size:0.75rem;"></i>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
