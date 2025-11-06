@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">{{ translate('Payment Allocations') }}</h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ translate('Allocation History') }}</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex gap-2">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"> </i>
                                    <input data-datatable-search="#allocations_table"
                                        placeholder="{{ translate('Search order') }}" type="text" />
                                </label>

                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="allocations_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('Client') }}</th>
                                            <th>{{ translate('Order Tracking') }}</th>
                                            <th>{{ translate('Total Received') }}</th>
                                            <th>{{ translate('Allocation Method') }}</th>
                                            <th>{{ translate('Allocated to Principal') }}</th>
                                            <th>{{ translate('Allocated to Fees/Penalties') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    @include('admin.collections.partials.allocations_table', [
                                        'groupedPayments' => $groupedPayments,
                                    ])
                                </table>
                            </div>

                            <div id="allocations_pagination_container">
                                @include('layouts.includes.table-pagination', [
                                    'paginator' => $schedulePayments,
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        const searchInput = document.querySelector('input[data-datatable-search="#allocations_table"]');

        function updateTable(query) {
            const url = new URL(window.location.href);

            if (query) {
                url.searchParams.set('q', query);
            } else {
                url.searchParams.delete('q');
            }

            // ✅ This updates the visible URL in the browser
            window.history.pushState({}, '', url);

            // Fetch filtered data
            fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    document.querySelector('#allocations_table tbody').outerHTML = data.html;
                });
        }

        // Debounce typing
        let typingTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(typingTimer);
            const query = this.value.trim();
            typingTimer = setTimeout(() => updateTable(query), 300);
        });

        // Restore search value from URL
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            if (params.has('q')) {
                searchInput.value = params.get('q');
            }
        });

        // Handle back/forward buttons
        window.addEventListener('popstate', () => {
            const params = new URLSearchParams(window.location.search);
            const query = params.get('q') || '';
            searchInput.value = query;
            updateTable(query);
        });
    </script>
@endpush
