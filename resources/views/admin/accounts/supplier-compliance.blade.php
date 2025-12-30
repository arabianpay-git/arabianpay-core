@extends('layouts.base')

@section('content')
    <main class="w-full max-w-[1400px] mx-auto p-6 space-y-6 bg-slate-50">

        @include('admin.accounts.includes.supplier-profile-header')
        @include('admin.accounts.includes.supplier-nav')

        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 space-y-6">
                <div class="card border-none shadow-sm overflow-hidden">
                    <div class="card-header bg-white border-b border-slate-100 py-5">
                        <h3 class="card-title text-slate-900 font-bold text-xl">{{ translate('Compliance Documents') }}</h3>
                    </div>

                    <div class="card-body p-6 bg-white space-y-4">
                        @php
                            $approvals = App\Models\Approval::where('user_id', $merchant->user_id)->get();
                            $contract = $approvals->first();

                            $compliance = [];
                            foreach ($approvals as $approval) {
                                $compliance[] = [
                                    'title' => translate('Supplier Contract'),
                                    // store the raw contract path (Approval->contract) here
                                    'file' => $approval->contract,
                                    'number' => null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'is_contract' => true,
                                    'document_type' => 'contract',
                                ];
                            }

                            $fixedDocuments = [
                                [
                                    'title' => translate('CR File'),
                                    'file' => supplierMedia($merchant->registration_number_form),
                                    'number' => $merchant->cr_number ?? null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'cr_file',
                                ],
                                [
                                    'title' => translate('VAT Register File'),
                                    'file' => supplierMedia($merchant->vat_register_file),
                                    'number' => $merchant->vat_register_number ?? null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'vat_file',
                                ],
                                [
                                    'title' => translate('Return Policy File'),
                                    'file' => supplierMedia($merchant->return_policy_file),
                                    'number' => isset($merchant->return_day_count)
                                        ? $merchant->return_day_count . ' ' . translate('Days')
                                        : null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'return_policy_file',
                                ],
                                [
                                    'title' => translate('Delivery Policy File'),
                                    'file' => supplierMedia($merchant->exchange_policy_file),
                                    'number' => isset($merchant->exchange_day_count)
                                        ? $merchant->exchange_day_count . ' ' . translate('Days')
                                        : null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'exchange_policy_file',
                                ],
                                [
                                    'title' => translate('Cancel Policy File'),
                                    'file' => supplierMedia($merchant->cancel_policy_file),
                                    'number' => isset($merchant->cancel_day_count)
                                        ? $merchant->cancel_day_count . ' ' . translate('Days')
                                        : null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'cancel_policy_file',
                                ],
                                [
                                    'title' => translate('ID Image'),
                                    'file' => supplierMedia($merchant->owner_iqama_image),
                                    'number' => $merchant->owner_iqama_number ?? null,
                                    'permission' => 'national_id_iqama',
                                    'document_type' => 'id_image',
                                ],
                                [
                                    'title' => translate('Balady Certificate'),
                                    'file' => supplierMedia($merchant->balady_certificate),
                                    'number' => null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'balady_certificate',
                                ],
                            ];

                            if ($merchant->user->is_manager) {
                                $fixedDocuments[] = [
                                    'title' => translate('Company Approval Letter for Manager'),
                                    'file' => supplierMedia($merchant->manager_approval),
                                    'number' => null,
                                    'permission' => 'documents_id_cr_contracts',
                                    'document_type' => 'manager_approval',
                                ];
                            }

                            $compliance = array_merge($compliance, $fixedDocuments);

                            $mainUserId = $merchant->user->main_user_id ?: $merchant->user_id;
                            $relatedUserIds = \App\Models\User::where('id', $mainUserId)
                                ->orWhere('main_user_id', $mainUserId)
                                ->pluck('id');
                            $ibanBanks = App\Models\SupplierBank::whereIn('user_id', $relatedUserIds)->get();
                        @endphp

                        @foreach ($compliance as $item)
                            <div
                                class="group flex flex-col md:flex-row md:items-center justify-between p-5 rounded-2xl border border-slate-200 bg-white hover:border-blue-300 hover:shadow-md transition-all duration-300 gap-4">
                                <div class="flex items-start gap-4">
                                    {{-- Icon Column --}}
                                    <div class="mt-1">
                                        @if ($item['file'])
                                            <span
                                                class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-50 text-emerald-600">
                                                <i class="ki-outline ki-check-circle text-xl"></i>
                                            </span>
                                        @else
                                            <span
                                                class="flex items-center justify-center w-10 h-10 rounded-full bg-rose-50 text-rose-600">
                                                <i class="ki-outline ki-cross-circle text-xl"></i>
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Content Column --}}
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-3">
                                            {{-- Title --}}
                                            <h4 class="text-base font-bold text-slate-900 leading-tight">
                                                {{ $item['title'] }}
                                            </h4>

                                            {{-- Status Badge --}}
                                            <span
                                                class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-lg {{ $item['file'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $item['file'] ? translate('Compliant') : translate('Missing') }}
                                            </span>
                                        </div>

                                        {{-- Number --}}
                                        @if ($item['number'])
                                            <div class="flex items-center">
                                                <span
                                                    class="text-xs font-mono font-semibold text-slate-500 bg-slate-50 px-2 py-1 rounded border border-slate-200/60">
                                                    <span
                                                        class="opacity-50 mr-0.5">#</span>{{ maskedSensitiveText($item['permission'], $item['number'], 3, 4) }}
                                                </span>
                                            </div>
                                        @endif

                                        {{-- Contract Dates --}}
                                        @if (isset($item['is_contract']) && $contract)
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                                                @if ($contract->created_at)
                                                    <p class="text-[11px] text-slate-500 flex items-center gap-1">
                                                        <i class="ki-outline ki-calendar text-xs"></i>
                                                        {{ translate('Start') }}:
                                                        {{ maskedSensitiveText($item['permission'], $contract->created_at->format('M d, Y'), 3, 5) }}
                                                    </p>
                                                @endif
                                                @if ($contract->contract_end_date)
                                                    <p class="text-[11px] text-slate-500 flex items-center gap-1">
                                                        <i class="ki-outline ki-calendar-remove text-xs"></i>
                                                        {{ translate('Expiry') }}:
                                                        {{ maskedSensitiveText($item['permission'], \Carbon\Carbon::parse($contract->contract_end_date)->format('M d, Y'), 3, 5) }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @php
                                        $authPath = null;
                                        if (!empty($item['file'])) {
                                            $authPath = authorizeSensitiveFileOrDeny(
                                                $item['permission'],
                                                $item['file'],
                                                null,
                                            );
                                        }
                                    @endphp

                                    @if ($authPath)
                                        <a href="{{ asset($authPath) }}" target="_blank"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-blue-600 transition-colors shadow-sm">
                                            <i class="ki-outline ki-eye"></i> {{ translate('View') }}
                                        </a>
                                    @elseif ($item['file'])
                                        <span
                                            class="flex items-center gap-1.5 text-amber-600 text-xs font-medium bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-100">
                                            <i class="ki-outline ki-lock"></i> {{ translate('Access Restricted') }}
                                        </span>
                                    @endif

                                    <button type="button"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-blue-900 rounded-xl hover:bg-blue-600 transition-colors shadow-sm update-document-btn"
                                        data-modal-toggle="#documentUpdateModal"
                                        data-document-type="{{ $item['document_type'] }}"
                                        data-merchant-id="{{ $merchant->id }}"
                                        data-existing-file="{{ $authPath ? asset($authPath) : '' }}"
                                        @if (isset($item['is_contract']) && $item['is_contract']) data-is-contract="true" @endif>
                                        <i class="ki-outline ki-exit-up"></i>
                                        {{ $item['file'] ? translate('Update') : translate('Upload') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        <div class="mt-10">
                            <h4 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <i class="ki-outline ki-bank text-2xl text-slate-400"></i>
                                {{ translate('IBAN Certificates') }}
                            </h4>

                            @if ($ibanBanks->isEmpty())
                                <div
                                    class="p-10 text-center border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50">
                                    <p class="text-slate-500 text-sm font-medium">
                                        {{ translate('No IBAN Certificates have been uploaded yet.') }}</p>
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                    @foreach ($relatedUserIds as $userId)
                                        @php
                                            $user = \App\Models\User::find($userId);
                                            $userBanks = $ibanBanks->where('user_id', $userId);
                                            $hasAnyCert = $userBanks->contains(fn($b) => !empty($b->iban_certificate));
                                        @endphp

                                        <div
                                            class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                                            <div class="flex justify-between items-start mb-4">
                                                <div>
                                                    <h5 class="text-sm font-bold text-slate-900">
                                                        {{ maskedSensitiveText('authorized_person_name', trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) }}
                                                    </h5>
                                                    <p
                                                        class="text-[10px] text-slate-500 uppercase font-bold tracking-tight">
                                                        {{ $user->business_name ?? translate('N/A') }}</p>
                                                </div>
                                                <span
                                                    class="text-[9px] font-black px-2 py-0.5 rounded-md uppercase {{ $hasAnyCert ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                    {{ $hasAnyCert ? translate('Valid') : translate('Missing') }}
                                                </span>
                                            </div>

                                            <div class="space-y-3">
                                                @foreach ($userBanks as $bank)
                                                    <div
                                                        class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                                        <div class="overflow-hidden">
                                                            <p class="text-[10px] text-slate-400 font-bold uppercase">
                                                                {{ translate('IBAN') }}
                                                            </p>
                                                            <p class="text-xs font-mono font-bold text-slate-700">
                                                                <span
                                                                    id="iban-text">{{ $bank->iban ? maskedSensitiveText('iban_bank_account', $bank->iban, 5, 3) : 'N/A' }}</span>
                                                                <i id="iban-copy-icon"
                                                                    class="ki-filled ki-copy cursor-pointer text-lg"></i>
                                                            </p>
                                                        </div>

                                                        <div class="flex items-center gap-2">
                                                            @php
                                                                $authIban = null;
                                                                if (!empty($bank->iban_certificate)) {
                                                                    $authIban = authorizeSensitiveFileOrDeny(
                                                                        'iban_bank_account',
                                                                        supplierMedia($bank->iban_certificate),
                                                                        null,
                                                                    );
                                                                }
                                                            @endphp

                                                            @if ($authIban)
                                                                <a href="{{ asset($authIban) }}" target="_blank"
                                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-blue-600 transition-colors shadow-sm">
                                                                    <i class="ki-outline ki-eye"></i>
                                                                    {{ translate('View') }}
                                                                </a>
                                                            @endif

                                                            <button type="button"
                                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-blue-900 rounded-xl hover:bg-blue-600 transition-colors shadow-sm update-document-btn"
                                                                data-modal-toggle="#documentUpdateModal"
                                                                data-document-type="iban_certificate"
                                                                data-merchant-id="{{ $merchant->id }}"
                                                                data-bank-id="{{ $bank->id }}"
                                                                data-existing-file="{{ $authIban ? asset($authIban) : '' }}"
                                                                data-bank-name="{{ $bank->bank_name }}"
                                                                data-account-name="{{ $bank->account_name }}"
                                                                data-iban="{{ $bank->iban }}">
                                                                <i class="ki-outline ki-exit-up"></i>
                                                                {{ translate('Update') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Document Update Modal -->
    <div class="modal" data-modal="true" id="documentUpdateModal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title text-lg font-bold text-slate-900" id="documentUpdateModalLabel">
                    {{ translate('Update Document') }}
                </h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <form id="documentUpdateForm" method="POST" enctype="multipart/form-data" class="px-5">
                    @csrf
                    <div class="py-5">
                        <input type="hidden" name="merchant_id" id="merchant_id">
                        <input type="hidden" name="document_type" id="document_type">
                        <input type="hidden" name="bank_id" id="bank_id">

                        <!-- IBAN Bank Fields -->
                        <div id="ibanFields" class="space-y-4 hidden">
                            <!-- Bank Name -->
                            <div class="flex flex-col gap-2.5">
                                <label class="form-label flex items-center gap-1">
                                    {{ translate('Bank Name') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <select name="bank_name" id="bank_name_select" class="input">
                                    <option value="">{{ translate('Select Bank') }}</option>
                                    @php
                                        $saudiBanks = [
                                            'Al Rajhi Bank',
                                            'National Commercial Bank (NCB)',
                                            'Samba Financial Group',
                                            'Riyad Bank',
                                            'Banque Saudi Fransi',
                                            'Alinma Bank',
                                            'Arab National Bank',
                                            'The Saudi British Bank (SABB)',
                                            'Gulf International Bank',
                                            'Bank Aljazira',
                                            'Saudi Investment Bank',
                                            'Alawwal Bank',
                                            'Bank Albilad',
                                            'Eastern Province Bank',
                                        ];
                                    @endphp
                                    @foreach ($saudiBanks as $bank)
                                        <option value="{{ $bank }}">{{ translate($bank) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Account Name -->
                            <div class="flex flex-col gap-2.5">
                                <label class="form-label flex items-center gap-1">
                                    {{ translate('Account Name') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="account_name" id="account_name_input" class="input"
                                    required>
                            </div>

                            <!-- IBAN -->
                            <div class="flex flex-col gap-2.5">
                                <label class="form-label flex items-center gap-1">
                                    {{ translate('IBAN') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="iban" id="iban_input" class="input"
                                    placeholder="{{ translate('Enter a valid IBAN, e.g. SA00 0000 0000 0000 0000 0000') }}"
                                    maxlength="34" required pattern="[A-Za-z]{2}[0-9]{2}([ ]?[A-Za-z0-9]{4}){1,7}">
                                </p>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label for="file" class="block text-sm font-medium text-slate-700 mb-2">
                                {{ translate('Select File') }}
                            </label>
                            <div class="flex items-center w-full max-w-md relative">
                                <!-- Hidden file input -->
                                <input type="file" name="file" id="file_input" class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">

                                <!-- Button to trigger file input -->
                                <button type="button"
                                    class="absolute top-0 bottom-0 left-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-l z-10"
                                    id="selectFileBtn">
                                    <i class="ki-filled ki-folder text-xl"></i>
                                </button>

                                <!-- Readonly input to show selected filename -->
                                <input type="text" id="file_name_display" class="input w-full pl-12 cursor-pointer"
                                    placeholder="Click to select media" readonly style="padding-inline-start: 2.75rem;">
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ translate('Allowed formats: PDF, JPG, PNG, DOC, DOCX. Max size: 5MB') }}
                            </p>

                            <!-- File info display (used for selected file OR existing contract preview) -->
                            <div id="file_info_display" class="mt-2 hidden">
                                <div
                                    class="flex items-center justify-between bg-slate-50 p-3 rounded-lg border border-slate-200">
                                    <div class="flex items-center gap-3">
                                        <i class="ki-outline ki-file text-xl text-blue-600"></i>
                                        <div>
                                            <p class="text-sm font-medium text-slate-700" id="selected_file_name"></p>
                                            <p class="text-xs text-slate-500" id="selected_file_size"></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="#" id="currentFileViewLink"
                                            class="hidden p-2 bg-white rounded-lg shadow-sm text-slate-900 hover:text-blue-600 border border-slate-200 transition-all"
                                            target="_blank">
                                            <i class="ki-outline ki-eye text-lg"></i>
                                        </a>

                                        <button type="button" class="text-red-500 hover:text-red-700 transition-colors"
                                            id="clear_file_btn">
                                            <i class="ki-outline ki-cross text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Specific Fields -->
                        <div id="contractFields" class="space-y-4 hidden">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="commission" class="block text-sm font-medium text-slate-700 mb-2">
                                        {{ translate('Commission %') }}
                                    </label>
                                    <input type="number" name="commission" id="commission" class="input"
                                        step="0.01" min="0" max="100" placeholder="e.g., 15.5">
                                </div>
                                <div>
                                    <label for="contract_end_date" class="block text-sm font-medium text-slate-700 mb-2">
                                        {{ translate('Contract End Date') }}
                                    </label>
                                    <input type="text" name="contract_end_date" id="contract_end_date"
                                        class="input flatpickr" placeholder="{{ translate('Select date...') }}"
                                        data-input>
                                </div>
                            </div>

                            <div id="currentContractInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hidden">
                                <div class="flex items-start gap-3">
                                    <i class="ki-outline ki-information text-xl text-blue-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">
                                            {{ translate('Current Contract Details') }}
                                        </p>
                                        <p class="text-xs text-blue-600 mt-1">
                                            {{ translate('Commission') }}: <span id="currentCommission"></span>%<br>
                                            {{ translate('Expiry Date') }}: <span id="currentExpiryDate"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="loadingIndicator" class="hidden">
                            <div class="flex items-center justify-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                <span
                                    class="ml-3 text-sm text-slate-600">{{ translate('Loading contract details...') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer py-4 px-5 border-t border-slate-200 flex justify-end gap-2">
                        <button type="button" class="btn btn-sm btn-light" data-modal-dismiss="true">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" id="submitBtn">
                            {{ translate('Upload Document') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .flatpickr-input {
            background-color: white !important;
        }

        .flatpickr-calendar {
            z-index: 99999 !important;
        }

        .input {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            outline: none;
            transition: border-color 0.15s ease-in-out;
        }

        .input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .hover\:bg-primary-light:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        .hover\:text-primary:hover {
            color: #3b82f6;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentDocumentType = '';
            let currentMerchantId = '';
            let flatpickrInstance = null;
            let hasExistingContract = false; // tracks if there's already a contract
            let currentExistingFile = null; // tracks any existing file URL for preview (all docs)

            // Initialize flatpickr
            function initFlatpickr() {
                if (flatpickrInstance) {
                    flatpickrInstance.destroy();
                }

                flatpickrInstance = flatpickr("#contract_end_date", {
                    dateFormat: "Y-m-d",
                    minDate: "today",
                    locale: {
                        firstDayOfWeek: 0
                    },
                    allowInput: true
                });
            }

            // File input functionality
            const fileInput = document.getElementById('file_input');
            const fileNameDisplay = document.getElementById('file_name_display');
            const fileInfoDisplay = document.getElementById('file_info_display');
            const selectedFileName = document.getElementById('selected_file_name');
            const selectedFileSize = document.getElementById('selected_file_size');
            const selectFileBtn = document.getElementById('selectFileBtn');
            const clearFileBtn = document.getElementById('clear_file_btn');
            const currentFileViewLink = document.getElementById('currentFileViewLink');

            // Click on the button or input to trigger file selection
            if (selectFileBtn) {
                selectFileBtn.addEventListener('click', function() {
                    fileInput.click();
                });
            }

            if (fileNameDisplay) {
                fileNameDisplay.addEventListener('click', function() {
                    fileInput.click();
                });
            }

            // Handle file selection
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        const file = this.files[0];
                        const fileName = file.name;
                        const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB

                        // Update display
                        fileNameDisplay.value = fileName;
                        selectedFileName.textContent = fileName;
                        selectedFileSize.textContent = `${fileSize} MB`;

                        // Show file info section and view link is hidden (new file, not existing)
                        fileInfoDisplay.classList.remove('hidden');
                        if (currentFileViewLink) currentFileViewLink.classList.add('hidden');

                        // Clear currentExistingFile because user selected a new file
                        currentExistingFile = null;
                    }
                });
            }

            // Clear file selection
            if (clearFileBtn) {
                clearFileBtn.addEventListener('click', function() {
                    clearFileSelection();
                });
            }

            function clearFileSelection() {
                if (fileInput) {
                    fileInput.value = '';
                    fileNameDisplay.value = '';
                    fileInfoDisplay.classList.add('hidden');
                    selectedFileName.textContent = '';
                    selectedFileSize.textContent = '';
                    if (currentFileViewLink) currentFileViewLink.classList.add('hidden');
                    currentExistingFile = null;
                }
            }

            // Handle document update button click
            document.querySelectorAll('.update-document-btn').forEach(button => {
                button.addEventListener('click', function() {
                    currentDocumentType = this.getAttribute('data-document-type');
                    currentMerchantId = this.getAttribute('data-merchant-id');
                    const isContract = this.getAttribute('data-is-contract') === 'true' || false;
                    const bankId = this.getAttribute('data-bank-id') || null;
                    const existingFileUrl = this.getAttribute('data-existing-file') || null;

                    // IBAN specific data
                    const bankName = this.getAttribute('data-bank-name') || '';
                    const accountName = this.getAttribute('data-account-name') || '';
                    const iban = this.getAttribute('data-iban') || '';

                    // Reset form
                    const form = document.getElementById('documentUpdateForm');
                    form.reset();
                    clearFileSelection();

                    // Hide all field groups
                    document.getElementById('contractFields').classList.add('hidden');
                    document.getElementById('ibanFields').classList.add('hidden');
                    document.getElementById('currentContractInfo').classList.add('hidden');
                    document.getElementById('loadingIndicator').classList.add('hidden');

                    // Reset flags
                    hasExistingContract = false;
                    currentExistingFile = null;

                    // Set form values
                    document.getElementById('merchant_id').value = currentMerchantId;
                    document.getElementById('document_type').value = currentDocumentType;
                    document.getElementById('bank_id').value = bankId;

                    // Set form action
                    form.action = '{{ route('supplier.update-document', ['id' => ':id']) }}'
                        .replace(':id', currentMerchantId);

                    // Update modal title and show appropriate fields based on document type
                    let modalTitle = '';
                    switch (currentDocumentType) {
                        case 'contract':
                            modalTitle = '{{ translate('Update Supplier Contract') }}';
                            document.getElementById('contractFields').classList.remove('hidden');
                            initFlatpickr(); // Initialize flatpickr for contract date
                            // Always fetch contract metadata (commission / end date)
                            loadContractDetails();
                            // also use existingFileUrl (if present) to show preview immediately
                            if (existingFileUrl) {
                                showExistingFilePreview(existingFileUrl);
                                hasExistingContract = true;
                                currentExistingFile = existingFileUrl;
                            }
                            break;

                        case 'iban_certificate':
                            modalTitle = '{{ translate('Update IBAN Certificate') }}';
                            document.getElementById('ibanFields').classList.remove('hidden');

                            // Pre-fill IBAN fields
                            if (bankName) {
                                document.getElementById('bank_name_select').value = bankName;
                            }
                            if (accountName) {
                                document.getElementById('account_name_input').value = accountName;
                            }
                            if (iban) {
                                document.getElementById('iban_input').value = iban;
                            }

                            // Show existing file preview if exists
                            if (existingFileUrl) {
                                showExistingFilePreview(existingFileUrl);
                                currentExistingFile = existingFileUrl;
                            }
                            break;

                        default:
                            modalTitle = '{{ translate('Update Document') }}';
                            // For non-contracts, if a file exists show preview (no fetch needed)
                            if (existingFileUrl) {
                                showExistingFilePreview(existingFileUrl);
                                currentExistingFile = existingFileUrl;
                            }
                    }

                    document.getElementById('documentUpdateModalLabel').textContent = modalTitle;
                });
            });

            // Helper: show existing file preview in modal
            function showExistingFilePreview(url) {
                if (!url) return;
                // Extract filename for display
                const parts = url.split('/');
                const filename = parts[parts.length - 1] || url;

                selectedFileName.textContent = filename;
                selectedFileSize.textContent = '{{ translate('Existing file') }}';
                fileInfoDisplay.classList.remove('hidden');

                if (currentFileViewLink) {
                    currentFileViewLink.href = url;
                    currentFileViewLink.classList.remove('hidden');
                }
            }

            // Load contract details if exists (unchanged)
            function loadContractDetails() {
                document.getElementById('loadingIndicator').classList.remove('hidden');

                fetch('{{ route('supplier.contract-data', ['id' => $merchant->id]) }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.contract) {
                            const contract = data.contract;

                            // Pre-fill form fields
                            if (contract.commission) {
                                document.getElementById('commission').value = contract.commission;
                            }

                            if (contract.contract_end_date) {
                                // Format date for flatpickr (YYYY-MM-DD)
                                const date = new Date(contract.contract_end_date);
                                const formattedDate = date.toISOString().split('T')[0];

                                // Set flatpickr value
                                if (flatpickrInstance) {
                                    flatpickrInstance.setDate(formattedDate, true);
                                }

                                // Also set the input value directly as backup
                                document.getElementById('contract_end_date').value = formattedDate;
                            }

                            // Show current contract info
                            if (data.has_contract) {
                                hasExistingContract = true;

                                document.getElementById('currentCommission').textContent = contract
                                    .commission || 'N/A';

                                if (contract.contract_end_date) {
                                    const date = new Date(contract.contract_end_date);
                                    document.getElementById('currentExpiryDate').textContent =
                                        date.toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric'
                                        });
                                } else {
                                    document.getElementById('currentExpiryDate').textContent = 'N/A';
                                }

                                // If contract.contract_url is provided by the endpoint, prefer it for preview
                                if (contract.contract_url) {
                                    showExistingFilePreview(contract.contract_url);
                                    currentExistingFile = contract.contract_url;
                                }

                                document.getElementById('currentContractInfo').classList.remove('hidden');
                            } else {
                                hasExistingContract = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Failed to load contract details:', error);
                    })
                    .finally(() => {
                        document.getElementById('loadingIndicator').classList.add('hidden');
                    });
            }

            // Handle form submission
            document.getElementById('documentUpdateForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate file only if needed:
                // - For non-contract docs file is required
                // - For contract: file required only if there is NO existing contract
                // - For IBAN certificate: file is required
                const fileInput = document.getElementById('file_input');
                const hasFile = fileInput.files && fileInput.files.length > 0;
                const isContract = currentDocumentType === 'contract';
                const isIbanCertificate = currentDocumentType === 'iban_certificate';

                if (!isContract && !hasFile && !isIbanCertificate) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('Error') }}',
                        text: '{{ translate('Please select a file to upload.') }}',
                        confirmButtonColor: '#3b82f6',
                    });
                    return;
                }

                if (isContract && !hasExistingContract && !hasFile) {
                    // no existing contract and no file provided — require file
                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('Error') }}',
                        text: '{{ translate('Please select a contract file to upload.') }}',
                        confirmButtonColor: '#3b82f6',
                    });
                    return;
                }

                if (isIbanCertificate && !hasFile) {
                    // IBAN certificate requires a file
                    Swal.fire({
                        icon: 'error',
                        title: '{{ translate('Error') }}',
                        text: '{{ translate('Please select an IBAN certificate file to upload.') }}',
                        confirmButtonColor: '#3b82f6',
                    });
                    return;
                }

                // Validate IBAN fields if this is an IBAN certificate
                if (isIbanCertificate) {
                    const bankName = document.getElementById('bank_name_select').value;
                    const accountName = document.getElementById('account_name_input').value;
                    const iban = document.getElementById('iban_input').value;

                    if (!bankName) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('Please select a bank name.') }}',
                            confirmButtonColor: '#3b82f6',
                        });
                        return;
                    }

                    if (!accountName) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('Please enter an account name.') }}',
                            confirmButtonColor: '#3b82f6',
                        });
                        return;
                    }

                    if (!iban) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('Please enter an IBAN number.') }}',
                            confirmButtonColor: '#3b82f6',
                        });
                        return;
                    }

                    // Validate IBAN format
                    const ibanPattern = /^[A-Za-z]{2}[0-9]{2}([ ]?[A-Za-z0-9]{4}){1,7}$/;
                    if (!ibanPattern.test(iban.replace(/\s/g, ''))) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('Please enter a valid IBAN number.') }}',
                            confirmButtonColor: '#3b82f6',
                        });
                        return;
                    }
                }

                // Validate file size if a file was selected
                if (hasFile) {
                    const file = fileInput.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                    if (file.size > maxSize) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('File size must be less than 5MB.') }}',
                            confirmButtonColor: '#3b82f6',
                        });
                        return;
                    }
                }

                const formData = new FormData(this);
                const submitBtn = document.getElementById('submitBtn');
                const originalText = submitBtn.textContent;

                // Show loading state
                submitBtn.textContent = '{{ translate('Uploading...') }}';
                submitBtn.disabled = true;

                fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message with SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: '{{ translate('Success') }}',
                                text: data.message,
                                confirmButtonColor: '#10b981',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                // Close modal
                                const modalCloseBtn = document.querySelector(
                                    '#documentUpdateModal [data-modal-dismiss="true"]');
                                if (modalCloseBtn) modalCloseBtn.click();

                                // Reload page to show updated document
                                window.location.reload();
                            });
                        } else {
                            // Show error message with SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: '{{ translate('Error') }}',
                                text: data.message,
                                confirmButtonColor: '#3b82f6',
                            });
                        }
                    })
                    .catch(error => {
                        // Show error message with SweetAlert
                        Swal.fire({
                            icon: 'error',
                            title: '{{ translate('Error') }}',
                            text: '{{ translate('An error occurred. Please try again.') }}',
                            confirmButtonColor: '#3b82f6',
                        });
                    })
                    .finally(() => {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
            });

            // Initialize flatpickr on page load for any existing date inputs
            if (document.querySelector('.flatpickr')) {
                initFlatpickr();
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ibanInput = document.getElementById('iban_input');

            if (ibanInput) {
                ibanInput.addEventListener('input', function(e) {
                    let value = e.target.value;

                    // Remove all non-alphanumeric characters and spaces
                    value = value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();

                    // Limit to max 34 characters
                    if (value.length > 34) {
                        value = value.slice(0, 34);
                    }

                    // Add space every 4 characters for readability
                    const formatted = value.match(/.{1,4}/g)?.join(' ') || '';

                    e.target.value = formatted;
                });

                // Optional: prevent pasting invalid chars
                ibanInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text');
                    const clean = paste.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 34);
                    const formatted = clean.match(/.{1,4}/g)?.join(' ') || '';
                    e.target.value = formatted;
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyIcon = document.getElementById('iban-copy-icon');
            const ibanText = document.getElementById('iban-text').innerText;

            copyIcon.addEventListener('click', async function() {
                try {
                    await navigator.clipboard.writeText(ibanText); // Copy IBAN to clipboard
                    copyIcon.classList.remove('ki-copy');
                    copyIcon.classList.add('ki-check');

                    // Optionally revert back to original icon after 2 seconds
                    setTimeout(() => {
                        copyIcon.classList.remove('ki-check');
                        copyIcon.classList.add('ki-copy');
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy IBAN:', err);
                }
            });
        });
    </script>
@endpush
