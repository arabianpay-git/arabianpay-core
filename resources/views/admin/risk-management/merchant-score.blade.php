@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .risk-tab-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100px;
            }

            .risk-tab-buttons {
                display: flex;
                gap: 10px;
            }

            .risk-tab-buttons a {
                padding: 8px 20px;
                border-radius: 8px;
                font-weight: 600;
                text-decoration: none;
                color: #4B5563;
                background-color: #F3F4F6;
                transition: all 0.2s ease;
                box-shadow: none;
            }

            .risk-tab-buttons a:hover {
                background-color: #E5E7EB;
                color: #1F2937;
            }

            .risk-tab-buttons a.active {
                background-color: #3B82F6;
                color: #FFFFFF;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }
        </style>
    @endpush
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex justify-between gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Merchant Risk Score') }}
                    </h1>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <ul class="risk-tab-buttons">
                            <li>
                                <a href="{{ route('risk.merchantScore', array_merge(request()->query(), ['type' => 'merchant'])) }}"
                                    class="{{ request('type') === 'merchant' ? 'active' : '' }}">
                                    {{ translate('Supplier') }}
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('risk.merchantScore', array_merge(request()->query(), ['type' => 'user'])) }}"
                                    class="{{ request('type') === 'user' || !request('type') ? 'active' : '' }}">
                                    {{ translate('Merchant') }}
                                </a>
                            </li>
                        </ul>

                        <div class="flex flex-wrap gap-2 lg:gap-5 items-center mt-3">
                            <div class="flex">
                                <form id="risk-search-form" method="GET" action="{{ route('risk.merchantScore') }}"
                                    class="flex">
                                    <input type="hidden" name="type" value="{{ request('type') }}">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input name="search" type="text"
                                            placeholder="{{ translate('Search by first name, last name, email, business name, phone number') }}"
                                            value="{{ request('search') }}" style="width: 492px;" />
                                    </label>

                                    <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                                        {{ translate('Search') }}
                                    </button>
                                </form>
                                <a href="{{ route('risk.merchantScore', array_merge(request()->query(), ['search' => ''])) }}"
                                    class="btn btn-sm btn-light" style="margin-left: 5px;">
                                    <i class="ki-filled ki-arrows-circle"></i>{{ translate('Clear') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        {{-- include the table partial --}}
                        @include('admin.risk-management.components.merchant-score-table', [
                            'risks' => $risks,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
