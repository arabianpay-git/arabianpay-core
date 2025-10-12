@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Third Party API Control') }}
                    </h1>
                    <p class="text-sm text-gray-500">
                        {{ translate('Manage and control integrations with connected third-party services.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Cards Layout -->
        <div class="container-fixed">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                <!-- API Card Example -->
                <div
                    class="card card-grid border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 rounded-2xl">
                    <div class="card-body flex flex-col gap-4">
                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full overflow-hidden">
                                    <img src="{{ asset('images/api-sample.png') }}" alt="API Icon" class="w-6 h-6">
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-800">
                                        {{ translate('Payment Gateway') }}
                                    </h4>
                                    <p class="text-xs text-gray-500">
                                        {{ translate('Controls online payment processing integration.') }}
                                    </p>
                                </div>
                            </div>
                            <!-- Switch -->
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="checkbox">
                                <div
                                    class="relative w-10 h-5 bg-gray-300 rounded-full peer-checked:bg-primary transition-colors duration-300">
                                    <span
                                        class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-5"></span>
                                </div>
                            </label>
                        </div>

                        <!-- Info Section -->
                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ translate('API Key') }}</span>
                                <span class="font-medium text-gray-800">••••••••••</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ translate('Status') }}</span>
                                <span class="badge badge-sm badge-success">{{ translate('Enabled') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ translate('Last Synced') }}</span>
                                <span>08 Oct 2025, 09:42 AM</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end mt-3">
                            <a href="#" class="btn btn-sm btn-light-primary flex items-center gap-2">
                                <i class="ki-filled ki-setting"></i>
                                {{ translate('Settings') }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Another API Example -->
                <div
                    class="card card-grid border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 rounded-2xl">
                    <div class="card-body flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center w-12 h-12 bg-gray-100 rounded-full overflow-hidden">
                                    <img src="{{ asset('images/api-sample2.png') }}" alt="API Icon" class="w-6 h-6">
                                </div>
                                <div>
                                    <h4 class="text-base font-semibold text-gray-800">
                                        {{ translate('Shipping Provider') }}
                                    </h4>
                                    <p class="text-xs text-gray-500">
                                        {{ translate('Handles delivery and logistics integration.') }}
                                    </p>
                                </div>
                            </div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div
                                    class="relative w-10 h-5 bg-gray-300 rounded-full peer-checked:bg-primary transition-colors duration-300">
                                    <span
                                        class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-5"></span>
                                </div>
                            </label>
                        </div>

                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ translate('API Key') }}</span>
                                <span class="font-medium text-gray-800">{{ translate('Not Set') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ translate('Status') }}</span>
                                <span class="badge badge-sm badge-secondary">{{ translate('Disabled') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ translate('Last Synced') }}</span>
                                <span>{{ translate('Never') }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end mt-3">
                            <a href="#" class="btn btn-sm btn-light-primary flex items-center gap-2">
                                <i class="ki-filled ki-setting"></i>
                                {{ translate('Settings') }}
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
@endsection
