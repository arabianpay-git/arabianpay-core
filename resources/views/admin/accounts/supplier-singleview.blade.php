@extends('layouts.base')

@section('content')
    <main class="flex-grow w-full mx-auto p-6 space-y-6 bg-slate-50">

        @include('admin.accounts.includes.supplier-profile-header')

        @include('admin.accounts.includes.supplier-nav')

        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 space-y-6">
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
        </div>
    </main>
@endsection
