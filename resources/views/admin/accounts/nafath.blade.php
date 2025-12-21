@extends('layouts.base')

@section('content')
    @push('styles')
        <style>
            .nested-json-table {
                border: 1px solid #ddd;
                margin-bottom: 1rem;
                font-size: 0.9rem;
            }

            .nested-json-table td {
                border: 1px solid #ddd;
                padding: 6px 10px;
                vertical-align: top;
                word-break: break-word;
                background-color: #fafafa;
            }

            .nested-json-table tr:nth-child(even) td {
                background-color: #f0f0f0;
            }

            .nested-json-table tr:hover td {
                background-color: #e8f0fe;
            }
        </style>
    @endpush
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <!-- Header -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">{{ translate('Nafath') }}</h1>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">{{ translate('Nafath') }}</h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex">
                                <label class="input input-sm">
                                    <i class="ki-filled ki-magnifier"></i>
                                    <input data-datatable-search="#team_crew_table" placeholder="Search users"
                                        type="text" />
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="team_crew_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border">
                                    <thead>
                                        <tr>
                                            <th>{{ translate('ID') }}</th>
                                            <th>{{ translate('Name') }}</th>
                                            <th>{{ translate('National Id') }}</th>
                                            <th>{{ translate('Phone Number') }}</th>
                                            <th>{{ translate('Nafath Status') }}</th>
                                            <th>{{ translate('Nafath Response') }}</th>
                                            <th>{{ translate('Wathiq Status') }}</th>
                                            <th>{{ translate('Wathiq Reject Reason') }}</th>
                                            <th>{{ translate('Wathiq Response') }}</th>
                                            <th>{{ translate('Joining Date') }}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($nafath as $item)
                                            <tr>
                                                <td class="text-center">{{ $item->id }}</td>
                                                <td>
                                                    @php
                                                        $nafathData = $item->nafath_response;
                                                        $fullName =
                                                            is_array($nafathData) && isset($nafathData['full_name#en'])
                                                                ? $nafathData['full_name#en']
                                                                : '--';
                                                    @endphp
                                                    {{ $fullName }}
                                                </td>
                                                <td>
                                                    @php
                                                        $idValue =
                                                            is_array($nafathData) && isset($nafathData['id'])
                                                                ? (string) $nafathData['id']
                                                                : null;
                                                    @endphp
                                                    {{ $idValue ? maskedSensitiveText('national_id_iqama', $idValue) : '--' }}
                                                </td>

                                                <td>{{ $item->phone_number ? maskedSensitiveText('phone_number', $item->phone_number) : '--' }}
                                                </td>

                                                <td>
                                                    <span
                                                        class="badge badge-sm badge-outline 
                    @if ($item->status == 'approved') badge-success
                    @elseif($item->status == 'pending') badge-danger
                    @else badge-warning @endif">
                                                        {{ ucfirst($item->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{-- Only show Nafath button if authorized --}}
                                                    @if (hasSensitivePermission('national_id_iqama') && !empty($item->nafath_response))
                                                        <button type="button"
                                                            class="btn btn-sm btn-primary btn-show-nafath"
                                                            data-modal-toggle="#nafathModal"
                                                            data-response='@json($item->nafath_response)'>
                                                            {{ translate('View Nafath') }}
                                                        </button>
                                                    @else
                                                        <span
                                                            class="text-xs text-gray-400 italic">{{ translate('Access Restricted') }}</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <span
                                                        class="badge badge-sm badge-outline 
                                                        @if ($item->wathiq_status == 'approved') badge-success
                                                        @elseif($item->wathiq_status == 'rejected') badge-danger
                                                        @else badge-warning @endif">
                                                        {{ ucfirst($item->wathiq_status ?? 'N/A') }}
                                                    </span>
                                                </td>
                                                <td>{{ $item->reject_reason ?? '—' }}</td>
                                                <td>
                                                    {{-- Only show Wathiq button if authorized --}}
                                                    @if (hasSensitivePermission('business_identity') && !empty($item->cr_data))
                                                        <button type="button"
                                                            class="btn btn-sm btn-primary btn-show-wathiq"
                                                            data-modal-toggle="#wathiqModal"
                                                            data-response='@json($item->cr_data)'>
                                                            {{ translate('View Wathiq') }}
                                                        </button>
                                                    @else
                                                        <span
                                                            class="text-xs text-gray-400 italic">{{ translate('Access Restricted') }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $item->created_at->format(dateFormat()) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @include('layouts.includes.table-pagination', ['paginator' => $nafath])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Nafath Response Modal -->
    <div class="modal" data-modal="true" id="nafathModal">
        <div class="modal-content max-w-[600px] top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">{{ translate('Nafath Response') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body px-5 py-4">
                <ul id="nafathResponseList" class="list-group list-group-flush space-y-2"></ul>
            </div>
            <div class="modal-footer px-5 py-3">
                <button type="button" class="btn btn-secondary"
                    data-modal-dismiss="true">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>

    <!-- Wathiq Response Modal -->
    <div class="modal" data-modal="true" id="wathiqModal">
        <div class="modal-content top-[5%]">
            <div class="modal-header py-4 px-5">
                <h5 class="modal-title">{{ translate('Wathiq Response') }}</h5>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>
            <div class="modal-body px-5 py-4">
                <ul id="wathiqResponseList" class="list-group list-group-flush space-y-2"></ul>
            </div>
            <div class="modal-footer px-5 py-3">
                <button type="button" class="btn btn-secondary"
                    data-modal-dismiss="true">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function createTableFromObject(data) {
            // Create a table element
            const table = document.createElement('table');
            table.style.width = '100%';
            table.style.borderCollapse = 'collapse';
            table.className = 'nested-json-table';

            const tbody = document.createElement('tbody');

            if (data === null || data === undefined) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 2;
                td.style.fontStyle = 'italic';
                td.style.color = '#666';
                td.textContent = '(null)';
                tr.appendChild(td);
                tbody.appendChild(tr);
                table.appendChild(tbody);
                return table;
            }

            if (typeof data !== 'object') {
                const tr = document.createElement('tr');
                const tdKey = document.createElement('td');
                const tdVal = document.createElement('td');
                tdKey.style.fontWeight = 'bold';
                tdKey.textContent = 'Value';
                tdVal.textContent = data.toString();
                tr.appendChild(tdKey);
                tr.appendChild(tdVal);
                tbody.appendChild(tr);
                table.appendChild(tbody);
                return table;
            }

            if (Array.isArray(data)) {
                if (data.length === 0) {
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.colSpan = 2;
                    td.style.fontStyle = 'italic';
                    td.style.color = '#666';
                    td.textContent = '(Empty array)';
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                    table.appendChild(tbody);
                    return table;
                }

                data.forEach((item, idx) => {
                    const tr = document.createElement('tr');
                    const tdKey = document.createElement('td');
                    tdKey.style.fontWeight = 'bold';
                    tdKey.style.verticalAlign = 'top';
                    tdKey.textContent = `Item ${idx + 1}`;

                    const tdVal = document.createElement('td');
                    if (item && typeof item === 'object') {
                        tdVal.appendChild(createTableFromObject(item));
                    } else {
                        tdVal.textContent = item === null ? '(null)' : item.toString();
                    }
                    tr.appendChild(tdKey);
                    tr.appendChild(tdVal);
                    tbody.appendChild(tr);
                });

                table.appendChild(tbody);
                return table;
            }

            // Plain object
            const keys = Object.keys(data);
            if (keys.length === 0) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 2;
                td.style.fontStyle = 'italic';
                td.style.color = '#666';
                td.textContent = '(Empty object)';
                tr.appendChild(td);
                tbody.appendChild(tr);
                table.appendChild(tbody);
                return table;
            }

            keys.forEach(key => {
                const tr = document.createElement('tr');

                const tdKey = document.createElement('td');
                tdKey.style.fontWeight = 'bold';
                tdKey.style.verticalAlign = 'top';
                tdKey.style.width = '30%';
                tdKey.style.paddingRight = '10px';
                tdKey.textContent = key;

                const tdVal = document.createElement('td');
                const value = data[key];

                if (value === null || value === undefined) {
                    tdVal.style.fontStyle = 'italic';
                    tdVal.style.color = '#666';
                    tdVal.textContent = '(null)';
                } else if (typeof value === 'object') {
                    tdVal.appendChild(createTableFromObject(value));
                } else {
                    tdVal.textContent = value.toString();
                }

                tr.appendChild(tdKey);
                tr.appendChild(tdVal);
                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            return table;
        }

        function populateModalList(targetId, data) {
            const container = document.getElementById(targetId);
            container.innerHTML = '';
            if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
                container.innerHTML = '<div class="text-danger px-3 py-2">No response data available.</div>';
                return;
            }
            const table = createTableFromObject(data);
            container.appendChild(table);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-show-nafath').forEach(button => {
                button.addEventListener('click', function() {
                    let response = this.getAttribute('data-response') || '{}';
                    try {
                        response = JSON.parse(response);
                    } catch {
                        response = {};
                    }
                    populateModalList('nafathResponseList', response);
                });
            });

            document.querySelectorAll('.btn-show-wathiq').forEach(button => {
                button.addEventListener('click', function() {
                    let response = this.getAttribute('data-response') || '{}';
                    try {
                        response = JSON.parse(response);
                        if (typeof response === 'string') response = JSON.parse(response);
                    } catch {
                        response = {};
                    }
                    populateModalList('wathiqResponseList', response);
                });
            });
        });
    </script>
@endpush
