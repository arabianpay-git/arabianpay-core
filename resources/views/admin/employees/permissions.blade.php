<div class="w-full">
    <label class="form-label">
        {{ translate('Sensitive Permissions') }}
    </label>

    @php
        $selected = collect(old('sensitive_permissions', $selected ?? []))
            ->map(fn($v) => (string) $v)
            ->toArray();
    @endphp

    <select id="sensitive_permissions" name="sensitive_permissions[]" class="select w-full" multiple>

        <option value="business_identity" @selected(in_array('business_identity', $selected))>
            Business Identity (CR/VAT/Activity)
        </option>

        <option value="authorized_person_name" @selected(in_array('authorized_person_name', $selected))>
            Authorized Person Name
        </option>

        <option value="national_id_iqama" @selected(in_array('national_id_iqama', $selected))>
            National ID / Iqama
        </option>

        <option value="phone_number" @selected(in_array('phone_number', $selected))>
            Phone Number
        </option>

        <option value="email_address" @selected(in_array('email_address', $selected))>
            Email Address
        </option>

        <option value="full_address" @selected(in_array('full_address', $selected))>
            Address (Full)
        </option>

        <option value="iban_bank_account" @selected(in_array('iban_bank_account', $selected))>
            IBAN / Bank Account
        </option>

        <option value="documents_id_cr_contracts" @selected(in_array('documents_id_cr_contracts', $selected))>
            Documents (ID/CR/Contracts)
        </option>

        <option value="credit_data_simah_bureau" @selected(in_array('credit_data_simah_bureau', $selected))>
            Credit Data (SIMAH / Bureau)
        </option>

        <option value="credit_decision_output" @selected(in_array('credit_decision_output', $selected))>
            Credit Decision Output (Limit/Grade)
        </option>

        <option value="risk_drivers_aggregated" @selected(in_array('risk_drivers_aggregated', $selected))>
            Risk Drivers (Aggregated)
        </option>

        <option value="transaction_references" @selected(in_array('transaction_references', $selected))>
            Transaction References (Gateway/Bank)
        </option>

        <option value="risk_compliance_notes" @selected(in_array('risk_compliance_notes', $selected))>
            Notes (Risk/Compliance/Collections)
        </option>

        <option value="export_dataset_row_level" @selected(in_array('export_dataset_row_level', $selected))>
            Export Dataset (Row-level)
        </option>

        <option value="api_keys_secrets" @selected(in_array('api_keys_secrets', $selected))>
            API Keys / Secrets
        </option>
    </select>
</div>

@push('scripts')
    <script>
        if (!window.sensitiveChoices) {
            window.sensitiveChoices = new Choices('#sensitive_permissions', {
                removeItemButton: true,
                shouldSort: false,
                duplicateItemsAllowed: false,
                searchEnabled: true,
                placeholderValue: 'Select Sensitive Permissions',
            });
        }
    </script>
@endpush
