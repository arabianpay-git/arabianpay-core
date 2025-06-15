@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <div class="container-fixed" id="content_container"></div>

        <!-- Page Header -->
        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        Support Ticket #{{ $ticket->ticket_number }}
                    </h1>
                </div>

                @if ($ticket->user_id == Auth::id() || Auth::user()->user_type == 'admin')
                    <div class="">
                        <form action="{{ route('ticketUpdateStatus', $ticket->ticket_number) }}" method="POST"
                            class="w-full">
                            @csrf
                            @method('POST')

                            <label for="status" class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="select" style="width: 10rem;"
                                onchange="this.form.submit()">
                                <option value="active" {{ $ticket->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="solved" {{ $ticket->status == 'solved' ? 'selected' : '' }}>Solved</option>
                                <option value="draft" {{ $ticket->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="canceled" {{ $ticket->status == 'canceled' ? 'selected' : '' }}>Canceled
                                </option>
                            </select>
                        </form>
                    </div>
                @endif
            </div>
        </div>


        <!-- Ticket Details -->
        <div class="container-fixed">
            <div class="grid grid-cols-1 lg:gap-7.5">
                <div class="card grow" id="activity_2024">
                    <div class="card-header">
                        <h3 class="card-title">Activity</h3>
                    </div>
                    <div class="card-body">
                        <div class="flex flex-col">
                            @foreach ($activityData as $index => $activity)
                                <div class="flex items-start relative">
                                    @if (!$loop->last)
                                        <div
                                            class="w-9 start-0 top-9 absolute bottom-0 rtl:-translate-x-1/2 translate-x-1/2 border-s border-s-gray-300">
                                        </div>
                                    @endif
                                    <div
                                        class="flex items-center justify-center shrink-0 rounded-full bg-gray-100 border border-gray-300 size-9 text-gray-600">
                                        <i class="ki-filled ki-entrance-left text-base"></i>
                                    </div>

                                    <div class="ps-2.5 mb-7 text-md grow">
                                        <div class="flex flex-col">
                                            @if (!empty($activity['subject']))
                                                <div class="text-sm text-gray-800 font-semibold">
                                                    {{ $activity['subject'] }}
                                                    <small class="text-xs font-medium text-gray-500">
                                                        @if (\Carbon\Carbon::parse($activity['created_at'])->isToday())
                                                            (Today,
                                                            {{ \Carbon\Carbon::parse($activity['created_at'])->format('g:i A') }})
                                                        @else
                                                            ({{ \Carbon\Carbon::parse($activity['created_at'])->format('d M Y, g:i A') }})
                                                        @endif
                                                    </small>
                                                </div>
                                            @endif

                                            <span class="text-sm text-gray-600 mt-1">
                                                {{ $activity['message'] }}
                                            </span>

                                            @if (!empty($activity['files']))
                                                <div class="flex flex-wrap gap-2 mt-3">
                                                    @foreach ($activity['files'] as $file)
                                                        <a href="{{ $file }}" target="_blank"
                                                            class="text-xs text-primary underline">
                                                            View Attachment
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Reply Button -->
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-link reply-toggle-btn"
                                                    data-index="{{ $index }}">
                                                    {{ empty($activity['reply']) ? 'Reply' : 'Edit Reply' }}
                                                </button>
                                            </div>

                                            <!-- Inline Reply Form (hidden initially) -->
                                            <form action="{{ route('ticketReply', $activity['id']) }}" method="POST"
                                                class="reply-form mt-4 hidden" id="reply-form-{{ $index }}">
                                                @csrf
                                                <textarea name="reply" rows="2" class="textarea w-full mb-2" placeholder="Type your reply..." required>{{ $activity['reply'] ?? '' }}</textarea>
                                                <button class="btn btn-sm btn-primary" type="submit">Submit Reply</button>
                                            </form>

                                            <!-- Show Existing Reply -->
                                            @if (!empty($activity['reply']))
                                                <div class="text-sm text-gray-800 mt-2">
                                                    <strong>Your Reply:</strong> {{ $activity['reply'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.reply-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = btn.getAttribute('data-index');
                document.getElementById('reply-form-' + idx).classList.toggle('hidden');
            });
        });
    </script>
@endpush
