@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ __('Transfer Requests') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- Container -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ __('Requests') }}</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#role_permission_table" placeholder="Search roles"
                                        type="text" value="" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-state-save="false" id="role_permission_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Model Type</th>
                                            <th>Model Info</th>
                                            <th>Description</th>
                                            <th>Requested At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($transferRequests as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->fromUser?->first_name ?? '-' }}</td>
                                                <td>{{ $item->toUser?->first_name ?? '-' }}</td>
                                                <td>{{ class_basename($item->model_type) }}</td>
                                                <td>
                                                    @if ($item->model_type === \App\Models\Customer::class && $item->model)
                                                        {{ $item->model->user?->first_name }}
                                                        {{ $item->model->user?->last_name }}
                                                    @elseif ($item->model)
                                                        ID: {{ $item->model->id }}
                                                    @else
                                                        <span class="text-red-500">Model not found</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($item->description)
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            data-modal-toggle="#transfer_description_modal"
                                                            data-description="{{ $item->description }}">
                                                            {{ __('View') }}
                                                        </button>
                                                    @else
                                                        -
                                                    @endif
                                                </td>

                                                <td>{{ $item->created_at->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-gray-500">No transfer requests
                                                    found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination -->
                            @include('layouts.includes.table-pagination', [
                                'paginator' => $transferRequests,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Description Modal -->
    <div class="modal" data-modal="true" id="transfer_description_modal">
        <div class="modal-content max-w-[600px] top-[15%]">
            <div class="modal-header py-4 px-5">
                <h1>{{ __('Transfer Request Description') }}</h1>
                <button class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body px-5 pt-0 pb-5">
                <p id="transfer_description_text" class="text-sm text-gray-800 whitespace-pre-line"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-modal-toggle="#transfer_description_modal"]').forEach(button => {
                button.addEventListener('click', function() {
                    const description = this.getAttribute('data-description') || '-';
                    document.getElementById('transfer_description_text').innerText = description;
                });
            });
        });
    </script>
@endpush
