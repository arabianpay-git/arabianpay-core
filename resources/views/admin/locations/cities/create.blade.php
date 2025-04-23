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
                            Add New City
                        </h3>
                    </div>

                    <form action="{{ route('cities.store') }}" method="POST">
                        @csrf
                        <div class="card-body grid gap-5">
                            <!-- State Name Field -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        City Name
                                    </label>
                                    <input class="input @error('name') border-red-500 @enderror" 
                                           name="name" 
                                           type="text" 
                                           value="{{ old('name') }}" 
                                           required />
                                </div>
                                @error('name')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- state Select Field -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        State
                                    </label>
                                    <select class="input @error('state_id') border-red-500 @enderror" 
                                            name="state_id" 
                                            required>
                                        <option value="">Select State</option>
                                        @foreach ($states as $state)
                                            <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>
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
                                    Save Changes
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
