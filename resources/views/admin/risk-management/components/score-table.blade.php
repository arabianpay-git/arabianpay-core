<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th data-tooltip-key="user-id" class="w-[60px] text-center">{{ translate('User ID') }}</th>
                <th data-tooltip-key="user-name" class="text-center">{{ translate('User Name') }}</th>
                <th data-tooltip-key="cr-id" class="w-[200px] text-center">{{ translate('CR & ID Validation') }}</th>
                <th data-tooltip-key="pos" class="text-center">{{ translate('POS Revenue') }}</th>
                <th data-tooltip-key="repayment" class="text-center">{{ translate('Repayment Delays') }}</th>
                <th data-tooltip-key="industry" class="text-center">{{ translate('Industry Category') }}</th>
                <th data-tooltip-key="location" class="w-[120px] text-left">{{ translate('Location Risk') }}</th>
                <th data-tooltip-key="google" class="text-left">{{ translate('Google Reviews') }}</th>
                <th data-tooltip-key="risk-score" class="text-left">{{ translate('Risk Score') }}</th>
                <th data-tooltip-key="action" class="text-left">{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($risks as $item)
                <tr>
                    <td class="text-center">{{ $item->id }}</td>
                    <td>
                        @if ($item->flagged)
                            <span class="menu-icon items-start w-[20px]">
                                <i class="ki-filled ki-flag text-lg text-danger"></i>
                            </span>
                        @endif

                        <div class="whitespace-nowrap">
                            @php
                                $type = request('type');
                                $profileRoute =
                                    $type === 'merchant'
                                        ? route('supplierProfile', ['id' => $item->id])
                                        : route('customerProfile', ['id' => $item->id]);
                            @endphp

                            <a href="{{ $profileRoute }}" class="underline">{{ $item->name ?? '-' }}</a>
                            <br>
                            <small class="text-gray-500">— {{ $item->business_name ?? '-' }}</small>
                        </div>

                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ translate('CR Number') }}: {{ $item->cr_number ?? '-' }}<br>
                            <small class="text-gray-500">— {{ translate('ID Number') }}:
                                {{ $item->id_number ?? '-' }}</small><br>
                            <small class="text-gray-500">— {{ translate('ID/CR Match') }}:
                                {{ $item->cr_id_match_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('ID Expiry Score') }}:
                                {{ $item->id_expiry_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('CR Expiry Score') }}:
                                {{ $item->cr_expiry_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('Business Type') }}:
                                {{ $item->business_type_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('Activity Match') }}:
                                {{ $item->activity_score ?? 0 }}</small><br>
                            <small class="text-gray-500">— {{ translate('Total Raw') }}:
                                {{ $item->cr_id_total ?? 0 }}</small><br>
                            <small class="text-green-600 font-semibold">—
                                {{ translate('Weighted Score') }}:
                                {{ $item->cr_id_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ translate('Revenue') }}:
                            {{ number_format((float) ($item->pos_revenue ?? 0)) }}<br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->pos_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ translate('Late Payments') }}:
                            {{ $item->late_payments ?? 0 }}<br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->repayment_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ $item->industry ?? '-' }}<br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->industry_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ $item->location['city'] ?? '-' }}<br>
                            <small class="text-gray-500">
                                — {{ translate('Tier Score') }}:
                                {{ $item->location['tier_score'] ?? 0 }}<br>
                                — {{ translate('Activity') }}:
                                {{ $item->location['activity_score'] ?? 0 }}<br>
                                — {{ translate('Default Rate') }}:
                                {{ $item->location['default_rate_score'] ?? 0 }}
                            </small><br>
                            <small class="text-gray-500">— {{ translate('Risk Score') }}:
                                {{ $item->location_score ?? 0 }}</small>
                        </div>
                    </td>
                    <td>
                        {{ translate('Overall') }}
                        {{ $item->google_rating ?? 0 }}/5
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            @php
                                $score = $item->total_score ?? 0;
                                if ($score >= 80) {
                                    $riskLevel = translate('Low Risk');
                                    $badgeColor = 'badge badge-sm badge-outline badge-success';
                                } elseif ($score >= 60) {
                                    $riskLevel = translate('Medium Risk');
                                    $badgeColor = 'badge badge-sm badge-outline badge-warning';
                                } else {
                                    $riskLevel = translate('High Risk');
                                    $badgeColor = 'badge badge-sm badge-outline badge-danger';
                                }
                            @endphp
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $badgeColor }}">
                                {{ $score }}/100<br>- {{ $riskLevel }}
                            </span>
                        </div>
                    </td>


                    <td>
                        @if (Auth::user()->user_type == 'admin')
                            <div class="flex gap-1">
                                <!-- Existing Risk Score Edit Button -->
                                <a class="btn btn-sm btn-icon btn-clear btn-primary" data-modal-toggle="#score_modal"
                                    data-id="{{ $item->id }}"
                                    data-action="{{ route('risk.merchantScoreUpdate') }}"
                                    data-score="{{ $item->risk_score ?? '' }}" data-reason="{{ $item->reason ?? '' }}"
                                    title="{{ translate('Manual Score Adjustment') }}">
                                    <i class="ki-filled ki-notepad-edit"></i>
                                </a>

                                <!-- New Set Risk Weights Button -->
                                <a class="btn btn-sm btn-icon btn-clear btn-info"
                                    title="{{ translate('Set Risk Weights') }}" data-modal-toggle="#weight_modal"
                                    data-user-id="{{ $item->id }}">
                                    <i class="ki-filled ki-setting-4"></i>
                                </a>
                            </div>
                        @else
                            {{ translate('Unauthorized') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@include('layouts.includes.table-pagination', ['paginator' => $risks])

@push('styles')
    <style>
        /* Tooltip card (human-friendly styling) */
        .table-tooltip {
            position: absolute;
            z-index: 99999;
            max-width: 420px;
            background: #fff;
            color: #111827;
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.45;
            display: none;
            pointer-events: none;
        }

        .table-tooltip h4 {
            margin: 0 0 6px 0;
            font-size: 13px;
            font-weight: 700;
        }

        .table-tooltip p {
            margin: 6px 0;
        }

        .table-tooltip ul {
            margin: 6px 0;
            padding-left: 18px;
        }

        .table-tooltip ul li {
            margin-bottom: 4px;
        }

        .table-tooltip .muted {
            color: #6b7280;
            font-size: 12px;
        }

        th[data-tooltip-key] {
            position: relative;
            cursor: help;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ----------------------------
            // Tooltip content map (human-readable)
            // ----------------------------
            const tooltipContent = {
                'user-id': `<h4>User ID</h4>
                    <p>A unique database ID for the user. Used to link records and perform lookups.</p>`,

                'user-name': `<h4>User Name</h4>
                    <p>The user's full name. Click to open the user's profile page. Business name shown underneath.</p>`,

                'cr-id': `<h4>CR & ID Validation</h4>
                    <p>This column shows how we validate the user's company (CR) and owner's ID (Iqama).</p>
                    <ul>
                        <li><strong>ID Match</strong> — compare owner ID in CR vs stored iqama. (Weight: 30)</li>
                        <li><strong>ID Expiry</strong> — whether the iqama is expired or close to expiring. (Weight: 20)</li>
                        <li><strong>CR Expiry</strong> — whether the company registration is active or near expiry. (Weight: 20)</li>
                        <li><strong>Business Type</strong> — industry-based raw score. (Weight: 15)</li>
                        <li><strong>Activity Match</strong> — presence/number of registered activities. (Weight: 15)</li>
                    </ul>
                    <p class="muted">How it contributes: we add those component scores into a "raw" subtotal, scale that to a maximum of <strong>100</strong>, then apply the CR/ID weight (default <strong>25</strong>) to get the final CR/ID score shown.</p>`,

                'pos': `<h4>POS Revenue</h4>
                    <p>Reflects monthly point-of-sale revenue.</p>
                    <p class="muted">We compare the merchant's monthly POS to a threshold (default <strong>50,000</strong>). If they meet or exceed the threshold they get the full POS weight (default <strong>25</strong>); otherwise the score is prorated.</p>`,

                'repayment': `<h4>Repayment Delays</h4>
                    <p>Counts late payments and maps that to a repayment score.</p>
                    <ul>
                        <li><strong>0 late payments</strong> → best repayment score (default <strong>20</strong>).</li>
                        <li><strong>Few delays</strong> (default ≤ 2) → moderate score (default <strong>15</strong>).</li>
                        <li><strong>Many delays</strong> → low score (default <strong>5</strong>).</li>
                    </ul>`,

                'industry': `<h4>Industry Category</h4>
                    <p>Each industry has a risk level which is mapped to a numeric industry score.</p>
                    <ul>
                        <li><strong>low</strong> ⇒ 15</li>
                        <li><strong>medium-low</strong> ⇒ 12</li>
                        <li><strong>medium</strong> ⇒ 10</li>
                        <li><strong>high</strong> ⇒ 8</li>
                        <li><strong>very high</strong> ⇒ 5</li>
                    </ul>
                    <p class="muted">The industry score is included directly in the total score.</p>`,

                'location': `<h4>Location Risk</h4>
                    <p>Combines city tier, local business activity and the area's default rate.</p>
                    <ul>
                        <li><strong>Tier score</strong> — city-based value (e.g., Riyadh/Jeddah/Dammam = 6; Makkah/Madinah/Khobar = 4; default = 2).</li>
                        <li><strong>Activity score</strong> — higher where many businesses are present (defaults: 4.5 / 3 / 1).</li>
                        <li><strong>Default-rate score</strong> — better where local default rate is low (defaults: 4.5 / 2 / 0).</li>
                    </ul>
                    <p class="muted">Those parts are summed, scaled by the location weight (default <strong>10</strong>) and a subtotal cap (default <strong>15</strong>) to produce the location score shown.</p>`,

                'google': `<h4>Google Reviews</h4>
                    <p>Displays the cached Google rating (e.g. 4.2 / 5).</p>
                    <p class="muted">If a cached rating exists it is added directly into the total score. We do not make live API calls during page render — the rating must be cached beforehand.</p>`,

                'risk-score': `<h4>Risk Score (Total)</h4>
                    <p>The full risk score is the sum of the component scores: CR/ID score, POS score, Repayment score, Industry score, Location score, any manual adjustment, and Google rating (if present).</p>
                    <p class="muted">Displayed risk bands: <strong>≥80</strong> Low Risk, <strong>60–79.99</strong> Medium Risk, <strong>&lt;60</strong> High Risk.</p>`,

                'action': `<h4>Action</h4>
                    <p>Admin-only buttons:</p>
                    <ul>
                        <li><strong>Manual Score Adjustment</strong> — set a manual risk score and reason (overrides part of the calculation).</li>
                        <li><strong>Set Risk Weights</strong> — open the UI to change weights for this user (affects future calculations).</li>
                    </ul>`
            };

            // ----------------------------
            // Create tooltip element
            // ----------------------------
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'table-tooltip';
            tooltipEl.setAttribute('role', 'tooltip');
            document.body.appendChild(tooltipEl);

            let activeTarget = null;
            let hideTimeout = null;

            function showTooltip(target, html) {
                if (!html) return;
                tooltipEl.innerHTML = html;
                tooltipEl.style.display = 'block';
                tooltipEl.style.pointerEvents = 'none';

                // Position tooltip near target (above if space, otherwise below)
                const rect = target.getBoundingClientRect();
                const ttRect = tooltipEl.getBoundingClientRect();
                const margin = 8;
                let top = rect.top - ttRect.height - margin;
                let left = rect.left + (rect.width / 2) - (ttRect.width / 2);

                // If not enough space above, place below
                if (top < 6) {
                    top = rect.bottom + margin;
                }

                // Keep within viewport horizontally
                left = Math.max(6, Math.min(left, window.innerWidth - ttRect.width - 6));

                tooltipEl.style.top = `${top + window.scrollY}px`;
                tooltipEl.style.left = `${left + window.scrollX}px`;
            }

            function hideTooltip() {
                tooltipEl.style.display = 'none';
                activeTarget = null;
            }

            // Attach events to all th with data-tooltip-key
            const ths = document.querySelectorAll('th[data-tooltip-key]');
            ths.forEach(th => {
                const key = th.getAttribute('data-tooltip-key');
                const content = tooltipContent[key] || '';

                // mouse events
                th.addEventListener('mouseenter', (e) => {
                    if (hideTimeout) {
                        clearTimeout(hideTimeout);
                        hideTimeout = null;
                    }
                    activeTarget = th;
                    showTooltip(th, content);
                });
                th.addEventListener('mouseleave', (e) => {
                    hideTimeout = setTimeout(hideTooltip, 150);
                });

                // focus events (keyboard)
                th.setAttribute('tabindex', '0');
                th.addEventListener('focus', (e) => {
                    if (hideTimeout) {
                        clearTimeout(hideTimeout);
                        hideTimeout = null;
                    }
                    activeTarget = th;
                    showTooltip(th, content);
                });
                th.addEventListener('blur', (e) => {
                    hideTimeout = setTimeout(hideTooltip, 150);
                });
            });

            // Hide on scroll/resize
            window.addEventListener('scroll', () => {
                if (activeTarget) showTooltip(activeTarget, tooltipContent[activeTarget.getAttribute(
                    'data-tooltip-key')]);
            }, {
                passive: true
            });
            window.addEventListener('resize', hideTooltip);

            // ----------------------------
            // Existing modal-handling code (merged)
            // ----------------------------
            const scoreButtons = document.querySelectorAll('[data-modal-toggle="#score_modal"]');
            scoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('score_modal');
                    const form = modal.querySelector('form');
                    form.setAttribute('action', this.getAttribute('data-action'));
                    form.querySelector('#user_id').value = this.getAttribute('data-id');
                    form.querySelector('#risk_score').value = this.getAttribute('data-score') || '';
                    form.querySelector('#reason').value = this.getAttribute('data-reason') || '';
                });
            });

            const weightButtons = document.querySelectorAll('[data-modal-toggle="#weight_modal"]');
            weightButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.getElementById('weight_modal');
                    const userIdInput = modal.querySelector('input[name="user_id"]');
                    if (userIdInput) {
                        userIdInput.value = this.getAttribute('data-user-id');
                    }
                });
            });
        });
    </script>
@endpush
