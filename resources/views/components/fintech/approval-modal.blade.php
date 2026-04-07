@props([
    'id' => 'approval-modal',
    'title' => 'Confirm Action',
    'action' => '',
    'method' => 'POST',
    'entityLabel' => '',
    'amount' => null,
    'currency' => 'SAR',
    'confirmText' => 'Confirm',
    'confirmClass' => 'btn-primary',
    'requireReason' => false,
    'makerCheckerWarning' => null,
    'highValueThreshold' => 10000,
])

@php
    $isHighValue = $amount && \App\Helpers\Money::compare($amount, (string)$highValueThreshold) > 0;
@endphp

<div class="modal" data-modal="true" id="{{ $id }}">
    <div class="modal-content max-w-[500px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h3 class="modal-title font-semibold">{{ $title }}</h3>
            <button class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form action="{{ $action }}" method="POST">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="modal-body px-5 py-4">
                {{-- Maker-checker warning --}}
                @if($makerCheckerWarning)
                    <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 mb-4">
                        <i class="ki-filled ki-shield-tick text-amber-600 text-lg mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Segregation of Duties</p>
                            <p class="text-xs text-amber-700 mt-0.5">{{ $makerCheckerWarning }}</p>
                        </div>
                    </div>
                @endif

                {{-- Entity info --}}
                @if($entityLabel)
                    <p class="text-sm text-gray-600 mb-3">{{ $entityLabel }}</p>
                @endif

                {{-- Amount display --}}
                @if($amount)
                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 mb-4">
                        <span class="text-sm text-gray-500">Amount</span>
                        <x-fintech.money :amount="$amount" :currency="$currency" size="lg" />
                    </div>
                @endif

                {{-- High-value warning --}}
                @if($isHighValue)
                    <div class="flex items-start gap-2 p-3 rounded-lg bg-red-50 border border-red-200 mb-4">
                        <i class="ki-filled ki-shield-cross text-red-600 mt-0.5"></i>
                        <p class="text-xs text-red-700">High-value transaction. This action will be logged and audited.</p>
                    </div>
                @endif

                {{-- Reason field --}}
                @if($requireReason)
                    <div class="mb-3">
                        <label class="form-label text-sm font-medium">Reason <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="2" class="input w-full" required
                                  placeholder="Provide justification for this action..."></textarea>
                    </div>
                @endif

                {{-- Slot for additional content --}}
                {{ $slot }}
            </div>

            <div class="modal-footer px-5 py-3 flex justify-end gap-2">
                <button type="button" class="btn btn-light" data-modal-dismiss="true">Cancel</button>
                <button type="submit" class="btn {{ $confirmClass }}">
                    <i class="ki-filled ki-check mr-1"></i>
                    {{ $confirmText }}
                </button>
            </div>
        </form>
    </div>
</div>
