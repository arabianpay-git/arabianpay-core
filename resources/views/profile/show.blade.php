@extends('layouts.base')

@section('content')
    <main class="grow content pt-5 bg-white" id="content" role="content">
        <div class="container-fixed">
            <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
                <div>
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        {{ translate('Profile') }}
                    </h1>
                    <p class="text-sm text-gray-600 mt-2">
                        {{ translate('Manage your account security, password, and two-factor authentication.') }}
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-light">
                    <i class="ki-filled ki-arrow-left rtl:rotate-180"></i>
                    {{ translate('Back to Dashboard') }}
                </a>
            </div>
        </div>

        <div class="container-fixed pb-10">
            <div class="flex flex-col gap-7.5">
                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                    @livewire('profile.update-profile-information-form')

                    <x-section-border />
                @endif

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                    @livewire('profile.update-password-form')

                    <x-section-border />
                @endif

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    @livewire('profile.two-factor-authentication-form')

                    <x-section-border />
                @endif

                @livewire('profile.logout-other-browser-sessions-form')

                <x-section-border />

                @include('profile.passkeys')

                @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                    <x-section-border />

                    @livewire('profile.delete-user-form')
                @endif
            </div>
        </div>
    </main>
@endsection
