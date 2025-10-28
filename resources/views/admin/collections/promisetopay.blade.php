@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Promise to Pay') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" data-modal-toggle="#promisetopay_modal">
                        {{ translate('Create New Promise') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Promises List') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex gap-2">
                                <form id="ptpFilterForm" method="GET" class="flex gap-2 items-center">
                                    <select name="status" class="select select-sm select-bordered"
                                        onchange="this.form.submit()">
                                        <option value="">{{ translate('Filter by Status') }}</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid
                                        </option>
                                        <option value="broken" {{ request('status') == 'broken' ? 'selected' : '' }}>Broken
                                        </option>
                                    </select>

                                    <select name="method" class="select select-sm select-bordered"
                                        onchange="this.form.submit()">
                                        <option value="">{{ translate('Filter by Method') }}</option>
                                        <option value="call" {{ request('method') == 'call' ? 'selected' : '' }}>Call
                                        </option>
                                        <option value="email" {{ request('method') == 'email' ? 'selected' : '' }}>Email
                                        </option>
                                    </select>

                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input type="text" name="search" value="{{ request('search') }}"
                                            placeholder="{{ translate('Search promises') }}" />
                                    </label>

                                    <!-- Clear Filter Button -->
                                    <a href="{{ route('collections.promisetopay') }}" class="btn btn-sm btn-light w-full">
                                        <i class="ki-filled ki-arrows-circle"></i>
                                        {{ translate('Clear Filter') }}
                                    </a>
                                </form>

                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="ptp_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('Client Name') }}</th>
                                            <th>{{ translate('Due Date') }}</th>
                                            <th>{{ translate('Amount') }}</th>
                                            <th>{{ translate('Contact Method') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="whitespace-nowrap">
                                                        <a href="{{ route('customerProfile', ['id' => $item->user_id]) }}"
                                                            class="underline">{{ $item->user?->business_name ?? $item->user?->first_name }}</a>
                                                        <br>
                                                        <small class="text-gray-500">—
                                                            {{ $item->user?->business_name ?? '—' }}</small>
                                                    </div>
                                                </td>
                                                <td>{{ optional($item->schedulePayment)->due_date?->format('d M Y') ?? '-' }}
                                                </td>
                                                <td>
                                                    <span class="icon-saudi_riyal"></span>
                                                    {{ number_format(optional($item->schedulePayment)->instalment_amount ?? 0, 2) }}
                                                </td>
                                                <td><span
                                                        class="badge badge-sm badge-outline badge-info">{{ ucfirst($item->method) }}</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $badge_class = match (
                                                            strtolower($item->schedulePayment->payment_status)
                                                        ) {
                                                            'pending' => 'badge-warning',
                                                            'paid' => 'badge-success',
                                                            'broken' => 'badge-danger',
                                                            default => 'badge-secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge badge-sm badge-outline {{ $badge_class }}">{{ ucfirst($item->schedulePayment->payment_status) }}</span>
                                                </td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary edit-promise"
                                                            data-id="{{ $item->id }}" href="#">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>

                                                        <a href="{{ route('promises.destroy', $item) }}"
                                                            class="btn btn-sm btn-icon btn-clear btn-danger promise-delete">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-gray-500 py-4">
                                                    {{ translate('No promises found.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="mt-4">
                                {{ $items->appends(request()->all())->links('layouts.includes.table-pagination') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('admin.collections.components.promise-to-pay-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Delete Promise with confirmation
            $(document).on('click', '.promise-delete', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                let row = $(this).closest('tr'); // Optional: row to remove after deletion

                Swal.fire({
                    icon: 'warning',
                    title: "{{ translate('Are you sure?') }}",
                    text: "{{ translate('This action cannot be undone!') }}",
                    showCancelButton: true,
                    confirmButtonText: "{{ translate('Yes, delete it!') }}",
                    cancelButtonText: "{{ translate('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                Swal.fire({
                                    icon: 'success',
                                    title: "{{ translate('Deleted!') }}",
                                    text: res.message ||
                                        "{{ translate('Promise deleted successfully.') }}",
                                    confirmButtonText: "{{ translate('OK') }}"
                                }).then(() => {
                                    // Optional: remove the row from table or reload page
                                    // row.remove();
                                    location.reload(); // reload page
                                });
                            },
                            error: function(xhr) {
                                let message = xhr.responseJSON?.message ||
                                    "{{ translate('Something went wrong.') }}";
                                Swal.fire({
                                    icon: 'error',
                                    title: "{{ translate('Error') }}",
                                    text: message
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
