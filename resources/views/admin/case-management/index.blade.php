@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Case Management') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('case-management.create') }}">
                        {{ translate('Create New Case') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Cases') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#cases_table" placeholder="{{ translate('Search') }}"
                                        type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="cases_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('ID') }}</th>
                                            <th>{{ translate('Title') }}</th>
                                            <th>{{ translate('Assigned User') }}</th>
                                            <th>{{ translate('Description') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                            <th>{{ translate('Priority') }}</th>
                                            <th>{{ translate('Due Date') }}</th>
                                            <th class="text-center">{{ translate('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cases as $item)
                                            <tr>
                                                <td class="text-center">{{ $item->id }}</td>
                                                <td>{{ $item->title }}</td>
                                                <td>
                                                    @if ($item->user)
                                                        {{ $item->user->first_name }} {{ $item->user->last_name ?? '' }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($item->description)
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            data-modal-toggle="#case_description_modal"
                                                            data-description="{{ $item->description }}">
                                                            {{ translate('View') }}
                                                        </button>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColors = [
                                                            'open' => 'badge badge-sm badge-outline  badge-primary',
                                                            'in_progress' => 'badge badge-sm badge-outline badge-info',
                                                            'resolved' => 'badge badge-sm badge-outline badge-success',
                                                            'closed' => 'badge badge-sm badge-outline badge-secondary',
                                                        ];
                                                        $statusLabel = ucfirst(str_replace('_', ' ', $item->status));
                                                        $statusClass =
                                                            $statusColors[$item->status] ??
                                                            'badge badge-sm badge-outline badge-light';
                                                    @endphp
                                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                                </td>

                                                <td>
                                                    @php
                                                        $priorityColors = [
                                                            'low' => 'badge badge-sm badge-outline badge-success',
                                                            'medium' => 'badge badge-sm badge-outline badge-warning',
                                                            'high' => 'badge badge-sm badge-outline badge-danger',
                                                        ];
                                                        $priorityLabel = ucfirst($item->priority);
                                                        $priorityClass =
                                                            $priorityColors[$item->priority] ??
                                                            'badge badge-sm badge-outline badge-light';
                                                    @endphp
                                                    <span class="{{ $priorityClass }}">{{ $priorityLabel }}</span>
                                                </td>

                                                <td>{{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('Y-m-d') : '-' }}
                                                </td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                                            href="{{ route('case-management.edit', $item->id) }}">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-btn"
                                                            href="{{ route('case-management.destroy', $item->id) }}">
                                                            <i class="ki-filled ki-trash"> </i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination Footer -->
                            @include('layouts.includes.table-pagination', ['paginator' => $cases])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Description Modal -->
    <div class="modal" data-modal="true" id="case_description_modal">
        <div class="modal-content max-w-[600px] top-[15%]">
            <div class="modal-header py-4 px-5">
                <h1>{{ translate('Case Description') }}</h1>
                <button class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body px-5 pt-0 pb-5">
                <p id="case_description" class="text-sm text-gray-800 whitespace-pre-line"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-modal-toggle="#case_description_modal"]').forEach(button => {
                button.addEventListener('click', function() {
                    const description = this.getAttribute('data-description') || '-';
                    document.getElementById('case_description').innerText = description;
                });
            });
        });
    </script>
@endpush
