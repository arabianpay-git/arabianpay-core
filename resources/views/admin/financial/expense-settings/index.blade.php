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
                        {{ translate('3rd Party Services Cost') }}
                    </h1>
                    
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-primary" href="{{ route('financial.expense-settings.create') }}">
                        <i class="ki-filled ki-plus"></i>
                        {{ translate('3rd Party Services Cost') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End of Container -->

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                  
                    <div class="card-body">
                        <div data-datatable="true" data-datatable-page-size="10">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" id="expense_settings_table">
                                    <thead>
                                        <tr>
                                            <th class="min-w-[120px]">{{ translate('Reference ID') }}</th>
                                            <th class="min-w-[250px]">{{ translate('Description') }}</th>
                                            <th class="min-w-[120px]">{{ translate('Amount Type') }}</th>
                                            <th class="min-w-[80px]">{{ translate('Amount') }}</th>
                                            <th class="min-w-[200px]">{{ translate('Credit Account') }}</th>
                                            <th class="min-w-[50px]">{{ translate('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($expenseSettings as $expenseSetting)
                                        <tr>
                                            <td>{{ $expenseSetting->refrence_id }}</td>
                                            <td>{{ $expenseSetting->description }}</td>
                                            <td>
                                                @if($expenseSetting->amount_type == 'fixed')
                                                    {{ translate('Fixed Amount') }}
                                                @elseif($expenseSetting->amount_type == 'percentage')
                                                    {{ translate('Percentage') }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($expenseSetting->amount_type == 'fixed')
                                                    {{ $expenseSetting->amount }}
                                                @elseif($expenseSetting->amount_type == 'percentage')
                                                    {{ $expenseSetting->percentage }}%
                                                @endif
                                            </td>
                                            <td>{{ $expenseSetting->creditAccount->account_name ?? '' }}</td>
                                            <td>
                                                <a href="{{ route('financial.expense-settings.edit', $expenseSetting->id) }}" class="btn btn-sm btn-light">
                                                  <i class="ki-filled ki-notepad-edit"> </i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection


