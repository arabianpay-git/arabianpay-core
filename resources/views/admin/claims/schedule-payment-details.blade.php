{{-- Schedule Payment Details Modal Content --}}
<div style="max-height: 80vh; overflow-y: auto;">
    {{-- Payment Header --}}
    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background-color: #f8fafc;">
        <div style="display: flex; justify-content: between; align-items: start; gap: 20px;">
            <div style="flex: 1;">
                <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 18px; font-weight: 600;">
                    Payment Schedule #{{ $schedulePayment->id }}
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                    <div>
                        <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">CUSTOMER</span>
                        <span style="color: #1f2937; font-weight: 600;">{{ $schedulePayment->checkout->user->name }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">AMOUNT</span>
                        <span style="color: #1f2937; font-weight: 600;">${{ number_format($schedulePayment->amount, 2) }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">DUE DATE</span>
                        <span style="color: #1f2937; font-weight: 600;">{{ $schedulePayment->due_date->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">STATUS</span>
                        <span style="
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-size: 12px;
                            font-weight: 600;
                            {{ $schedulePayment->payment ? 'background-color: #dcfce7; color: #166534;' : ($schedulePayment->due_date->isPast() ? 'background-color: #fee2e2; color: #dc2626;' : 'background-color: #fef3c7; color: #d97706;') }}
                        ">
                            @if($schedulePayment->payment)
                                PAID
                            @elseif($schedulePayment->due_date->isPast())
                                OVERDUE
                            @else
                                PENDING
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Information --}}
    @if($schedulePayment->payment)
    <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
        <h4 style="margin: 0 0 15px 0; color: #1f2937; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
            <span style="width: 20px; height: 20px; background-color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                <svg style="width: 12px; height: 12px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </span>
            Payment Information
        </h4>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">PAYMENT DATE</span>
                <span style="color: #1f2937; font-weight: 600;">{{ $schedulePayment->payment->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">AMOUNT PAID</span>
                <span style="color: #1f2937; font-weight: 600;">${{ number_format($schedulePayment->payment->amount, 2) }}</span>
            </div>
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">PAYMENT METHOD</span>
                <span style="color: #1f2937; font-weight: 600;">{{ ucfirst($schedulePayment->payment->payment_method ?? 'N/A') }}</span>
            </div>
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">TRANSACTION ID</span>
                <span style="color: #1f2937; font-weight: 600; font-family: monospace; font-size: 14px;">{{ $schedulePayment->payment->transaction_id ?? 'N/A' }}</span>
            </div>
        </div>

        @if($schedulePayment->payment->notes)
        <div style="margin-top: 15px;">
            <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">PAYMENT NOTES</span>
            <div style="background-color: #f9fafb; padding: 12px; border-radius: 6px; border-left: 4px solid #10b981;">
                <span style="color: #1f2937;">{{ $schedulePayment->payment->notes }}</span>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Claims History --}}
    <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h4 style="margin: 0; color: #1f2937; font-size: 16px; font-weight: 600; display: flex; align-items: center;">
                <span style="width: 20px; height: 20px; background-color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <svg style="width: 12px; height: 12px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                </span>
                Claims History
                <span style="background-color: #e5e7eb; color: #374151; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 8px;">
                    {{ $schedulePayment->claims->count() }}
                </span>
            </h4>
            
            @if(!$schedulePayment->payment && $schedulePayment->claims->count() > 0)
            <button onclick="createClaim({{ $schedulePayment->id }}, {{ $schedulePayment->checkout->user_id }}, 'call')" 
                style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                + New Claim
            </button>
            @endif
        </div>

        @if($schedulePayment->claims->count() > 0)
            <div style="space-y: 12px;">
                @foreach($schedulePayment->claims->sortByDesc('created_at') as $claim)
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 12px; background-color: {{ $claim->claim_status === 'resolved' ? '#f0fdf4' : ($claim->claim_status === 'failed' ? '#fef2f2' : '#ffffff') }};">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="
                                padding: 3px 8px;
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 600;
                                text-transform: uppercase;
                                {{ $claim->claim_type === 'call' ? 'background-color: #dbeafe; color: #1e40af;' : ($claim->claim_type === 'sms' ? 'background-color: #fef3c7; color: #d97706;' : 'background-color: #e0e7ff; color: #4338ca;') }}
                            ">
                                {{ $claim->claim_type }}
                            </span>
                            
                            <span style="
                                padding: 3px 8px;
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 600;
                                text-transform: uppercase;
                                @switch($claim->claim_status)
                                    @case('resolved')
                                        background-color: #dcfce7; color: #166534;
                                        @break
                                    @case('failed')
                                        background-color: #fee2e2; color: #dc2626;
                                        @break
                                    @case('contacted')
                                        background-color: #dbeafe; color: #1e40af;
                                        @break
                                    @case('promised')
                                        background-color: #fef3c7; color: #d97706;
                                        @break
                                    @default
                                        background-color: #f3f4f6; color: #374151;
                                @endswitch
                            ">
                                {{ str_replace('_', ' ', $claim->claim_status) }}
                            </span>

                            <span style="
                                padding: 3px 8px;
                                border-radius: 12px;
                                font-size: 11px;
                                font-weight: 600;
                                text-transform: uppercase;
                                {{ $claim->priority === 'urgent' ? 'background-color: #fee2e2; color: #dc2626;' : ($claim->priority === 'high' ? 'background-color: #fed7d7; color: #c53030;' : ($claim->priority === 'medium' ? 'background-color: #fef3c7; color: #d97706;' : 'background-color: #e5e7eb; color: #374151;')) }}
                            ">
                                {{ $claim->priority }}
                            </span>
                        </div>
                        
                        <span style="font-size: 12px; color: #6b7280;">
                            {{ $claim->created_at->format('M d, Y H:i') }}
                        </span>
                    </div>

                    @if($claim->notes)
                    <div style="margin-bottom: 10px;">
                        <p style="margin: 0; color: #374151; font-size: 14px; line-height: 1.5;">{{ $claim->notes }}</p>
                    </div>
                    @endif

                    @if($claim->customer_response)
                    <div style="margin-bottom: 8px;">
                        <span style="font-size: 12px; color: #6b7280; font-weight: 500;">Customer Response: </span>
                        <span style="color: #1f2937; font-weight: 500;">{{ str_replace('_', ' ', ucfirst($claim->customer_response)) }}</span>
                    </div>
                    @endif

                    @if($claim->customer_reason)
                    <div style="margin-bottom: 8px;">
                        <span style="font-size: 12px; color: #6b7280; font-weight: 500;">Customer Reason: </span>
                        <span style="color: #1f2937;">{{ $claim->customer_reason }}</span>
                    </div>
                    @endif

                    @if($claim->promised_amount || $claim->promised_payment_date)
                    <div style="background-color: #fffbeb; border: 1px solid #fed7aa; border-radius: 6px; padding: 10px; margin-bottom: 8px;">
                        <span style="font-size: 12px; color: #92400e; font-weight: 600;">PROMISE DETAILS:</span>
                        <div style="margin-top: 4px;">
                            @if($claim->promised_amount)
                                <span style="font-size: 12px; color: #92400e;">Amount: ${{ number_format($claim->promised_amount, 2) }}</span>
                            @endif
                            @if($claim->promised_payment_date)
                                <span style="font-size: 12px; color: #92400e; margin-left: 15px;">Date: {{ \Carbon\Carbon::parse($claim->promised_payment_date)->format('M d, Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($claim->assignedTo)
                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280;">Handled by:</span>
                        <span style="color: #1f2937; font-weight: 500; font-size: 12px;">{{ $claim->assignedTo->name }}</span>
                        
                        @if($claim->next_follow_up)
                        <span style="margin-left: auto; font-size: 12px; color: #6b7280;">Next follow-up: {{ \Carbon\Carbon::parse($claim->next_follow_up)->format('M d, Y H:i') }}</span>
                        @endif
                    </div>
                    @endif

                    @if($claim->requires_escalation)
                    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 8px; margin-top: 8px;">
                        <span style="font-size: 12px; color: #dc2626; font-weight: 600;">⚠️ REQUIRES ESCALATION</span>
                        @if($claim->escalation_reason)
                            <p style="margin: 4px 0 0 0; font-size: 12px; color: #dc2626;">{{ $claim->escalation_reason }}</p>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                <svg style="width: 48px; height: 48px; margin: 0 auto 12px; color: #d1d5db;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p style="margin: 0; font-size: 14px;">No claims recorded for this payment schedule.</p>
                @if(!$schedulePayment->payment)
                <button onclick="createClaim({{ $schedulePayment->id }}, {{ $schedulePayment->checkout->user_id }}, 'call')" 
                    style="margin-top: 12px; padding: 8px 16px; background-color: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
                    Create First Claim
                </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Investment Pool Info --}}
    @if($schedulePayment->checkout->investmentPool)
    <div style="padding: 20px; border-top: 1px solid #e5e7eb; background-color: #f8fafc;">
        <h5 style="margin: 0 0 10px 0; color: #1f2937; font-size: 14px; font-weight: 600;">Investment Pool Information</h5>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">POOL NAME</span>
                <span style="color: #1f2937; font-weight: 600; font-size: 13px;">{{ $schedulePayment->checkout->investmentPool->name }}</span>
            </div>
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">POOL PERIOD</span>
                <span style="color: #1f2937; font-weight: 600; font-size: 13px;">
                    {{ $schedulePayment->checkout->investmentPool->start_date->format('M d') }} - 
                    {{ $schedulePayment->checkout->investmentPool->end_date->format('M d, Y') }}
                </span>
            </div>
            <div>
                <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px;">CHECKOUT DATE</span>
                <span style="color: #1f2937; font-weight: 600; font-size: 13px;">{{ $schedulePayment->checkout->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>
    @endif
</div>