@extends('layouts.base')

@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Dashboard Header -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-2xl font-bold leading-none text-gray-900">
                        {{ translate('Collection Dashboard') }}
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ translate('Monitor all collection activities in real-time') }}
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center gap-2">
                        <div class="flex gap-2">
                            <input type="text" id="date-from" name="date_from" class="input" placeholder="From Date"
                                style="height: 32px;" value="{{ request('date_from', '') }}">
                            <input type="text" id="date-to" name="date_to" class="input" placeholder="To Date"
                                style="height: 32px;" value="{{ request('date_to', '') }}">

                            <button class="btn btn-sm btn-primary" id="filter-btn" type="button">
                                <i class="ki-filled ki-filter-search"> </i>
                                {{ translate('Filter') }}
                            </button>
                        </div>

                        <button class="btn btn-sm btn-light" id="clear-filters-btn" type="button">
                            <i class="ki-filled ki-arrows-circle"> </i>
                            {{ translate('Clear') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Dashboard Header -->

        <!-- Dashboard Stats Cards -->
        @include('admin.collections.components.dashboard-cards')
        <!-- End of Dashboard Stats Cards -->

        <!-- Charts Section -->
        @include('admin.collections.components.dashboard-collection-reports')
        <!-- End of Charts Section -->

        <!-- Quick Actions -->
        @include('admin.collections.components.dashboard-quick-actions')
        <!-- End of Quick Actions -->

        <!-- Alerts & Flags Section -->
        @include('admin.collections.components.dashboard-alerts')
        <!-- End of Alerts & Flags Section -->

        <!-- Installments Table -->
        @include('admin.collections.components.dashboard-installments')
        <!-- End of Installments Table -->
    </main>
@endsection

@push('scripts')
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Flatpickr on both inputs
            const fromPicker = flatpickr("#date-from", {
                dateFormat: "Y-m-d",
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        toPicker.set('minDate', selectedDates[0]);
                    }
                }
            });

            const toPicker = flatpickr("#date-to", {
                dateFormat: "Y-m-d",
                onChange: function(selectedDates) {
                    if (selectedDates.length > 0) {
                        fromPicker.set('maxDate', selectedDates[0]);
                    }
                }
            });

            // Filter button: navigate with query params
            document.getElementById('filter-btn').addEventListener('click', function() {
                const from = document.getElementById('date-from').value;
                const to = document.getElementById('date-to').value;

                // Build query params
                const params = new URLSearchParams(window.location.search);

                if (from) params.set('date_from', from);
                else params.delete('date_from');

                if (to) params.set('date_to', to);
                else params.delete('date_to');

                // Navigate to the same path with updated query string
                window.location.href = window.location.pathname + '?' + params.toString();
            });

            // Clear filters: remove date params and reload
            document.getElementById('clear-filters-btn').addEventListener('click', function() {
                const params = new URLSearchParams(window.location.search);
                params.delete('date_from');
                params.delete('date_to');
                const qs = params.toString();
                window.location.href = window.location.pathname + (qs ? '?' + qs : '');
            });

            // Small notification helper (kept from your previous file)
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white ${
                    type === 'success' ? 'bg-success' : 'bg-info'
                } z-50 transform transition-transform duration-300 translate-x-full`;
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i class="ki-filled ki-${type === 'success' ? 'check-circle' : 'information'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.remove('translate-x-full');
                }, 100);
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }
        });
    </script>
@endpush
