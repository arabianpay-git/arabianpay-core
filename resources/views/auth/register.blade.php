<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-label for="first_name" value="{{ translate('First Name') }}" />
                <x-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')"
                    required autofocus />
            </div>

            <div class="mt-4">
                <x-label for="last_name" value="{{ translate('Last Name') }}" />
                <x-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')"
                    required />
            </div>

            <div class="mt-4">
                <x-label for="email" value="{{ translate('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                    required autocomplete="email" />
            </div>

            <div class="mt-4">
                <x-label for="phone_number" value="{{ translate('Phone Number') }}" />
                <x-input id="phone_number" class="block mt-1 w-full" type="text" name="phone_number"
                    :value="old('phone_number')" required />
            </div>

            <div class="mt-4">
                <x-label for="business_name" value="{{ translate('Business Name') }}" />
                <x-input id="business_name" class="block mt-1 w-full" type="text" name="business_name"
                    :value="old('business_name')" required />
            </div>

            <div class="mt-4">
                <x-label for="iqama" value="{{ translate('Iqama (optional)') }}" />
                <x-input id="iqama" class="block mt-1 w-full" type="text" name="iqama" :value="old('iqama')" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ translate('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required
                    autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ translate('Confirm Password') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password"
                    name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="country_id" value="{{ translate('Country') }}" />
                <select id="country_id" class="block mt-1 w-full" name="country_id" required>
                    <option value="">Select Country</option>
                    @foreach (App\Models\Country::all() as $country)
                        <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4">
                <x-label for="state_id" value="{{ translate('State') }}" />
                <select id="state_id" class="block mt-1 w-full" name="state_id" required>
                    <option value="">Select State</option>
                </select>
            </div>

            <div class="mt-4">
                <x-label for="city_id" value="{{ translate('City') }}" />
                <select id="city_id" class="block mt-1 w-full" name="city_id" required>
                    <option value="">Select City</option>
                </select>
            </div>


            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mt-4">
                    <x-label for="terms">
                        <div class="flex items-center">
                            <x-checkbox name="terms" id="terms" required />
                            <div class="ms-2">
                                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                    'terms_of_service' =>
                                        '<a target="_blank" href="' .
                                        route('terms.show') .
                                        '" class="underline text-sm text-gray-600 hover:text-gray-900">' .
                                        __('Terms of Service') .
                                        '</a>',
                                    'privacy_policy' =>
                                        '<a target="_blank" href="' .
                                        route('policy.show') .
                                        '" class="underline text-sm text-gray-600 hover:text-gray-900">' .
                                        __('Privacy Policy') .
                                        '</a>',
                                ]) !!}
                            </div>
                        </div>
                    </x-label>
                </div>
            @endif

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                    {{ translate('Already registered?') }}
                </a>

                <x-button class="ms-4">
                    {{ translate('Register') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const countrySelect = document.getElementById('country_id');
        const stateSelect = document.getElementById('state_id');
        const citySelect = document.getElementById('city_id');

        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            stateSelect.innerHTML = '<option value="">Loading...</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';

            fetch(`/get-states/${countryId}`)
                .then(response => response.json())
                .then(data => {
                    stateSelect.innerHTML = '<option value="">Select State</option>';
                    data.forEach(state => {
                        stateSelect.innerHTML +=
                            `<option value="${state.id}">${state.name}</option>`;
                    });
                });
        });

        stateSelect.addEventListener('change', function() {
            const stateId = this.value;
            citySelect.innerHTML = '<option value="">Loading...</option>';

            fetch(`/get-cities/${stateId}`)
                .then(response => response.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    data.forEach(city => {
                        citySelect.innerHTML +=
                            `<option value="${city.id}">${city.name}</option>`;
                    });
                });
        });
    });
</script>
