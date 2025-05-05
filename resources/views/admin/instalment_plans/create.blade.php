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
                            Add New Instalment Plan
                        </h3>
                    </div>
                    <form action="{{ route('instalment-plans.store') }}" method="POST">
                        @csrf
                        <div class="card-body grid gap-5">
                            <!-- Plan Name Input -->
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Plan Name
                                    </label>
                                    <input class="input @error('name') border-red-500 @enderror" name="name" type="text" value="{{ old('name') }}" required />
                                </div>
                                @error('name')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Description
                                    </label>
                                    <textarea class="textarea @error('description') border-red-500 @enderror" name="description" rows="4">{{ old('description') }}</textarea>
                                </div>
                                @error('description')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                            <div class="flex gap-5">
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Duration (Total Payment Duration)
                                        </label>
                                        <input class="input @error('duration') border-red-500 @enderror" name="duration" type="number" value="{{ old('duration') }}" required />
                                    </div>
                                    @error('duration')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Patch Days
                                        </label>
                                        <input class="input @error('patch_days') border-red-500 @enderror" name="patch_days" type="number" value="{{ old('patch_days') }}" required />
                                    </div>
                                    @error('patch_days')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                    
                            <div class="flex gap-5">
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Finance Limit
                                        </label>
                                        <input class="input @error('finance_limit') border-red-500 @enderror" name="finance_limit" type="number" value="{{ old('finance_limit') }}" required />
                                    </div>
                                    @error('finance_limit')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            
                                <div class="w-full">
                                    <div class="flex items-baseline flex-wrap gap-2.5">
                                        <label class="form-label flex items-center gap-1 max-w-56">
                                            Late Fee
                                        </label>
                                        <input class="input @error('late_fee') border-red-500 @enderror" name="late_fee" type="number" step="0.01" value="{{ old('late_fee') }}" />
                                    </div>
                                    @error('late_fee')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                    
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Transaction Fee
                                    </label>
                                    <input class="input @error('transaction_fee') border-red-500 @enderror" name="transaction_fee" type="number" step="0.01" value="{{ old('transaction_fee') }}" required />
                                </div>
                                @error('transaction_fee')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Installments
                                    </label>
                                    <input class="input @error('installments') border-red-500 @enderror" name="installments" type="number" value="{{ old('installments') }}" required />
                                </div>
                                @error('installments')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
                            <div class="w-full">
                                <div class="flex items-baseline flex-wrap gap-2.5">
                                    <label class="form-label flex items-center gap-1 max-w-56">
                                        Status
                                    </label>
                                    <select class="input @error('status') border-red-500 @enderror" name="status" required>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                @error('status')
                                    <span class="text-danger text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                    
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