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
                        {{ translate('Create Financial Account') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <a class="text-gray-600 hover:text-primary" href="{{ route('financial.accounts.index') }}">
                            {{ translate('Financial Accounts') }}
                        </a>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900">{{ translate('Create') }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('financial.accounts.index') }}">
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
                        <h3 class="card-title">{{ translate('Account Information') }}</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('financial.accounts.store') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <!-- Account Code -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Account Code') }} <span class="text-danger">*</span>
                                    </label>
                                    <label class="input">
                                        <input name="id" type="number" placeholder="{{ translate('Enter account code') }}" 
                                               value="{{ old('id') }}" required />
                                    </label>
                                    @error('id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Account Name -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Account Name') }} <span class="text-danger">*</span>
                                    </label>
                                    <label class="input">
                                        <input name="account_name" type="text" placeholder="{{ translate('Enter account name') }}" 
                                               value="{{ old('account_name') }}" required />
                                    </label>
                                    @error('account_name')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Account Type 1 (Budget/Non-Budget) -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Account Category') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="account_type1" class="select" required>
                                        <option value="">{{ translate('Select category') }}</option>
                                        <option value="1" {{ old('account_type1') == '1' ? 'selected' : '' }}>{{ translate('Budget') }}</option>
                                        <option value="2" {{ old('account_type1') == '2' ? 'selected' : '' }}>{{ translate('Non-Budget') }}</option>
                                    </select>
                                    @error('account_type1')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Account Type 2 (Debit/Credit) -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Account Type') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="account_type2" class="select" required>
                                        <option value="">{{ translate('Select type') }}</option>
                                        <option value="1" {{ old('account_type2') == '1' ? 'selected' : '' }}>{{ translate('Debit') }}</option>
                                        <option value="2" {{ old('account_type2') == '2' ? 'selected' : '' }}>{{ translate('Credit') }}</option>
                                    </select>
                                    @error('account_type2')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Status') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="status" class="select" required>
                                        <option value="">{{ translate('Select status') }}</option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Description (Full Width) -->
                                <div class="flex flex-col gap-2.5 lg:col-span-2">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Description') }}
                                    </label>
                                    <textarea name="description" class="textarea" rows="4" 
                                              placeholder="{{ translate('Enter account description') }}">{{ old('description') }}</textarea>
                                    @error('description')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end gap-4 mt-7.5">
                                <a href="{{ route('financial.accounts.index') }}" class="btn btn-light">
                                    {{ translate('Cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{ translate('Create Account') }}
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