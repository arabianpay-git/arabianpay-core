@extends('layouts.base') @section('content')

<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">
                            Support Ticket
                        </h3>
                    </div>

                    <form action="{{ route('support-ticket.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body grid gap-5">
                    
                            <!-- Subject -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Subject <span class="text-danger">*</span>
                                    </label>
                                    <input class="input @error('subject') border-red-500 @enderror" name="subject" type="text" value="{{ old('subject') }}" required />
                                </div>
                                @error('subject')
                                <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                            <!-- Details -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Details <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="textarea @error('details') border-red-500 @enderror" name="details" rows="4" required>{{ old('details') }}</textarea>
                                </div>
                                @error('details')
                                <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                            <!-- Files (Media Picker) -->
                            @include('media.multiple', [
                                'name' => 'files',
                                'label' => 'Files (Optional)',
                                'required' => false,
                                'value' => old('files'),
                                'multiple_select' => true,
                            ])
                    
                            <!-- Submit Button -->
                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">
                                    Create Ticket
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

