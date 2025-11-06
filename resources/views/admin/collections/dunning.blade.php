@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Dunning Management') }}
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <a class="btn btn-sm btn-light" data-modal-toggle="#template_modal">
                        {{ translate('Create New Template') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="card card-grid min-w-full">
                    <div class="card-header flex-wrap gap-2">
                        <h3 class="card-title font-medium text-sm">
                            {{ translate('Dunning Templates & Tasks') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5">
                            <div class="flex gap-2">
                                <form id="dunningFilterForm" method="GET" class="flex gap-2">
                                    <select name="type" class="select select-sm select-bordered"
                                        onchange="this.form.submit()">
                                        <option value="">{{ translate('Filter by Channel') }}</option>
                                        <option value="sms" {{ request('type') == 'sms' ? 'selected' : '' }}>SMS</option>
                                        <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email
                                        </option>
                                        <option value="ivr" {{ request('type') == 'ivr' ? 'selected' : '' }}>IVR</option>
                                        <option value="task" {{ request('type') == 'task' ? 'selected' : '' }}>Agent Task
                                        </option>
                                    </select>

                                    <select name="dpd_bucket" class="select select-sm select-bordered"
                                        onchange="this.form.submit()">
                                        <option value="">{{ translate('Filter by DPD Bucket') }}</option>
                                        <option value="0-3" {{ request('dpd_bucket') == '0-3' ? 'selected' : '' }}>0-3
                                            DPD</option>
                                        <option value="4-7" {{ request('dpd_bucket') == '4-7' ? 'selected' : '' }}>4-7
                                            DPD</option>
                                        <option value="8-15" {{ request('dpd_bucket') == '8-15' ? 'selected' : '' }}>8-15
                                            DPD</option>
                                    </select>

                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input type="text" name="search" value="{{ request('search') }}"
                                            placeholder="{{ translate('Search templates') }}" />
                                    </label>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div data-datatable="true" data-datatable-city-save="false" id="dunning_table">
                            <div class="scrollable-x-auto">
                                <table class="table table-auto table-border" data-datatable-table="true">
                                    <thead>
                                        <tr>
                                            <th class="w-[60px] text-center">{{ translate('No') }}</th>
                                            <th>{{ translate('Name') }}</th>
                                            <th>{{ translate('DPD Bucket') }}</th>
                                            <th>{{ translate('Type') }}</th>
                                            <th>{{ translate('Language') }}</th>
                                            <th>{{ translate('Throttling') }}</th>
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $item)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    <span class="badge badge-sm badge-outline badge-primary">
                                                        {{ $item->dpd_bucket }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $badge_class = match (strtolower($item->type)) {
                                                            'sms' => 'badge-success',
                                                            'email' => 'badge-info',
                                                            'ivr' => 'badge-warning',
                                                            'task', 'agent task' => 'badge-danger',
                                                            default => 'badge-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge badge-sm badge-outline {{ $badge_class }}">
                                                        {{ ucfirst($item->type) }}
                                                    </span>
                                                </td>
                                                <td>{{ strtoupper($item->language) }}</td>
                                                <td>{{ $item->throttling }}</td>
                                                <td>
                                                    <div class="flex gap-1">
                                                        <a class="btn btn-sm btn-icon btn-clear btn-primary edit-template"
                                                            data-id="{{ $item->id }}" href="javascript:void(0);">
                                                            <i class="ki-filled ki-notepad-edit"></i>
                                                        </a>
                                                        <a class="btn btn-sm btn-icon btn-clear btn-danger delete-template"
                                                            data-id="{{ $item->id }}" href="javascript:void(0);">
                                                            <i class="ki-filled ki-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-gray-500 py-4">
                                                    {{ translate('No templates found.') }}
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

    {{-- Create & Edit Modal --}}
    @include('admin.collections.components.dunning-template-create-edit-modal')
@endsection

@push('scripts')
    <script>
        // Edit template
        $(document).on('click', '.edit-template', function() {
            let id = $(this).data('id');
            $.ajax({
                url: "{{ url('admin/dunning-templates') }}/" + id,
                type: "GET",
                success: function(response) {
                    if (response.success) {
                        let item = response.data;
                        let modal = $('#template_modal');
                        modal.find('form').attr('action', "{{ url('admin/dunning-templates') }}/" +
                        id);
                        modal.find('input[name=name]').val(item.name);
                        modal.find('select[name=type]').val(item.type);
                        modal.find('select[name=dpd_bucket]').val(item.dpd_bucket);
                        modal.find('select[name=throttling]').val(item.throttling);
                        modal.find('select[name=language]').val(item.language);
                        modal.find('textarea[name=message]').val(item.message);
                        modal.find('button[type=submit]').text('{{ translate('Update Template') }}');
                        modal.attr('data-mode', 'edit');
                        modal.modal('show');
                    }
                }
            });
        });
    </script>
@endpush
