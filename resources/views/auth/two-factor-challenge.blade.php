<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div x-data="{ recovery: false }">
            <div class="mb-4 text-sm text-gray-600" x-show="! recovery">
                {{ translate('two_factor_challenge_intro') }}
            </div>

            <div class="mb-4 text-sm text-gray-600" x-cloak x-show="recovery">
                {{ translate('two_factor_challenge_recovery_intro') }}
            </div>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="mt-4" x-show="! recovery">
                    <x-label for="code" value="{{ translate('Code') }}" />
                    <x-input id="code" class="block mt-1 w-full" type="text" inputmode="numeric" name="code"
                        autofocus x-ref="code" autocomplete="one-time-code" />
                </div>

                <div class="mt-4" x-cloak x-show="recovery">
                    <x-label for="recovery_code" value="{{ translate('two_factor_recovery_code_label') }}" />
                    <x-input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code"
                        x-ref="recovery_code" autocomplete="one-time-code" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                        x-show="! recovery"
                        x-on:click="
                                        recovery = true;
                                        $nextTick(() => { $refs.recovery_code.focus() })
                                    ">
                        {{ translate('two_factor_use_recovery_code') }}
                    </button>

                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                        x-cloak x-show="recovery"
                        x-on:click="
                                        recovery = false;
                                        $nextTick(() => { $refs.code.focus() })
                                    ">
                        {{ translate('two_factor_use_authentication_code') }}
                    </button>

                    <x-button class="ms-4">
                        {{ translate('two_factor_challenge_log_in') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-authentication-card>
</x-guest-layout>
