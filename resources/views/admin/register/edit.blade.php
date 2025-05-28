@extends('layouts.base')
@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed">
            <div class="flex grow gap-5 lg:gap-7.5">
                <div class="flex flex-col items-stretch grow gap-5 lg:gap-7.5">
                    <div class="card pb-2.5">
                        <div class="card-header" id="basic_settings">
                            <h3 class="card-title">
                                Edit Risk Register
                            </h3>
                        </div>

                        <form action="{{ route('risk-register.update', $risk->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body grid gap-5">

                                <div class="flex gap-4">
                                    <!-- Type -->
                                    <div class="w-full">
                                        <label for="type" class="form-label">Type</label>
                                        <input type="text" name="type" id="type" class="input w-full"
                                            value="{{ old('type', $risk->type) }}">
                                        @error('type')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Entity -->
                                    <div class="w-full">
                                        <label for="entity" class="form-label">Entity</label>
                                        <input type="text" name="entity" id="entity" class="input w-full"
                                            value="{{ old('entity', $risk->entity) }}">
                                        @error('entity')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <!-- Score -->
                                    <div class="w-full">
                                        <label for="score" class="form-label">Score <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="score" id="score" class="input w-full"
                                            step="0.01" value="{{ old('score', $risk->score) }}" required>
                                        @error('score')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <!-- Status -->
                                    <div class="w-full">
                                        <label for="status" class="form-label">Status <span
                                                class="text-danger">*</span></label>
                                        <select name="status" id="status" class="select w-full" required>
                                            <option value="low"
                                                {{ old('status', $risk->status) == 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium"
                                                {{ old('status', $risk->status) == 'medium' ? 'selected' : '' }}>Medium
                                            </option>
                                            <option value="high"
                                                {{ old('status', $risk->status) == 'high' ? 'selected' : '' }}>High</option>
                                            <option value="critical"
                                                {{ old('status', $risk->status) == 'critical' ? 'selected' : '' }}>Critical
                                            </option>
                                        </select>
                                        @error('status')
                                            <span class="text-danger text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="w-full">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="textarea w-full" rows="3">{{ old('description', $risk->description) }}</textarea>
                                    @error('description')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Action -->
                                <div class="w-full">
                                    <label for="action" class="form-label">Action</label>
                                    <input type="text" name="action" id="action" class="input w-full"
                                        value="{{ old('action', $risk->action) }}">
                                    @error('action')
                                        <span class="text-danger text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <div class="flex justify-end pt-2.5">
                                    <button type="submit" class="btn btn-primary">Update Risk Register</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
