<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th>
                    <input class="checkbox checkbox-sm" data-datatable-check="true" type="checkbox"
                        id="select-all-checkbox">
                </th>
                <th class="w-[60px] text-center">{{ translate('ID') }}</th>
                <th>{{ translate('Name') }}</th>
                <th>{{ translate('CR Number') }}</th>
                <th>{{ translate('Business Type') }}</th>
                <th>{{ translate('Assigned To') }}</th>
                <th>{{ translate('Status') }}</th>
                <th>{{ translate('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($merchants as $item)
                <tr>
                    <td><input class="checkbox checkbox-sm row-checkbox" type="checkbox" name="ids[]"
                            value="{{ $item->id }}"></td>
                    <td class="text-center">{{ $item->id }}</td>
                    <td>
                        <div class="whitespace-nowrap">
                            {{ $item->user->first_name }} {{ $item->user->last_name }}
                            <br>
                            <small class="text-gray-500">— {{ $item->user?->business_name ?? '—' }}</small>
                        </div>
                    </td>
                    <td>{{ $item->cr_number }}</td>
                    <td>{{ $item->businessType->name ?? 'N/A' }}</td>
                    <td>{{ $item->assigned ? $item->assigned->first_name . ' ' . $item->assigned->last_name : __('--Not Assigned--') }}
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
                        <div class="flex gap-1">
                            <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                href="{{ route('supplierProfile', ['id' => $item->user_id]) }}">
                                <i class="ki-filled ki-notepad-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-icon btn-clear btn-info transfer-requests-btn"
                                data-modal-toggle="#transfer_detail" data-model-id="{{ $item->id }}"
                                data-model-type="App\Models\Merchant">
                                <i class="ki-filled ki-disconnect"></i>
                            </button>
                            @if (Auth::user()->user_type == 'admin')
                                <a target="__blank" class="btn btn-sm btn-icon btn-clear btn-warning"
                                    href="{{ route('impersonate.redirect', ['id' => $item->user_id]) }}"
                                    onclick="return confirm('Login to this partner account?')">
                                    <i class="ki-filled ki-wrench"></i>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-gray-500">
                        {{ __('No suppliers found.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('layouts.includes.table-pagination', ['paginator' => $merchants])
