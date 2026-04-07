@props([
    'entityType' => '',
    'entityId' => null,
    'trails' => collect(),
    'limit' => 10,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <h3 class="card-title text-sm font-semibold">
            <i class="ki-filled ki-shield-tick text-gray-500 mr-1"></i>
            Audit Trail
        </h3>
    </div>
    <div class="card-body p-0">
        @if($trails->isEmpty())
            <div class="py-8 text-center text-gray-400 text-sm">
                <i class="ki-filled ki-shield-tick text-2xl mb-2"></i>
                <p>No audit records found</p>
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($trails->take($limit) as $trail)
                    <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-3">
                            {{-- Actor avatar --}}
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <i class="ki-filled ki-user text-gray-400 text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                {{-- Action summary --}}
                                <p class="text-sm text-gray-800">
                                    <span class="font-medium">{{ $trail->actor_email ?? 'System' }}</span>
                                    <span class="text-gray-500">{{ $trail->action_summary ?? $trail->event_type }}</span>
                                </p>
                                {{-- Metadata --}}
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                                    <span>
                                        <i class="ki-filled ki-time text-2xs"></i>
                                        {{ \Carbon\Carbon::parse($trail->created_at)->diffForHumans() }}
                                    </span>
                                    @if($trail->ip_address ?? null)
                                        <span>
                                            <i class="ki-filled ki-geolocation text-2xs"></i>
                                            {{ $trail->ip_address }}
                                        </span>
                                    @endif
                                    @if($trail->event_category ?? null)
                                        <span class="badge badge-sm badge-light">{{ $trail->event_category }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
