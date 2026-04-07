@props([
    'steps' => [],        // array of ['label' => '', 'icon' => '', 'status' => '', 'actor' => null, 'date' => null]
    'currentIndex' => 0,
    'cancelled' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 flex-wrap']) }}>
    @foreach($steps as $index => $step)
        @php
            $isCompleted = $index < $currentIndex;
            $isCurrent = $index === $currentIndex;
            $isFuture = $index > $currentIndex;

            $badgeClass = $isCompleted ? 'badge-success'
                : ($isCurrent ? 'badge-primary'
                : 'badge-light text-gray-400');
        @endphp

        <div class="flex items-center gap-2" title="{{ $step['actor'] ?? '' }} {{ $step['date'] ?? '' }}">
            <div class="flex badge items-center gap-1 px-2.5 py-1.5 rounded {{ $badgeClass }}">
                <i class="ki-filled {{ $step['icon'] ?? 'ki-check' }} text-xs"></i>
                <span class="text-xs font-medium">{{ $step['label'] }}</span>
                @if($isCompleted && !empty($step['date']))
                    <span class="text-2xs opacity-70 hidden sm:inline">{{ \Carbon\Carbon::parse($step['date'])->format('M d') }}</span>
                @endif
            </div>

            @if($index < count($steps) - 1)
                <i class="ki-filled ki-right text-xs {{ $isCompleted ? 'text-green-400' : 'text-gray-300' }}"></i>
            @endif
        </div>
    @endforeach

    @if($cancelled)
        <div class="flex items-center gap-1 px-2.5 py-1.5 rounded bg-red-100 text-red-700">
            <i class="ki-filled ki-cross-circle text-xs"></i>
            <span class="text-xs font-medium">Cancelled</span>
        </div>
    @endif
</div>
