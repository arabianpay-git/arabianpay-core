<x-action-section>
    <x-slot name="title">
        {{ translate('Registered Passkeys') }}
    </x-slot>

    @php
        $passkeys = \Spatie\LaravelPasskeys\Models\Passkey::where('authenticatable_id', Auth::id())->latest()->get();
    @endphp

    <x-slot name="description">
        {{ translate('Manage and remove your registered passkeys (security keys, biometrics, etc.).') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600 leading-relaxed">
            {{ translate('Below is a list of passkeys you have registered. You may remove any passkey to revoke its access.') }}
        </div>

        @if ($passkeys->isEmpty())
            <div class="mt-5 text-sm text-gray-500">
                {{ translate('No passkeys registered yet.') }}
            </div>
        @else
            <div class="mt-5 space-y-6">
                @foreach ($passkeys as $passkey)
                    @php
                        $ua = $passkey->name ?? $passkey->data->publicKeyCredentialId;
                        $browser = 'Unknown Browser';
                        $platform = 'Unknown OS';

                        if (\Illuminate\Support\Str::contains($ua, 'Chrome')) {
                            preg_match('/Chrome\/([\d.]+)/', $ua, $m);
                            $browser = 'Chrome ' . ($m[1] ?? '');
                        } elseif (
                            \Illuminate\Support\Str::contains($ua, 'Safari') &&
                            !\Illuminate\Support\Str::contains($ua, 'Chrome')
                        ) {
                            $browser = 'Safari';
                        }

                        if (\Illuminate\Support\Str::contains($ua, 'Windows')) {
                            $platform = 'Windows';
                        } elseif (\Illuminate\Support\Str::contains($ua, 'Macintosh')) {
                            $platform = 'Mac OS';
                        } elseif (\Illuminate\Support\Str::contains($ua, 'Linux')) {
                            $platform = 'Linux';
                        }

                        $label = "$browser on $platform";
                    @endphp

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <i class="ki-duotone ki-key text-xl text-gray-600 shrink-0"></i>
                            <div class="min-w-0">
                                <div class="font-medium text-gray-800">
                                    {{ $label }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ translate('Created At') }} {{ $passkey->created_at->format('M j, Y H:i') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ translate('Last Used') }}
                                    {{ optional($passkey->last_used_at)->diffForHumans() ?? translate('Never used') }}
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('passkeys.destroy', $passkey->id) }}"
                            onsubmit="return confirm({{ json_encode(translate('Are you sure you want to delete this passkey?')) }});">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline btn-danger shrink-0">
                                {{ translate('Delete') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-slot>
</x-action-section>
