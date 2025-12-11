<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <table class="min-w-full border-collapse">
        <thead class="bg-blue-50">
            <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">No</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">First Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Last Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Business Name</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Created At</th>
                <th class="px-4 py-3 text-sm font-semibold text-center text-gray-700">Action</th>
            </tr>
        </thead>
        <tbody id="merchant-body" class="divide-y divide-gray-200">
            @forelse ($users as $index => $user)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">{{ $user->id }}</td>
                    <td class="px-4 py-3">{{ $user->first_name }}</td>
                    <td class="px-4 py-3">{{ $user->last_name }}</td>
                    <td class="px-4 py-3">{{ $user->business_name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">{{ optional($user->created_at)->format(dateFormat()) }}</td>
                    <td class="px-4 py-3 text-center">
                        <button
                            class="edit-btn px-4 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            data-id="{{ $user->id }}" data-first="{{ e($user->first_name) }}"
                            data-last="{{ e($user->last_name) }}" data-business="{{ e($user->business_name) }}">
                            Edit
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-gray-500">No merchants found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($users->hasPages())
    <div class="mt-4">
        @include('layouts.includes.table-pagination', ['paginator' => $users])
    </div>
@endif
