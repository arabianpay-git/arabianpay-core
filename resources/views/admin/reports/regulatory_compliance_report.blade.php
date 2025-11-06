@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">

        <!-- Header -->
        <div class="container-fixed mb-5">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Regulatory Compliance Report') }}
                    </h1>
                    <p class="text-gray-600">{{ translate('Tracks all reports required by SAMA, PDPL, etc.') }}</p>
                </div>
            </div>
        </div>

        @include('admin.reports.includes.filter', [
            'filterHeading' => 'Filter Merchat Data',
        ])

        @include('admin.reports.includes.customer-filter', [
            'filterHeading' => 'Filter Customer Data',
        ])

        <!-- Compliance Reports Table -->
        <div class="container-fixed mb-10">
            <div class="card card-grid min-w-full">
                <div class="card-header flex-wrap gap-2">
                    <h3 class="card-title font-medium text-sm">{{ translate('Compliance Reports') }}</h3>
                    <div class="flex flex-wrap gap-2 lg:gap-5">
                        <div class="flex">
                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"></i>
                                <input data-datatable-search="#compliance_report_table"
                                    placeholder="{{ translate('Search reports') }}" type="text" />
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div data-datatable="true" data-datatable-city-save="false" id="compliance_report_table">
                        <div class="scrollable-x-auto">
                            <table class="table table-auto table-border" data-datatable-table="true">
                                <thead>
                                    <tr>
                                        <th>{{ translate('Entity Name') }}</th>
                                        <th>{{ translate('Entity Type') }}</th>
                                        <th>{{ translate('File') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($complianceFiles as $entity)
                                        @foreach ($entity['files'] as $fileName => $file)
                                            <tr>
                                                @if ($loop->first)
                                                    <td rowspan="{{ count($entity['files']) }}">{{ $entity['name'] }}</td>
                                                    <td rowspan="{{ count($entity['files']) }}">{{ $entity['type'] }}</td>
                                                @endif
                                                <td>{{ $fileName }}</td>
                                                <td>
                                                    @php
                                                        $fileClass = match ($file['status']) {
                                                            'Uploaded' => 'badge-success',
                                                            'Not uploaded' => 'badge-danger',
                                                            default => 'badge-secondary',
                                                        };
                                                    @endphp

                                                    @if ($file['status'] === 'Uploaded' && $file['path'])
                                                        <a href="{{ supplierMedia($file['path']) }}" target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="badge badge-outline {{ $fileClass }}">
                                                            {{ translate($file['status']) }}
                                                        </a>
                                                    @else
                                                        <span class="badge badge-outline {{ $fileClass }}">
                                                            {{ translate($file['status']) }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-gray-500 py-4">
                                                {{ translate('No compliance reports found.') }}
                                            </td>
                                        </tr>
                                    @endforelse

                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->

                        @include('layouts.includes.table-pagination', ['paginator' => $complianceFiles])

                    </div>
                </div>
            </div>
        </div>

    </main>
@endsection
