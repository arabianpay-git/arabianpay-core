<div class="scrollable-x-auto">
    <table class="table table-auto table-border" data-datatable-table="true">
        <thead>
            <tr>
                <th class="w-[60px] text-center">{{ translate('ID') }}</th>
                <th>{{ translate('Name & Business') }}</th>
                <th>{{ translate('CR Number') }}</th>
                <th>{{ translate('Business Type') }}</th>
                <th>{{ translate('Original Status') }}</th>
                <th>{{ translate('Deleted At') }}</th>
                <th>{{ translate('Time in Trash') }}</th>
                <th>{{ translate('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($merchants as $item)
                @php
                    $isUserOnly = isset($item->is_user_only) && $item->is_user_only;
                    $businessName = $item->user->business_name ?? 'N/A';
                    $deletedAt = $item->deleted_at;
                    $timeAgo = $deletedAt ? $deletedAt->diffForHumans() : '—';
                    $daysInTrash = $deletedAt ? round($deletedAt->floatDiffInSeconds(now()) / 86400, 6) : 0;

                    $isOverdue = $daysInTrash > 30;
                @endphp
                <tr class="trash-row {{ $isOverdue ? 'overdue' : '' }}">
                    <td class="text-center">
                        @if ($isUserOnly)
                            User-{{ $item->user_id }}
                        @else
                            {{ $item->id }}
                        @endif
                    </td>
                    <td>
                        <div class="whitespace-nowrap">
                            <div class="font-medium text-gray-900">
                                @if ($isUserOnly)
                                    Phone: {{ $item->user->phone_number ?? 'N/A' }}
                                @else
                                    {{ $item->user->first_name ?? 'N/A' }} {{ $item->user->last_name ?? 'N/A' }}
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $businessName }}
                            </div>
                            @if ($isOverdue)
                                <div class="mt-1">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        <i class="ki-filled ki-warning text-xs mr-1"></i>
                                        Overdue for deletion
                                    </span>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if ($isUserOnly)
                            N/A
                        @else
                            {{ maskedSensitiveText('business_identity', $item->cr_number) ?: '—' }}
                        @endif
                    </td>
                    <td>
                        @if ($isUserOnly)
                            N/A
                        @else
                            {{ $item->businessType->name ?? 'N/A' }}
                        @endif
                    </td>
                    <td>
                        @if ($isUserOnly)
                            <span class="badge badge-sm badge-outline badge-warning">
                                Onboarding
                            </span>
                        @else
                            <span
                                class="badge badge-sm badge-outline
                                @switch($item->status)
                                    @case('under_review')
                                        badge-info
                                        @break
                                    @case('contract_sent')
                                        badge-primary
                                        @break
                                    @case('active')
                                        badge-success
                                        @break
                                    @case('pending')
                                        badge-warning
                                        @break
                                    @case('approved')
                                        badge-success
                                        @break
                                    @case('suspended')
                                        badge-dark
                                        @break
                                    @case('blacklisted')
                                        badge-danger
                                        @break
                                    @default
                                        badge-secondary
                                @endswitch
                            ">
                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                            </span>
                        @endif
                    </td>
                    <td class="deleted-at-cell text-center" data-deleted-at="{{ $deletedAt }}">
                        {{ $deletedAt ? $deletedAt->format('Y-m-d H:i:s') : '—' }}
                    </td>
                    <td class="text-center">
                        @if ($deletedAt)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-[5px] text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $timeAgo }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <!-- Restore Button -->
                            <button type="button" class="btn btn-sm btn-icon btn-clear btn-success restore-btn"
                                onclick="restoreMerchant({{ $item->id }}, '{{ addslashes($businessName) }}')"
                                title="{{ translate('Restore Supplier') }}">
                                <i class="ki-filled ki-arrow-right"></i>
                            </button>

                            <!-- View Details Button -->
                            <a class="btn btn-sm btn-icon btn-clear btn-primary"
                                title="{{ translate('View Details') }}"
                                href="{{ route('supplierProfile', ['id' => $item->user_id]) }}?trashed=true">
                                <i class="ki-filled ki-eye"></i>
                            </a>

                            <!-- Permanent Delete Button (Admin Only) -->
                            @if (Auth::user()->user_type === 'admin')
                                <button type="button" class="btn btn-sm btn-icon btn-clear btn-danger force-delete-btn"
                                    onclick="forceDeleteMerchant({{ $item->id }}, '{{ addslashes($businessName) }}')"
                                    title="{{ translate('Permanently Delete') }}">
                                    <i class="ki-filled ki-trash"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-8">
                        <div class="text-gray-400 mb-3">
                            <i class="ki-filled ki-trash" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-500 mb-2">
                            {{ translate('Trash is Empty') }}
                        </h4>
                        <p class="text-gray-400 mb-4">
                            {{ translate('No deleted suppliers found in trash.') }}
                        </p>
                        <a href="{{ route('suppliers') }}" class="btn btn-sm btn-primary">
                            <i class="ki-filled ki-arrow-left"></i>
                            {{ translate('Back to Suppliers') }}
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('layouts.includes.table-pagination', ['paginator' => $merchants])


@push('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function forceDeleteMerchant(merchantId, merchantName) {
            Swal.fire({
                title: '⚠️ PERMANENT DELETE',
                html: `<div class="text-left">
                <p>This will PERMANENTLY delete:</p>
                <ul class="list-disc pl-5 mt-2">
                    <li>Supplier: <strong>${merchantName}</strong></li>
                    <li>User account</li>
                    <li>All related data (orders, products, transactions)</li>
                </ul>
                <p class="mt-3 text-red-600 font-semibold">This action CANNOT be undone!</p>
            </div>`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete permanently',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/merchants/${merchantId}/force-delete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                redirect_to_trash: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Permanently Deleted!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = '/admin/suppliers/trashed';
                                });
                            } else {
                                Swal.fire('Error!', data.message || 'Unexpected response', 'error');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        });
                }
            });
        }

        function restoreMerchant(merchantId, merchantName) {
            Swal.fire({
                title: 'Restore Supplier?',
                text: `Restore "${merchantName}" from trash?`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, restore',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/merchants/${merchantId}/restore`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                redirect_to_suppliers: true
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Restored!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = '/admin/suppliers';
                                });
                            } else {
                                Swal.fire('Error!', data.message || 'Unexpected response', 'error');
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        });
                }
            });
        }

        window.forceDeleteMerchant = forceDeleteMerchant;
        window.restoreMerchant = restoreMerchant;
    </script>
@endpush
