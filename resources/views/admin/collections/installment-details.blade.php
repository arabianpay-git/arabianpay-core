@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{-- {{ translate('Order Information') }} #{{ \Illuminate\Support\Str::upper($order->tracking ?? '-') }} --}}
                        {{ translate('Installmet Information') }} # JBH786GVHB
                    </h1>
                </div>
                <div class="flex justify-end" style="gap: 0.5rem;">
                    @include('admin.collections.components.installment-detail-header')
                </div>
            </div>
        </div>


        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <!-- begin: grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

                <div class="col-span-2">
                    <div class="card card-grid min-w-full">
                        <div class="card-header flex-wrap gap-2">
                            <h3 class="card-title font-medium text-sm">
                                {{ translate('Products Orders') }}
                            </h3>
                            <div class="flex flex-wrap gap-2 lg:gap-5">
                                <div class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"> </i>
                                        <input data-datatable-search="#team_crew_table"
                                            placeholder="{{ translate('Search product') }}" type="text" value="" />
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('admin.orders.partials.order-product-table')
                        </div>
                    </div>

                    @include('admin.orders.partials.order-customer-info')

                    @include('admin.orders.partials.order-schedule-payments')

                </div>

                <div class="col-span-1 lg:col-span-1">
                    @include('admin.orders.partials.order-summary')

                    @include('admin.orders.partials.order-payment-info')

                    @include('admin.collections.components.installment-notes')

                    @include('admin.collections.components.installment-promises')

                </div>
            </div>
            <!-- end: grid -->
        </div>
        <!-- End of Container -->
    </main>
@endsection
