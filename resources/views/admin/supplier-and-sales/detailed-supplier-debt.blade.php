@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Detailed Supplier Debt') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed mb-5">
            <div class="card shadow-sm border border-gray-200">
                <div class="card-header bg-gray-50 px-5 py-3 border-b border-gray-200">
                    <h3 class="card-title text-sm font-semibold text-gray-700">
                        {{ translate('Filter Suppliers') }}
                    </h3>
                </div>
                <div class="card-body px-5 py-4">
                    <form method="GET" action="{{ route('detailedSupplierDebt') }}">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="w-full md:w-1/3">
                                <label for="merchant_select" class="block text-xs font-medium text-gray-600 mb-1">
                                    {{ translate('Select Supplier') }}
                                </label>
                                <select id="merchant_select" name="merchant_id" class="form-select w-full">
                                    <option value="">{{ translate('All Suppliers') }}</option>
                                    @foreach ($merchants as $merchant)
                                        <option value="{{ encrypt($merchant['id']) }}"
                                            {{ request('merchant_id') == encrypt($merchant['id']) ? 'selected' : '' }}>
                                            {{ $merchant['business_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pt-5 md:pt-0 flex items-center gap-3">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    {{ translate('Apply Filter') }}
                                </button>
                                <a href="{{ route('detailedSupplierDebt') }}" class="btn btn-danger btn-sm">
                                    {{ translate('Clear Filter') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Detailed Supplier Debt') }}
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
                                            <th class="text-left"><span class="sort asc"><span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Supplier') }}</span></span>
                                            </th>
                                            <th class="text-left"><span class="sort asc"><span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Tax Number') }}</span></span>
                                            </th>
                                            <th class="text-center"><span class="sort"><span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Opening Balance') }}</span></span>
                                            </th>
                                            <th class="text-center"><span class="sort"><span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Total Purchases') }}</span></span>
                                            </th>
                                            <th class="text-center"><span class="sort"><span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Total Payments') }}</span></span>
                                            </th>
                                            <th class="text-center"><span class="sort"><span
                                                        class="sort-label font-normal text-gray-700">{{ translate('Remaining Balance') }}</span></span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summary as $index => $item)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        {{ $item['seller_name'] }}
                                                        <br>
                                                        <small class="text-gray-500">—
                                                            {{ $item['seller_business'] }}</small>
                                                    </div>
                                                </td>
                                                <td>{{ $item['tax_number'] }}</td>
                                                <td class="text-center"><span class="icon-saudi_riyal"></span>
                                                    {{ number_format((float) $item['opening_balance'], 2) }}</td>
                                                <td class="text-center"><span class="icon-saudi_riyal"></span>
                                                    {{ number_format((float) $item['total_purchases'], 2) }}</td>
                                                <td class="text-center"><span class="icon-saudi_riyal"></span>
                                                    {{ number_format((float) $item['total_payments'], 2) }}</td>
                                                <td class="text-center"><span class="icon-saudi_riyal"></span>
                                                    {{ number_format((float) $item['remaining_balance'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @include('layouts.includes.table-pagination', ['paginator' => $paginator])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        const merchantSelect = document.getElementById('merchant_select');
        if (merchantSelect) {
            new Choices(merchantSelect, {
                searchEnabled: true,
                itemSelectText: '',
            });
        }
    </script>
@endpush

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush
