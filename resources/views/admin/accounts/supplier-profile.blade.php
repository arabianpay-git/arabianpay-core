@extends('layouts.base')

@section('content')
    <main class="max-w-[1400px] mx-auto p-6 space-y-6 bg-slate-50">

        @include('admin.accounts.includes.supplier-profile-header')

        @include('admin.accounts.includes.supplier-nav')

        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 lg:col-span-8 space-y-6">

                <!-- Business Summary -->
                @include('admin.accounts.components.business-summary')

                <!-- Financial Snapshot -->
                @include('admin.accounts.components.financial-snapshot')

                <!-- Recent Activity -->
                @include('admin.accounts.components.recent-activities')
            </div>

            <div class="col-span-12 lg:col-span-4 space-y-6">

                <!-- Compliance & Risk -->
                @include('admin.accounts.components.compliance-and-risk')

                <!-- Updated Risk Indicators -->
                @include('admin.accounts.components.risk-indicators', ['riskScore' => $riskScore])

                <!-- Updated Document Alert -->
                @include('admin.accounts.components.document-alert', ['riskScore' => $riskScore])

            </div>
        </div>
    </main>
@endsection
