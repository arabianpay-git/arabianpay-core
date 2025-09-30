@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->
        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>
        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            <!-- Container -->
            @include('admin.accounts.includes.profile')
            <!-- End of Container -->
        </div>

        <!-- Container -->
        @include('admin.accounts.includes.header')
        <!-- End of Container -->
        <!-- Container -->
        <div class="container-fixed">
            <!-- begin: grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">

                <div class="col-span-1 lg:col-span-3">
                    @include('admin.accounts.singleview-components.accounts')
                </div>

                <div class="col-span-1 lg:col-span-3">
                    @include('admin.accounts.singleview-components.all-accounts-balance')
                </div>

                <div class="col-span-1 lg:col-span-3">
                    @include('admin.accounts.singleview-components.credit-check-basic')
                </div>

                <div class="col-span-1 lg:col-span-3">
                    @include('admin.accounts.singleview-components.income-check-advanced')
                </div>
            </div>
            <!-- end: grid -->
        </div>
        <!-- End of Container -->
    </main>
@endsection
