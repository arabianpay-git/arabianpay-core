<x-action-section>
    <x-slot name="title">
        {{ __('Registered Passkeys') }}
    </x-slot>

    @php
        $passkeys = \Spatie\LaravelPasskeys\Models\Passkey::where('authenticatable_id', Auth::id())->latest()->get();
    @endphp

    <x-slot name="description">
        {{ __('Manage and remove your registered passkeys (security keys, biometrics, etc.).') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600">
            {{ __('Below is a list of passkeys you have registered. You may remove any passkey to revoke its access.') }}
        </div>

        @if ($passkeys->isEmpty())
            <div class="mt-5 text-sm text-gray-500">
                {{ __('No passkeys registered yet.') }}
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

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="ki-duotone ki-key text-xl text-gray-600"></i>
                            <div>
                                <div class="font-medium text-gray-800">
                                    {{ $label }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ __('Created At') }} {{ $passkey->created_at->format('M j, Y H:i') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ __('Last Used') }}
                                    {{ optional($passkey->last_used_at)->diffForHumans() ?? __('Never used') }}
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('passkeys.destroy', $passkey->id) }}"
                            onsubmit="return confirm('{{ __('Are you sure you want to delete this passkey?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </x-slot>
</x-action-section>
