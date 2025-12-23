@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <!-- begin: works -->
            <div class="flex flex-col items-stretch gap-5 lg:gap-7.5">
                <!-- begin: toolbar -->
                <div class="flex flex-wrap items-center gap-5 justify-between">
                    <div class="flex-wrap gap-2">
                        <h3 class="card-title font-medium mb-1.5">
                            {{ translate('Activity Logs') }}
                        </h3>
                        <div class="flex flex-wrap gap-2 lg:gap-5 items-center">
                            <div class="flex">
                                <form method="GET" action="{{ route('activity-logs.index') }}" class="flex">
                                    <label class="input input-sm">
                                        <i class="ki-filled ki-magnifier"></i>
                                        <input name="search" type="text"
                                            placeholder="{{ translate('Search by employee, action, module') }}"
                                            value="{{ request('search') }}" style="width: 492px;" />
                                    </label>
                                    <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 5px;">
                                        {{ translate('Search') }}
                                    </button>
                                </form>
                            </div>

                            <div class="flex gap-2 lg:gap-3">
                                <a href="{{ route('activity-logs.exportCsv', request()->only('search')) }}"
                                    class="btn btn-sm btn-outline btn-success flex items-center"
                                    title="{{ translate('Export CSV') }}">
                                    <i class="ki-filled ki-abstract-14"></i>
                                    {{ translate('Export CSV') }}
                                </a>

                                <a href="{{ route('activity-logs.exportPdf', request()->only('search')) }}"
                                    class="btn btn-sm btn-outline btn-danger flex items-center"
                                    title="{{ translate('Export PDF') }}">
                                    <i class="ki-filled ki-file-down"></i>
                                    {{ translate('Export PDF') }}
                                </a>
                            </div>
                        </div>

                    </div>

                </div>
                <!-- end: toolbar -->
                <!-- begin: list -->
                <div id="teams_list">
                    <div class="flex flex-col gap-1 lg:gap-1.5">
                        @foreach ($logs as $log)
                            @php
                                switch ($log->event) {
                                    case 'create':
                                        $txtColor = 'text-primary';
                                        $icon = 'ki-filled ki-add-folder';
                                        break;
                                    case 'view':
                                        $txtColor = 'text-success';
                                        $icon = 'ki-filled ki-eye';
                                        break;
                                    case 'update':
                                        $txtColor = 'text-warning';
                                        $icon = 'ki-filled ki-update-folder';
                                        break;
                                    case 'delete':
                                        $txtColor = 'text-danger';
                                        $icon = 'ki-filled ki-delete-folder';
                                        break;
                                    default:
                                        $txtColor = 'text-gray-600';
                                        $icon = 'ki-filled ki-update-folder';
                                        break;
                                }
                            @endphp
                            <div class="card p-1.5">
                                <div class="flex flex-wrap justify-between items-center gap-7">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex justify-center items-center size-8 shrink-0 rounded-full ring-1 ring-gray-300 bg-gray-100">
                                            <i class="ki-filled {{ $icon }} {{ $txtColor }}"></i>
                                        </div>
                                        <div class="grid grid-col gap-1">
                                            <a class="text-base font-medium text-gray-900 hover:text-primary-active mb-px"
                                                href="#">
                                                [{{ $log->event }}] <span
                                                    class="text-2sm text-gray-600">{{ $log->created_at->diffForHumans() }}
                                                    [{{ $log->created_at }}] -
                                                    {{ $log->causer->first_name ?? 'System' }}</span>
                                            </a>
                                            <span class="text-2sm text-gray-700">
                                                {{ $log->description }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-6 lg:gap-12">
                                        <div class="grid justify-end min-w-20">
                                            <a class="btn btn-light btn-sm" onclick="toggleDetails('{{ $log->id }}')">
                                                <i class="ki-filled ki-information-2 text-gray-600 me-1.5">
                                                </i>
                                                Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Details (Hidden by default) -->
                            <div id="details-{{ $log->id }}"
                                class="hidden mt-2 bg-gray-100 p-2 rounded text-xs font-mono whitespace-pre-wrap">
                                @if ($log->properties)
                                    @foreach ($log->properties->toArray() as $key => $value)
                                        <div class="flex justify-between text-xs text-gray-700 border-b py-1">
                                            <span class="font-medium">{{ $key }}</span>
                                            <span>
                                                @if (is_array($value))
                                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="flex grow justify-center pt-5 lg:pt-7.5">
                        {{ $logs->links() }}
                    </div>
                </div>
                <!-- end: list -->
            </div>
            <!-- end: works -->
        </div>
        <!-- End of Container -->
    </main>
@endsection

@push('scripts')
    <script>
        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            el.classList.toggle('hidden');
        }
    </script>
@endpush
