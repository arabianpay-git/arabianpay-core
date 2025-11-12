@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Create Expense Setting') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <a class="text-gray-600 hover:text-primary" href="{{ route('financial.expense-settings.index') }}">
                            {{ translate('Expense Settings') }}
                        </a>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900">{{ translate('Create') }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('financial.expense-settings.index') }}">
                        {{ translate('Back to List') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ translate('Expense Setting Information') }}</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('financial.expense-settings.store') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <!-- Reference ID -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Reference ID') }} <span class="text-danger">*</span>
                                    </label>
                                    <label class="input">
                                        <input name="refrence_id" type="text" placeholder="{{ translate('Enter reference ID') }}" 
                                               value="{{ old('refrence_id') }}" required />
                                    </label>
                                    @error('refrence_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                    <span class="form-hint text-gray-700">{{ translate('Unique identifier for this expense setting') }}</span>
                                </div>

                                <!-- Amount Type -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Amount Type') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="amount_type" id="amount_type" class="select" required>
                                        <option value="">{{ translate('Select type') }}</option>
                                        <option value="fixed" {{ old('amount_type') == 'fixed' ? 'selected' : '' }}>{{ translate('Fixed Amount') }}</option>
                                        <option value="percent" {{ old('amount_type') == 'percent' ? 'selected' : '' }}>{{ translate('Percentage') }}</option>
                                    </select>
                                    @error('amount_type')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Amount -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Amount') }} <span class="text-danger">*</span>
                                    </label>
                                    <label class="input">
                                        <input name="amount" id="amount" type="number" step="0.01" min="0" 
                                               placeholder="{{ translate('Enter amount') }}" 
                                               value="{{ old('amount') }}" required />
                                        <span id="amount_suffix" class="text-gray-500"></span>
                                    </label>
                                    @error('amount')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                    <span class="form-hint text-gray-700" id="amount_hint">{{ translate('Enter the amount') }}</span>
                                </div>

                                <!-- Credit Account -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Credit Account') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="credit_acc_id" class="select" required>
                                        <option value="">{{ translate('Select credit account') }}</option>
                                        @foreach($creditAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('credit_acc_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->id }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('credit_acc_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Description (Full Width) -->
                                <div class="flex flex-col gap-2.5 lg:col-span-2">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Description') }} <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="description" class="textarea" rows="4" 
                                              placeholder="{{ translate('Enter description') }}" required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end gap-4 mt-7.5 mt-5">
                                <a href="{{ route('financial.expense-settings.index') }}" class="btn btn-light">
                                    {{ translate('Cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{ translate('Create Expense Setting') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Update amount hint based on amount type
            $('#amount_type').on('change', function() {
                var type = $(this).val();
                if (type === 'percent') {
                    $('#amount_hint').text('{{ translate("Enter percentage value (e.g., 5 for 5%)") }}');
                    $('#amount_suffix').text('%');
                    $('#amount').attr('max', '100');
                } else {
                    $('#amount_hint').text('{{ translate("Enter fixed amount") }}');
                    $('#amount_suffix').text('');
                    $('#amount').removeAttr('max');
                }
            });

            // Trigger on page load if old value exists
            if ($('#amount_type').val()) {
                $('#amount_type').trigger('change');
            }
        });
    </script>
@endpush
