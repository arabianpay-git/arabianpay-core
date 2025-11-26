@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                {{ translate('Add New City') }}
                            </h3>
                        </div>

                        <form action="{{ route('cities.store') }}" method="POST">
                            @csrf
                            <div class="card-body grid gap-5">
                                <!-- City Name Field -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('City Name') }}
                                        </label>
                                        <input class="input @error('name') border-red-500 @enderror" name="name"
                                            type="text" value="{{ old('name') }}" required />
                                    </div>
                                    @error('name')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('Risk') }}
                                        </label>
                                        <input class="input @error('risk') border-red-500 @enderror" name="risk"
                                            type="text" value="{{ old('risk') }}" required />
                                    </div>
                                    @error('risk')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                    <small class="text-gray">Hint: 1–3; 1 = low, 3 = high</small>
                                </div>

                                <!-- State Select Field -->
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            {{ translate('State') }}
                                        </label>
                                        <select class="input @error('state_id') border-red-500 @enderror" name="state_id"
                                            required>
                                            <option value="">{{ translate('Select State') }}</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}"
                                                    {{ old('state_id') == $state->id ? 'selected' : '' }}>
                                                    {{ $state->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('state_id')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button class="btn btn-primary">
                                        {{ translate('Save Changes') }}
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
