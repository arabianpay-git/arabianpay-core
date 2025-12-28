@php
    $isVatRegistered = !empty($merchant->vat_register_file) && $merchant->vat_register_file !== 'null';
    $vatFileUrl = $isVatRegistered ? supplierMedia($merchant->vat_register_file) : null;

    $isCrRegistered = !empty($merchant->registration_number_form) && $merchant->registration_number_form !== 'null';
    $crFileUrl = $isCrRegistered ? supplierMedia($merchant->registration_number_form) : null;

    $isBaladyRegistered = !empty($merchant->balady_certificate) && $merchant->balady_certificate !== 'null';
    $baladyFileUrl = $isBaladyRegistered ? supplierMedia($merchant->balady_certificate) : null;

    $isContractSent = !empty($merchant->approval) && $merchant->approval !== 'null';
    $contractFileUrl = $isContractSent ? supplierMedia($merchant->approval->contract) : null;
@endphp

<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
    <h3 class="font-bold text-slate-800 mb-4">{{ translate('Compliance & Risk') }}</h3>
    <div class="space-y-3">
        <!-- CR Status -->
        <div
            @if ($isCrRegistered) onclick="window.open('{{ $crFileUrl }}', '_blank')"
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-pointer hover:bg-slate-50 transition"
            @else
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-not-allowed opacity-70" @endif>
            <div class="flex items-center gap-3">
                <i
                    class="ki-outline  
                    {{ $isCrRegistered ? 'text-emerald-500 ki-verify' : 'text-amber-500 ki-cross-circle' }} text-lg">
                </i>
                <span class="text-xs font-bold">{{ translate('CR Registered') }}</span>
            </div>

            <span
                class="text-[10px] font-bold uppercase
                {{ $isCrRegistered ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $isCrRegistered ? translate('Registered') : translate('Not Registered') }}
            </span>
        </div>


        <!-- VAT Status -->
        <div
            @if ($isVatRegistered) onclick="window.open('{{ $vatFileUrl }}', '_blank')"
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-pointer hover:bg-slate-50 transition"
            @else
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-not-allowed opacity-70" @endif>
            <div class="flex items-center gap-3">
                <i
                    class="ki-outline 
                    {{ $isVatRegistered ? 'text-emerald-500 ki-verify' : 'text-amber-500 ki-cross-circle' }} text-lg">
                </i>
                <span class="text-xs font-bold">{{ translate('VAT Registered') }}</span>
            </div>

            <span
                class="text-[10px] font-bold uppercase
                {{ $isVatRegistered ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $isVatRegistered ? translate('Registered') : translate('Not Registered') }}
            </span>
        </div>

        <!-- Bank Verified -->
        <div class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-pointer"
            data-modal-toggle="#bank_data_modal">
            <div class="flex items-center gap-3">
                <i class="ki-outline ki-bank text-emerald-500 text-lg"></i>
                <span class="text-xs font-bold">{{ translate('Bank Verified') }}</span>
            </div>
            <span
                class="text-[10px] text-blue-600 font-bold uppercase
                {{ $supplierBanks->isNotEmpty() ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $supplierBanks->isNotEmpty() ? translate('Verified') : translate('Pending') }}
            </span>
        </div>

        @include('admin.accounts.components.bank-data-modal')

        <!-- Balady Status -->
        <div
            @if ($isBaladyRegistered) onclick="window.open('{{ $baladyFileUrl }}', '_blank')"
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-pointer hover:bg-slate-50 transition"
            @else
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-not-allowed opacity-70" @endif>
            <div class="flex items-center gap-3">
                <i
                    class="ki-outline  
                    {{ $isBaladyRegistered ? 'text-emerald-500 ki-verify' : 'text-amber-500 ki-cross-circle' }} text-lg">
                </i>
                <span class="text-xs font-bold">{{ translate('Balady Registered') }}</span>
            </div>

            <span
                class="text-[10px] font-bold uppercase
                {{ $isBaladyRegistered ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $isBaladyRegistered ? translate('Registered') : translate('Not Registered') }}
            </span>
        </div>

        <!-- Contract Status -->
        <div
            @if ($isContractSent) onclick="window.open('{{ $contractFileUrl }}', '_blank')"
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-pointer hover:bg-slate-50 transition"
            @else
                class="flex items-center justify-between p-2 border border-slate-100 rounded-lg cursor-not-allowed opacity-70" @endif>
            <div class="flex items-center gap-3">
                <i
                    class="ki-outline  
                    {{ $isContractSent ? 'text-emerald-500 ki-verify' : 'text-amber-500 ki-cross-circle' }} text-lg">
                </i>
                <span class="text-xs font-bold">{{ translate('Contract Submitted') }}</span>
            </div>

            <span
                class="text-[10px] font-bold uppercase
                {{ $isContractSent ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $isContractSent ? translate('Submitted') : translate('Not Submitted') }}
            </span>
        </div>
    </div>
</div>
