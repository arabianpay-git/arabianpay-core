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
                        {{ translate('Create Financial Transaction') }}
                    </h1>
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-600">
                        <a class="text-gray-600 hover:text-primary" href="{{ route('financial.transactions.index') }}">
                            {{ translate('Financial Transactions') }}
                        </a>
                        <span class="text-gray-400">/</span>
                        <span class="text-gray-900">{{ translate('Create') }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('financial.transactions.index') }}">
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
                        <h3 class="card-title">{{ translate('Transaction Information') }}</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('financial.transactions.store') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                                <!-- Transaction Type -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Transaction Type') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="transaction_type" class="select" required>
                                        <option value="">{{ translate('Select type') }}</option>
                                        <option value="payment" {{ old('transaction_type') == 'payment' ? 'selected' : '' }}>{{ translate('Payment') }}</option>
                                        <option value="refund" {{ old('transaction_type') == 'refund' ? 'selected' : '' }}>{{ translate('Refund') }}</option>
                                        <option value="withdrawal" {{ old('transaction_type') == 'withdrawal' ? 'selected' : '' }}>{{ translate('Withdrawal') }}</option>
                                        <option value="deposit" {{ old('transaction_type') == 'deposit' ? 'selected' : '' }}>{{ translate('Deposit') }}</option>
                                        <option value="transfer" {{ old('transaction_type') == 'transfer' ? 'selected' : '' }}>{{ translate('Transfer') }}</option>
                                    </select>
                                    @error('transaction_type')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Amount -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Amount') }} <span class="text-danger">*</span>
                                    </label>
                                    <label class="input">
                                        <input name="amount" type="number" step="0.01" min="0" placeholder="{{ translate('Enter amount') }}" 
                                               value="{{ old('amount') }}" required />
                                    </label>
                                    @error('amount')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Transaction Date -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Transaction Date') }} <span class="text-danger">*</span>
                                    </label>
                                    <label class="input">
                                        <input name="transaction_date" type="date" value="{{ old('transaction_date', date('Y-m-d')) }}" required />
                                    </label>
                                    @error('transaction_date')
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
                                        <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>{{ translate('Pending') }}</option>
                                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>{{ translate('Completed') }}</option>
                                        <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>{{ translate('Failed') }}</option>
                                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>{{ translate('Cancelled') }}</option>
                                    </select>
                                    @error('status')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- User -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('User') }} <span class="text-danger">*</span>
                                    </label>
                                    <select name="user_id" class="select" required>
                                        <option value="">{{ translate('Select user') }}</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Reference ID -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Reference ID') }}
                                    </label>
                                    <label class="input">
                                        <input name="reference_id" type="text" placeholder="{{ translate('Enter reference ID') }}" 
                                               value="{{ old('reference_id') }}" />
                                    </label>
                                    @error('reference_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Customer (Optional) -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Customer') }}
                                    </label>
                                    <select name="customer_id" class="select">
                                        <option value="">{{ translate('Select customer (optional)') }}</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Supplier (Optional) -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Supplier') }}
                                    </label>
                                    <select name="supplier_id" class="select">
                                        <option value="">{{ translate('Select supplier (optional)') }}</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Order (Optional) -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Order') }}
                                    </label>
                                    <select name="order_id" class="select">
                                        <option value="">{{ translate('Select order (optional)') }}</option>
                                        @foreach($orders as $order)
                                            <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                                #{{ $order->id }} - {{ $order->total ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('order_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Payment (Optional) -->
                                <div class="flex flex-col gap-2.5">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Payment') }}
                                    </label>
                                    <select name="payment_id" class="select">
                                        <option value="">{{ translate('Select payment (optional)') }}</option>
                                        @foreach($payments as $payment)
                                            <option value="{{ $payment->id }}" {{ old('payment_id') == $payment->id ? 'selected' : '' }}>
                                                #{{ $payment->id }} - {{ $payment->amount ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_id')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Notes (Full Width) -->
                                <div class="flex flex-col gap-2.5 lg:col-span-2">
                                    <label class="form-label text-gray-900 font-medium">
                                        {{ translate('Notes') }}
                                    </label>
                                    <textarea name="notes" class="textarea" rows="4" 
                                              placeholder="{{ translate('Enter transaction notes') }}">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="form-hint text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="flex justify-end gap-4 mt-7.5">
                                <a href="{{ route('financial.transactions.index') }}" class="btn btn-light">
                                    {{ translate('Cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{ translate('Create Transaction') }}
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