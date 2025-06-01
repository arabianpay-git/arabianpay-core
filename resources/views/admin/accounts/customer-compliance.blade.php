@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <style>
            .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
            }

            .dark .hero-bg {
                background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
            }
        </style>

        <div class="bg-center bg-cover bg-no-repeat hero-bg">
            @include('admin.accounts.includes.customer')
        </div>

        @include('admin.accounts.includes.customer-header')

        <div class="container-fixed">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Customer Compliance</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
                <div class="col-span-1 lg:col-span-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Compliance Documents</h3>
                        </div>
                        <div class="card-body space-y-6">

                            @php
                                $compliance = [
                                    [
                                        'title' => 'ID Document',
                                        'file' => $customer->id_document,
                                        'status' => $customer->id_status,
                                    ],
                                    [
                                        'title' => 'CR Certificate',
                                        'file' => $customer->cr_certificate,
                                        'status' => $customer->cr_status,
                                    ],
                                    [
                                        'title' => 'VAT Certificate',
                                        'file' => $customer->vat_certificate,
                                        'status' => $customer->vat_status,
                                    ],
                                    [
                                        'title' => 'Registration Form',
                                        'file' => $customer->registration_number_form,
                                        'status' => null,
                                    ],
                                    [
                                        'title' => 'VAT Register File',
                                        'file' => $customer->vat_register_file,
                                        'status' => null,
                                    ],
                                    [
                                        'title' => 'Return Policy File',
                                        'file' => $customer->return_policy_file,
                                        'status' => null,
                                    ],
                                    [
                                        'title' => 'Exchange Policy File',
                                        'file' => $customer->exchange_policy_file,
                                        'status' => null,
                                    ],
                                    [
                                        'title' => 'Cancel Policy File',
                                        'file' => $customer->cancel_policy_file,
                                        'status' => null,
                                    ],
                                    [
                                        'title' => 'Owner Iqama Image',
                                        'file' => $customer->owner_iqama_image,
                                        'status' => null,
                                    ],
                                ];
                            @endphp


                            @foreach ($compliance as $item)
                                <div
                                    class="border p-4 mt-2 rounded-xl shadow-sm bg-white dark:bg-gray-800 transition-all duration-200 hover:shadow-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            @if ($item['file'])
                                                <img src="{{ asset('assets/media/images/check.png') }}" alt="checked"
                                                    class="w-6 h-6">
                                            @endif

                                            <h4 class="text-lg font-medium text-gray-800 dark:text-white">
                                                {{ $item['title'] }}</h4>
                                        </div>

                                        @if ($item['file'])
                                            <a href="{{ asset($item['file']) }}" target="_blank"
                                                class="inline-block px-3 py-1 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition duration-150">
                                                View
                                            </a>
                                        @else
                                            <span class="text-red-500 text-sm italic">Not uploaded</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
