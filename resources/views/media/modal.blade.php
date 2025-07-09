<div class="modal" data-modal="true" id="{{ $inputId }}_modal">
    <div class="modal-content max-w-[70%] top-[5%]">
        <div class="modal-header py-4 px-5 flex items-center justify-between gap-3">
            <h5 class="modal-title">Select Media</h5>

            <!-- Upload Button -->
            <button type="button" class="btn btn-primary upload-btn"
                onclick="$('#{{ $inputId }}_fileInput').click()">
                Upload File
            </button>

            <!-- Hidden File Input -->
            <input type="file" id="{{ $inputId }}_fileInput" accept="image/*,video/*,.pdf,.svg" multiple hidden>

            <!-- Upload Progress Bar -->
            <div id="{{ $inputId }}_uploadProgressContainer" class="w-full max-w-md ml-4 hidden">
                <div class="bg-gray-200 rounded h-2 overflow-hidden">
                    <div id="{{ $inputId }}_uploadProgressBar"
                        class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"></div>
                </div>
                <small id="{{ $inputId }}_uploadProgressText" class="text-sm text-gray-600">Uploading...</small>
            </div>

            <!-- Close Modal Button -->
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>

        <div class="modal-body p-0 pb-5 max-h-[60vh] overflow-auto">
            <div class="media-grid" id="{{ $inputId }}_grid">
                @php
                    $media = App\Models\Media::where('user_id', Auth::user()->id)
                        ->latest()
                        ->take(18)
                        ->get();
                @endphp

                @foreach ($media as $item)
                    @php
                        $isVideo = Str::startsWith($item->mime_type, 'video');
                        $isPdf = $item->mime_type === 'application/pdf';
                        $mediaUrl = asset('storage/media/' . $item->file_name);
                        $pdfImage = asset('assets/media/images/default-pdf.png');
                    @endphp

                    <div class="media-card position-relative" data-id="{{ $item->id }}"
                        data-url="{{ $mediaUrl }}" data-name="{{ $item->name }}"
                        data-size="{{ number_format($item->size / 1024, 1) }}" data-mime="{{ $item->mime_type }}">

                        @if ($isVideo)
                            <video src="{{ $mediaUrl }}" class="media-thumb" controls muted preload="metadata"
                                style="max-height: 150px; width: auto;"></video>
                        @elseif ($isPdf)
                            <img src="{{ $pdfImage }}" class="media-thumb" alt="PDF">
                        @else
                            <img src="{{ $mediaUrl }}" class="media-thumb" alt="media">
                        @endif

                        <div class="media-info">
                            <div class="name">{{ $item->name }}</div>
                            <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                        </div>
                        <div class="overlay-check"><i class="fas fa-check"></i></div>
                    </div>
                @endforeach
            </div>

            <div id="{{ $inputId }}_loadingSpinner" class="text-center d-none mt-4">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>
    </div>
</div>
