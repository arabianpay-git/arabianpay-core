@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>
        <!-- End of Container -->

        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>

        <!-- Hero Section -->
        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            <!-- Container -->
            <div class="container-fixed py-8">
                <div class="bg-white backdrop-blur-sm rounded-xl p-6 shadow-lg border border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lean Open Banking</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">
                                Integrate with Lean Open Banking API to fetch bank and entity data.
                            </p>
                            <div class="flex items-center gap-3 mt-3">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ config('lean.environment') === 'sandbox' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' }}">
                                    {{ ucfirst(config('lean.environment')) }} Environment
                                </span>
                                <button id="testConnectionBtn" class="btn btn-sm btn-outline btn-secondary">
                                    <i class="ki-filled ki-disconnect"></i>
                                    Test Connection
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button id="refreshTokenBtn" class="btn btn-sm btn-outline btn-secondary">
                                <i class="ki-filled ki-arrows-circle"></i>
                                Refresh Token
                            </button>
                            <a href="{{ route('customers', $customer->id) }}" class="btn btn-sm btn-primary">
                                <i class="ki-filled ki-arrow-left mr-2"></i>
                                Back to Customer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Container -->
        </div>

        <!-- Customer Info -->
        @include('admin.accounts.includes.customer-header')

        <!-- Main Container -->
        <div class="container-fixed mt-6">
            <!-- Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 lg:gap-8">

                <!-- Banks Component -->
                <div class="col-span-1">
                    @include('admin.accounts.lean.components.banks')
                </div>

                <!-- Entities Component -->
                <div class="col-span-1">
                    @include('admin.accounts.lean.components.entities')
                </div>

                <div class="col-span-1">
                    @include('admin.accounts.lean.components.bank-statement')
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        .flatpickr-calendar {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .dark .flatpickr-calendar {
            background: #1f2937;
            border-color: #374151;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .dark .card {
            background: #1f2937;
            border-color: #374151;
        }

        .card-header {
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }

        .dark .card-header {
            border-color: #374151;
        }

        .card-body {
            padding: 1.5rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Swal = window.Swal;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Test Connection Button
            document.getElementById('testConnectionBtn')?.addEventListener('click', async function() {
                const btn = this;
                const originalHtml = btn.innerHTML;

                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';
                btn.disabled = true;

                try {
                    const response = await fetch(`/admin/lean/test-connection`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Connection Successful!',
                            text: data.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Failed',
                            text: data.message,
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Failed to test connection: ' + error.message,
                        confirmButtonText: 'OK'
                    });
                } finally {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });

            // Refresh Token Button
            document.getElementById('refreshTokenBtn')?.addEventListener('click', async function() {
                const btn = this;
                const originalHtml = btn.innerHTML;

                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
                btn.disabled = true;

                try {
                    const response = await fetch(`/admin/lean/clear-cache`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Token Refreshed!',
                            text: data.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Refresh Failed',
                            text: data.message,
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Refresh Error',
                        text: 'Failed to refresh token: ' + error.message,
                        confirmButtonText: 'OK'
                    });
                } finally {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });
        });
    </script>
@endpush
