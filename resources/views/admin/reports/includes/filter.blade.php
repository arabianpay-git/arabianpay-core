@php
    $merchants = App\Models\User::where('user_type', 'merchant')
        ->select('id', 'first_name', 'last_name', 'business_name')
        ->get();
@endphp

@push('styles')
    <style>
        .choices__inner {
            min-height: 30px !important;
            padding: 4.5px 12.5px 6.75px !important;
        }

        .choices__list--single {
            padding: 0 !important;
        }
    </style>
@endpush

<div class="container-fixed mb-5">
    <div class="card shadow-sm border border-gray-200">
        <div class="card-body p-5">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ $filterHeading ?? 'Filter Data' }}</h2>
            <form method="GET" action="" class="flex flex-wrap gap-4 items-end">

                <!-- From Date -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 mb-1">From Date</label>
                    <input type="text" name="from" id="fromDate" value="{{ request('from') }}"
                        class="form-input border border-gray-300 rounded px-3 py-2 text-sm w-full"
                        placeholder="Select from date">
                </div>

                <!-- To Date -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 mb-1">To Date</label>
                    <input type="text" name="to" id="toDate" value="{{ request('to') }}"
                        class="form-input border border-gray-300 rounded px-3 py-2 text-sm w-full"
                        placeholder="Select to date">
                </div>

                <!-- Merchant Select -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 mb-1">Merchant</label>
                    <select name="merchant_id" id="merchantSelect"
                        class="form-select border border-gray-300 rounded px-3 py-2 text-sm w-full">
                        <option value="">All Merchants</option>
                        @foreach ($merchants as $merchant)
                            <option value="{{ $merchant->id }}" @selected(request('merchant_id') == $merchant->id)>
                                {{ $merchant->first_name }} {{ $merchant->last_name }}
                                ({{ $merchant->business_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit -->
                <div class="self-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ki-filled ki-magnifier"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Choices.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('#fromDate', {
                dateFormat: 'Y-m-d'
            });
            flatpickr('#toDate', {
                dateFormat: 'Y-m-d'
            });

            const merchantSelect = document.getElementById('merchantSelect');
            if (merchantSelect) {
                new Choices(merchantSelect, {
                    searchEnabled: true,
                    itemSelectText: '',
                    placeholder: true,
                    placeholderValue: 'Select a merchant'
                });
            }
        });
    </script>
@endpush
