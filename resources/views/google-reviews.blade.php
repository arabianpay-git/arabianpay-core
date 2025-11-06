@extends('layouts.auth')

@section('content')
    <div class="container mx-auto p-4">
        <h1 class="text-xl font-bold mb-4">Google Business Reviews</h1>

        @if (session('error'))
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('google.reviews.fetch') }}" method="POST" class="mb-6">
            @csrf
            <input type="text" name="business_name" placeholder="Enter Business Name" value="{{ old('business_name') }}"
                class="input" required>
            <button type="submit" class="btn btn-sm btn-primary mt-2">Search</button>
        </form>

        @if (isset($reviews))
            <h2 class="text-lg font-semibold mb-3">Reviews for: {{ $business }}</h2>

            @if (isset($overallRating))
                <p class="mb-3 text-gray-700">
                    <strong>Overall Rating:</strong> {{ $overallRating }} ★ ({{ $totalReviews }} total reviews)
                </p>
            @endif


            @forelse($reviews as $review)
                <div class="border p-4 mb-3 rounded shadow">
                    <p><strong>{{ $review['author_name'] }}</strong> ({{ $review['rating'] }}★)</p>
                    <p class="text-sm text-gray-600">{{ $review['relative_time_description'] }}</p>
                    <p class="mt-2">{{ $review['text'] }}</p>
                </div>
            @empty
                <p>No reviews found.</p>
            @endforelse
        @endif
    </div>
@endsection
