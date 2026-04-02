@props([
    'title' => null,
    'content' => null,
    'button' => null,
])

@php
    $thenMethod = $attributes->wire('then')->value();
    $confirmableId = md5($thenMethod);
@endphp

@if (app()->environment('local'))
    <span {{ $attributes->wire('then') }} x-data x-ref="span"
        x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
        x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);">
        {{ $slot }}
    </span>

    @once
        <x-dialog-modal wire:model.live="confirmingPassword">
            <x-slot name="title">
                {{ $title ?? translate('Confirm Password') }}
            </x-slot>

            <x-slot name="content">
                {{ $content ?? translate('For your security, please confirm your password to continue.') }}

                <div class="mt-4" x-data="{}"
                    x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
                    <x-input type="password" class="input w-full mt-1 block max-w-md" placeholder="{{ translate('Password') }}"
                        autocomplete="current-password" x-ref="confirmable_password" wire:model="confirmablePassword"
                        wire:keydown.enter="confirmPassword" />

                    <x-input-error for="confirmable_password" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button class="btn btn-sm btn-light" wire:click="stopConfirmingPassword" wire:loading.attr="disabled">
                    {{ translate('Cancel') }}
                </x-secondary-button>

                <x-button class="ms-3 btn btn-sm btn-primary" dusk="confirm-password-button" wire:click="confirmPassword"
                    wire:loading.attr="disabled">
                    {{ $button ?? translate('Confirm') }}
                </x-button>
            </x-slot>
        </x-dialog-modal>
    @endonce
@else
    {{-- Production: Microsoft SSO — run action directly without password confirmation --}}
    <span wire:click="{{ $thenMethod }}">
        {{ $slot }}
    </span>
@endif
