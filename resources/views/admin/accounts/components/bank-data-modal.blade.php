<div class="modal" data-modal="true" id="bank_data_modal">
    <div class="modal-content max-w-[600px] top-[10%]">
        <div class="modal-header py-4 px-6 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <h5 class="modal-title font-bold text-slate-800">{{ translate('Bank Accounts') }}</h5>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-6 bg-slate-50/50">
            <div class="space-y-4">
                @forelse($supplierBanks as $supplierBank)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-100 flex justify-between items-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                {{ translate('Account') }} #{{ $loop->iteration }}
                            </span>
                            <span class="text-xs font-bold text-blue-600">
                                {{ maskedSensitiveText('iban_bank_account', $supplierBank->bank_name ?? '-', 5, 3, 6) }}
                            </span>
                        </div>

                        <div class="p-4 grid grid-cols-1 gap-4">
                            <div class="flex flex-col">
                                <span
                                    class="text-[10px] text-slate-500 uppercase">{{ translate('Account Holder') }}</span>
                                <span class="text-sm font-semibold text-slate-800">
                                    {{ maskedSensitiveText(
                                        'authorized_person_name',
                                        trim(($supplierBank->user->first_name ?? '') . ' ' . ($supplierBank->user->last_name ?? '')),
                                    ) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[10px] text-slate-500 uppercase">{{ translate('Bank Account Name') }}</span>
                                    <span class="text-sm font-medium text-slate-700">
                                        {{ maskedSensitiveText('iban_bank_account', $supplierBank->account_name ?? '-', 5, 3, 6) }}
                                    </span>
                                </div>

                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-500 uppercase">{{ translate('IBAN') }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900 font-mono">
                                            {{ maskedSensitiveText('iban_bank_account', $supplierBank->iban ?? '-', 5, 3, 6) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 bg-white rounded-xl border border-dashed border-slate-300">
                        <i class="ki-outline ki-information-2 text-3xl text-slate-300 mb-2"></i>
                        <p class="text-sm text-slate-500">{{ translate('No bank details found') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
