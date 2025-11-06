@extends('layouts.base')
@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Installments') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" href="{{ route('collections.installmentsCalander') }}">
                        {{ translate('View Calendar') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Installments List') }}
                        </h3>
                        <div class="flex gap-2">
                            <select class="select select-sm select-bordered" id="filter-status">
                                <option value="">{{ translate('Filter by Status') }}</option>
                                <option value="pending">{{ translate('Pending') }}</option>
                                <option value="due">{{ translate('Due') }}</option>
                                <option value="late">{{ translate('Late') }}</option>
                                <option value="paid">{{ translate('Paid') }}</option>
                                <option value="failed">{{ translate('Failed') }}</option>
                            </select>

                            <input id="filter-from" placeholder="{{ translate('From Date') }}" type="text"
                                class="input input-sm" />
                            <input id="filter-to" placeholder="{{ translate('To Date') }}" type="text"
                                class="input input-sm" />

                            <label class="input input-sm">
                                <i class="ki-filled ki-magnifier"> </i>
                                <input id="filter-search" placeholder="{{ translate('Search installments') }}"
                                    type="text" value="" />
                            </label>
                        </div>

                    </div>
                    <div class="card-body">
                        <div id="installments_table_wrapper">
                            @include('admin.collections.partials.installments_table', [
                                'orders' => $orders,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('#filter-from', {
                dateFormat: 'Y-m-d'
            });
            flatpickr('#filter-to', {
                dateFormat: 'Y-m-d'
            });

            const statusSelect = document.getElementById('filter-status');
            const searchInput = document.getElementById('filter-search');
            const fromInput = document.getElementById('filter-from');
            const toInput = document.getElementById('filter-to');

            function fetchInstallments(url = null) {
                const status = encodeURIComponent(statusSelect.value);
                const search = encodeURIComponent(searchInput.value);
                const from = encodeURIComponent(fromInput.value);
                const to = encodeURIComponent(toInput.value);

                let fetchUrl = url ||
                    `{{ route('collections.installments') }}?status=${status}&search=${search}&from=${from}&to=${to}`;

                const newUrl = new URL(fetchUrl, window.location.origin);
                history.pushState({}, '', newUrl);

                fetch(fetchUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.text())
                    .then(html => {
                        document.querySelector('#installments_table_wrapper').innerHTML = html;
                        attachPaginationLinks();
                    });
            }

            statusSelect.addEventListener('change', () => fetchInstallments());
            searchInput.addEventListener('input', function() {
                clearTimeout(this._timeout);
                this._timeout = setTimeout(fetchInstallments, 500);
            });
            fromInput.addEventListener('change', () => fetchInstallments());
            toInput.addEventListener('change', () => fetchInstallments());

            function attachPaginationLinks() {
                document.querySelectorAll('#installments_table_wrapper .pagination a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        fetchInstallments(this.href);
                    });
                });
            }

            attachPaginationLinks();

            window.addEventListener('popstate', () => {
                const params = new URLSearchParams(window.location.search);
                statusSelect.value = params.get('status') || '';
                searchInput.value = params.get('search') || '';
                fromInput.value = params.get('from') || '';
                toInput.value = params.get('to') || '';
                fetchInstallments();
            });
        });
    </script>
@endpush
