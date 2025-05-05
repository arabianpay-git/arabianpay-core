@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <div class="container-fixed">
        <div class="flex grow gap-5 lg:gap-7.5">
            <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                <div class="card pb-2.5">
                    <div class="card-header" id="basic_settings">
                        <h3 class="card-title">Edit Instalment Plan</h3>
                    </div>

                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex gap-4" id="langTabs">
                            <button class="tab-btn active" data-tab="en">English</button>
                            <button class="tab-btn" data-tab="ar">Arabic</button>
                        </nav>
                    </div>

                    <form action="{{ route('instalment-plans.update', $instalmentPlan->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="p-5">
                            <!-- English Tab -->
                            <div class="tab-content" id="tab-en">
                                <div class="grid gap-5">
                                    <!-- Name (EN) -->
                                    <div class="w-full">
                                        <label class="form-label">Installment Plan</label>
                                        <input class="input @error('name.en') border-red-500 @enderror"
                                               name="name[en]"
                                               type="text"
                                               value="{{ old('name.en', $instalmentPlan->name) }}"
                                               required />
                                        @error('name.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                Description
                                            </label>
                                            <textarea class="textarea @error('description.en') border-red-500 @enderror" name="description[en]" rows="4">{{ old('description.en', $instalmentPlan->description) }}</textarea>
                                        </div>
                                        @error('description.en')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
                                    <div class="flex gap-5">
                                        <div class="w-full">
                                            <div class="flex items-baseline flex-wrap gap-2.5">
                                                <label class="form-label flex items-center gap-1 max-w-56">
                                                    Duration (Total Payment Duration)
                                                </label>
                                                <input class="input @error('duration') border-red-500 @enderror" name="duration" type="number" value="{{ old('duration', $instalmentPlan->duration) }}" required />
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
                                                <input class="input @error('patch_days') border-red-500 @enderror" name="patch_days" type="number" value="{{ old('patch_days', $instalmentPlan->patch_days) }}" required />
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
                                                <input class="input @error('finance_limit') border-red-500 @enderror" name="finance_limit" type="number" value="{{ old('finance_limit', $instalmentPlan->finance_limit) }}" required />
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
                                                <input class="input @error('late_fee') border-red-500 @enderror" name="late_fee" type="number" step="0.01" value="{{ old('late_fee', $instalmentPlan->late_fee) }}" />
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
                                            <input class="input @error('transaction_fee') border-red-500 @enderror" name="transaction_fee" type="number" step="0.01" value="{{ old('transaction_fee', $instalmentPlan->transaction_fee) }}" required />
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
                                            <input class="input @error('installments') border-red-500 @enderror" name="installments" type="number" value="{{ old('installments', $instalmentPlan->installments) }}" required />
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
                                                <option value="active" {{ old('status', $instalmentPlan->status) == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $instalmentPlan->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                        @error('status')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    
                                    

                                </div>
                            </div>

                            <!-- Arabic Tab -->
                            <div class="tab-content hidden" id="tab-ar">
                                <div class="grid gap-5">
                                    <!-- Name (AR) -->
                                    <div class="w-full">
                                        <label class="form-label">Installment Plan Name (Arabic)</label>
                                        <input class="input @error('name.ar') border-red-500 @enderror"
                                               name="name[ar]"
                                               type="text"
                                               value="{{ old('name.ar', $instalmentPlan->translations->where('locale', 'ar')->first()->name ?? '') }}" />
                                        @error('name.ar')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="w-full">
                                        <div class="flex items-baseline flex-wrap gap-2.5">
                                            <label class="form-label flex items-center gap-1 max-w-56">
                                                Description
                                            </label>
                                            <textarea class="textarea @error('description.ar') border-red-500 @enderror" name="description[ar]" rows="4">{{ old('description.ar', $instalmentPlan->translations->where('locale', 'ar')->first()->description ?? '') }}</textarea>
                                        </div>
                                        @error('description.ar')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-2.5">
                                <button class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
