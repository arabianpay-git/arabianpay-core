@props([
    'createdBy' => null,
    'createdAt' => null,
    'approvedBy' => null,
    'approvedAt' => null,
    'paidBy' => null,
    'paidAt' => null,
])

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-3 gap-4']) }}>
    {{-- Created --}}
    <div class="flex items-start gap-2.5 p-3 rounded-lg bg-gray-50">
        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
            <i class="ki-filled ki-plus-square text-gray-500 text-xs"></i>
        </div>
        <div>
            <p class="text-2xs text-gray-400 uppercase font-medium">Created By</p>
            @if($createdBy)
                <p class="text-sm font-medium text-gray-800">
                    {{ $createdBy->first_name ?? '' }} {{ $createdBy->last_name ?? '' }}
                </p>
                @if($createdAt)
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($createdAt)->format('M d, Y H:i') }}</p>
                @endif
            @else
                <p class="text-sm text-gray-400">-</p>
            @endif
        </div>
    </div>

    {{-- Approved --}}
    <div class="flex items-start gap-2.5 p-3 rounded-lg {{ $approvedBy ? 'bg-blue-50' : 'bg-gray-50' }}">
        <div class="w-8 h-8 rounded-full {{ $approvedBy ? 'bg-blue-200' : 'bg-gray-200' }} flex items-center justify-center flex-shrink-0">
            <i class="ki-filled ki-check-circle {{ $approvedBy ? 'text-blue-600' : 'text-gray-400' }} text-xs"></i>
        </div>
        <div>
            <p class="text-2xs text-gray-400 uppercase font-medium">Approved By</p>
            @if($approvedBy)
                <p class="text-sm font-medium text-gray-800">
                    {{ $approvedBy->first_name ?? '' }} {{ $approvedBy->last_name ?? '' }}
                </p>
                @if($approvedAt)
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($approvedAt)->format('M d, Y H:i') }}</p>
                @endif
            @else
                <p class="text-sm text-gray-400">Awaiting approval</p>
            @endif
        </div>
    </div>

    {{-- Paid --}}
    <div class="flex items-start gap-2.5 p-3 rounded-lg {{ $paidBy ? 'bg-green-50' : 'bg-gray-50' }}">
        <div class="w-8 h-8 rounded-full {{ $paidBy ? 'bg-green-200' : 'bg-gray-200' }} flex items-center justify-center flex-shrink-0">
            <i class="ki-filled ki-verify {{ $paidBy ? 'text-green-600' : 'text-gray-400' }} text-xs"></i>
        </div>
        <div>
            <p class="text-2xs text-gray-400 uppercase font-medium">Paid By</p>
            @if($paidBy)
                <p class="text-sm font-medium text-gray-800">
                    {{ $paidBy->first_name ?? '' }} {{ $paidBy->last_name ?? '' }}
                </p>
                @if($paidAt)
                    <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($paidAt)->format('M d, Y H:i') }}</p>
                @endif
            @else
                <p class="text-sm text-gray-400">Not yet paid</p>
            @endif
        </div>
    </div>
</div>
