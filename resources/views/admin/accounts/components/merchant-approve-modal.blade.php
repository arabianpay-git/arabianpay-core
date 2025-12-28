<div class="modal" data-modal="true" id="approve_modal">
    <div class="modal-content max-w-[600px] shadow-2xl">
        <div class="modal-header py-4 border-b border-slate-100">
            <h5 class="modal-title font-bold text-slate-800">{{ translate('Supplier Approval') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <form action="{{ route('updateSupplierStatusApprove', ['id' => $merchant->user_id]) }}" method="POST"
            class="modal-body p-6" enctype="multipart/form-data">
            @csrf @method('PUT')
            <input type="hidden" name="status" value="approved">

            @php
                $riskService = new App\Services\RiskAnalyticsService();
                $user = App\Models\User::find($merchant->user_id);
                $calculatedRisk = $riskService->calculateForUser($user);
                $branches = App\Models\Branch::where('merchant_id', $merchant->id)->get();
                $mainBranchActivity = $merchant->main_branch ?? null;
            @endphp

            <input type="hidden" name="fahman_score" value="{{ $calculatedRisk->total_score }}">

            <div class="grid grid-cols-1 gap-5">
                <div>
                    <label class="form-label font-semibold mb-2 block">{{ translate('Select Activity') }}</label>
                    <select name="selected_activity" class="select" required>
                        <option value="">-- {{ translate('Select Activity') }} --</option>
                        @if ($mainBranchActivity)
                            <option value="{{ $mainBranchActivity }}">{{ $mainBranchActivity }}</option>
                        @endif
                        @foreach ($branches as $branch)
                            @if ($branch->activity !== $mainBranchActivity)
                                <option value="{{ $branch->activity }}">{{ $branch->activity }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label font-semibold mb-2 block">{{ translate('Commission %') }}</label>
                        <input type="number" name="commission" class="input" step="0.01" min="0"
                            max="100" required>
                    </div>
                    <div>
                        <label
                            class="form-label font-semibold mb-2 block">{{ translate('Payment Schedule (Days)') }}</label>
                        <input type="number" name="payment_schedule" class="input" step="1" min="0"
                            required>
                    </div>
                </div>

                <div>
                    <label class="form-label font-semibold mb-2 block">{{ translate('Approval Reason') }}</label>
                    <textarea name="reason" rows="3" class="textarea" placeholder="Enter justification..." required></textarea>
                </div>

                <div>
                    <label class="form-label font-semibold mb-2 block">{{ translate('Contract Document') }}</label>
                    <div class="flex items-center relative">
                        <button type="button" class="absolute left-0 z-10 pl-3 text-slate-400" id="selectFileBtn">
                            <i class="ki-filled ki-file-up text-xl"></i>
                        </button>
                        <input type="text" id="fileNameDisplay" class="input pl-11 cursor-pointer"
                            placeholder="Choose file..." readonly
                            onclick="document.getElementById('contract_file').click()">
                        <input type="file" name="contract" id="contract_file" class="hidden">
                    </div>
                </div>

                <div>
                    <label class="form-label font-semibold mb-2 block">{{ translate('Contract End Date') }}</label>
                    <div class="relative">
                        <input type="text" name="contract_end_date" id="contract_end_date_picker"
                            class="input flatpickr-input" placeholder="YYYY-MM-DD" readonly required>
                        <i class="ki-filled ki-calendar absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-8">
                <button type="button" class="btn btn-light"
                    data-modal-dismiss="true">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ translate('Confirm & Approve') }}</button>
            </div>
        </form>
    </div>
</div>


@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Initialize Flatpickr
            const dateInput = document.querySelector("#contract_end_date_picker");
            if (dateInput) {
                flatpickr(dateInput, {
                    dateFormat: "Y-m-d",
                    minDate: "today", // Prevents picking past dates
                    disableMobile: "true",
                    // If using Metronic's specific modal container, you can append the calendar there:
                    // static: true 
                });
            }

            // 2. Handle File Input Selection Display
            const fileInput = document.getElementById('contract_file');
            const fileNameDisplay = document.getElementById('fileNameDisplay');

            if (fileInput && fileNameDisplay) {
                fileInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        fileNameDisplay.value = this.files[0].name;
                    }
                });
            }
        });
    </script>
@endpush
