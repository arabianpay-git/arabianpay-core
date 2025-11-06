@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .modal {
                z-index: 1055;
            }

            .media-modal {
                z-index: 1060 !important;
            }
        </style>
    @endpush

    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Supplier Entitlement') }}
                    </h1>
                </div>
            </div>
        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Supplier Entitlement') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#team_crew_table"
                                        placeholder="{{ translate('Search users') }}" type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Customer') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Seller') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-left">
                                                <span class="sort asc">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Order Code') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Order Date') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Settlement Status') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Supplier Amount') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Total Payment') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Total Amount') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span class="sort">
                                                    <span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Action') }}</span>
                                                    <span class="sort-icon"> </span>
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transactions as $transaction)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>

                                                <td>
                                                    {{ $transaction->user?->first_name ?? translate('N/A') }}
                                                    {{ $transaction->user?->last_name ?? translate('N/A') }}
                                                    <br>
                                                    <small class="text-gray-500">—
                                                        {{ $transaction->user?->business_name ?? '' }}</small>
                                                </td>

                                                <td>
                                                    {{ $transaction->seller?->first_name ?? translate('N/A') }}
                                                    {{ $transaction->seller?->last_name ?? translate('N/A') }}
                                                    <br>
                                                    <small class="text-gray-500">—
                                                        {{ $transaction->seller?->business_name ?? '' }}</small>
                                                </td>

                                                <td>{{ $transaction->order?->tracking ?? translate('N/A') }}</td>

                                                <td class="text-center">
                                                    {{ $transaction->created_at->format('Y-m-d') }}
                                                </td>

                                                <td class="text-center">
                                                    <span
                                                        class="badge badge-sm badge-{{ $transaction->settlement_status == 'settled' ? 'success' : 'danger' }} badge-outline">
                                                        {{ ucfirst($transaction->settlement_status) }}
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    {{ number_format($transaction->calculated['totalSuplierDue'] ?? 0.0, 2) }}
                                                </td>

                                                <td class="text-center">
                                                    {{ number_format($transaction->calculated['supplierDue'] ?? 0.0, 2) }}
                                                </td>

                                                <td class="text-center">
                                                    {{ number_format($transaction->calculated['base'] ?? 0.0, 2) }}
                                                </td>

                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('orders.details', ['id' => $transaction->order->id]) }}"
                                                            title="{{ translate('View Details') }}">
                                                            <i class="ki-filled ki-eye"> </i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-success open-payment-modal"
                                                            data-modal-toggle="#payment_modal"
                                                            data-id="{{ $transaction->id }}"
                                                            data-seller-id="{{ $transaction->seller->id }}"
                                                            data-due="{{ number_format($transaction->calculated['totalSuplierDue'] ?? 0.0, 2) }}"
                                                            data-transfer-fee="{{ get_setting('service_fees', 0) }}"
                                                            data-settled="{{ number_format($transaction->calculated['supplierDue'] ?? 0.0, 2) }}"
                                                            data-refund-sum="{{ $transaction->refund_amount }}"
                                                            data-final="{{ number_format(($transaction->calculated['totalSuplierDue'] ?? 0.0) - get_setting('service_fees', 0), 2) }}"
                                                            data-date="{{ now()->format('Y-m-d') }}"
                                                            title="{{ translate('Make Payment') }}">
                                                            <i class="ki-filled ki-cheque"> </i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $transactions])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>

    <div id="payment_backdrop"></div>

    <div class="modal" data-modal="true" id="payment_modal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">{{ translate('Make Supplier Payment') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true"
                    aria-label="{{ translate('Close') }}">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>

            <form action="{{ route('sellerPaymentFromAdmin') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="transaction_id" id="modal_transaction_id" />

                <div class="card-body grid gap-5">
                    <div class="overflow-x-auto mb-4">
                        <table class="table table-auto table-border w-full">
                            <tbody>
                                <tr>
                                    <td>{{ translate('Due to supplier') }}:</td>
                                    <td>SR <span id="modal_due"></span></td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Transfer fees') }}:</td>
                                    <td>SR <span id="modal_transfer_fee"></span></td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Final Supplier Amount') }}:</td>
                                    <td>SR <span id="modal_final"></span></td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Settled amount') }}:</td>
                                    <td>SR <span id="modal_settled"></span></td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Refund amount') }}:</td>
                                    <td>SR <span id="modal_refund"></span></td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Remaining amount') }}:</td>
                                    <td>SR <span id="modal_remaining"></span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <input type="hidden" name="seller_id" id="modal_seller_id">
                        <label class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" step="0.01" required
                            class="input @error('amount') border-red-500 @enderror"
                            placeholder="{{ translate('Enter amount') }}" />
                        @error('amount')
                            <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">{{ translate('Transfer Number') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" name="transfer_number" required
                            class="input @error('transfer_number') border-red-500 @enderror"
                            placeholder="{{ translate('Enter transfer number') }}" />
                        @error('transfer_number')
                            <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">{{ translate('Payment Date') }} <span
                                class="text-danger">*</span></label>
                        <input type="date" name="payment_date" id="modal_payment_date" required
                            class="input @error('payment_date') border-red-500 @enderror" />
                        @error('payment_date')
                            <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        @include('media.single', [
                            'name' => 'receipt',
                            'label' => translate('Photo of Receipt'),
                            'required' => false,
                            'value' => old('receipt'),
                            'modalParent' => '#payment_modal', // Pass parent modal selector
                        ])
                        @error('receipt')
                            <span class="text-danger text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer px-5 pb-5 flex justify-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        {{ translate('Submit Payment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle payment modal
            document.querySelectorAll('.open-payment-modal').forEach(button => {
                button.addEventListener('click', function() {
                    const modal = document.querySelector('#payment_modal');
                    const formatValue = (value) => value || value === 0 ? value : 'N/A';

                    // Set values with N/A fallback
                    document.getElementById('modal_transaction_id').value = this.dataset.id ||
                    'N/A';
                    document.getElementById('modal_seller_id').value = this.dataset.sellerId ||
                        'N/A';
                    document.getElementById('modal_due').textContent = formatValue(this.dataset
                    .due);
                    document.getElementById('modal_transfer_fee').textContent = formatValue(this
                        .dataset.transferFee);
                    document.getElementById('modal_final').textContent = formatValue(this.dataset
                        .final);
                    document.getElementById('modal_settled').textContent = formatValue(this.dataset
                        .settled);
                    document.getElementById('modal_refund').textContent = formatValue(this.dataset
                        .refundSum);

                    // Calculate remaining amount
                    const remaining = (parseFloat(this.dataset.due) || 0) -
                        (parseFloat(this.dataset.settled) || 0) -
                        (parseFloat(this.dataset.refundSum) || 0);
                    document.getElementById('modal_remaining').textContent = formatValue(remaining
                        .toFixed(2));

                    document.getElementById('modal_payment_date').value = this.dataset.date ||
                        new Date().toISOString().split('T')[0];
                });
            });

            // Handle media modal interactions
            document.addEventListener('click', function(e) {
                const paymentModal = document.querySelector('#payment_modal');
                const mediaModal = document.querySelector('.media-modal');
                const paymentBackdrop = document.querySelector('#payment_backdrop');

                // When opening media picker
                if (e.target.closest('[data-media-toggle]')) {
                    if (paymentModal && mediaModal) {
                        // Hide payment modal backdrop
                        if (paymentBackdrop) paymentBackdrop.style.display = 'none';
                        // Bring media modal to front
                        mediaModal.style.zIndex = '1065';
                        mediaModal.style.display = 'block';
                    }
                }

                // When closing media picker
                if (e.target.closest('[data-media-dismiss]')) {
                    if (paymentModal && mediaModal) {
                        // Show payment modal backdrop
                        if (paymentBackdrop) paymentBackdrop.style.display = 'block';
                        // Reset z-index
                        mediaModal.style.zIndex = '1060';
                    }
                }
            });

            // Handle media selection
            document.addEventListener('media-selected', function() {
                const paymentModal = document.querySelector('#payment_modal');
                const mediaModal = document.querySelector('.media-modal');
                const paymentBackdrop = document.querySelector('#payment_backdrop');

                if (paymentModal && mediaModal) {
                    mediaModal.style.display = 'none';
                    if (paymentBackdrop) paymentBackdrop.style.display = 'block';
                }
            });

            // Prevent closing payment modal when clicking on media modal
            document.addEventListener('click', function(e) {
                const mediaModal = e.target.closest('.media-modal');
                const paymentModal = document.querySelector('#payment_modal');

                if (mediaModal && paymentModal) {
                    e.stopPropagation();
                }
            });
        });
    </script>
@endpush
