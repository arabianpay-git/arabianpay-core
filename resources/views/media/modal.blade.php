<div class="modal" data-modal="true" id="{{ $inputId }}_modal">
    <div class="modal-content max-w-[70%] top-[5%]">

        <!-- Modal Header -->
        <div class="modal-header py-4 px-5 flex items-center justify-between gap-3 border-b border-gray-200">
            <h5 class="modal-title text-lg font-semibold text-gray-800">
                <i class="fas fa-photo-video mr-2 text-primary"></i>
                Select Media
            </h5>

            <div class="flex items-center gap-4">

                <!-- Upload Button -->
                <button type="button" class="btn btn-primary btn-outline upload-btn flex items-center gap-2"
                    onclick="document.getElementById('{{ $inputId }}_fileInput').click()">
                    <i class="ki-filled ki-file-up"></i> Upload File
                </button>

                <!-- Hidden File Input -->
                <input type="file" id="{{ $inputId }}_fileInput" accept="image/*,video/*,.pdf,.svg" multiple
                    hidden>

                <!-- Upload Progress Bar -->
                <div id="{{ $inputId }}_uploadProgressContainer" class="w-full max-w-md hidden">
                    <div class="bg-gray-200 rounded h-2 overflow-hidden">
                        <div id="{{ $inputId }}_uploadProgressBar"
                            class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"></div>
                    </div>
                    <small id="{{ $inputId }}_uploadProgressText"
                        class="text-sm text-gray-600">Uploading...</small>
                </div>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-0 pb-5 max-h-[60vh] overflow-auto bg-white">
            <div class="media-grid p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"
                id="{{ $inputId }}_grid">

                @php
                    $pdfImage = asset('assets/media/images/default-pdf.png');
                    $media = App\Models\Media::where('user_id', Auth::user()->id)
                        ->latest()
                        ->take(18)
                        ->get();
                @endphp

                @foreach ($media as $item)
                    @php
                        $isVideo = Str::startsWith($item->mime_type, 'video');
                        $isPdf = $item->mime_type === 'application/pdf';
                        $mediaUrl = getMediaUrl($item->file_name, asset('assets/media/images/default-image.png'));
                        $supplierName =
                            $item->user && $item->user->business_name ? $item->user->business_name : 'Unknown Supplier';
                    @endphp

                    <div class="media-card position-relative border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer"
                        data-id="{{ $item->id }}" data-url="{{ $mediaUrl }}" data-name="{{ $item->name }}"
                        data-size="{{ $item->size }}" data-mime="{{ $item->mime_type }}"
                        data-file-name="{{ $item->file_name }}">
                        @if ($isVideo)
                            <video src="{{ $mediaUrl }}" class="media-thumb w-full object-cover max-h-[150px]"
                                controls muted preload="metadata"
                                aria-label="Video preview: {{ $item->name }}"></video>
                        @elseif ($isPdf)
                            <img src="{{ $pdfImage }}" class="media-thumb w-full object-contain max-h-[150px]"
                                alt="PDF icon" loading="lazy">
                        @else
                            <img src="{{ $mediaUrl }}" class="media-thumb w-full object-cover max-h-[150px]"
                                alt="{{ $item->name }}" loading="lazy">
                        @endif

                        <div class="media-info p-2 bg-white border-t">
                            <div class="name text-sm font-medium text-gray-700 truncate" title="{{ $item->name }}">
                                {{ $item->name }}</div>
                            <div class="size text-xs text-gray-500">{{ number_format($item->size / 1024, 1) }} KB</div>
                            <div class="supplier-name text-xs text-gray-500 mt-1 truncate" title="{{ $supplierName }}">
                                <strong>User:</strong> {{ $supplierName }}
                            </div>
                        </div>

                        <div class="overlay-check absolute top-2 right-2 bg-primary text-white rounded-full p-1 hidden">
                            <i class="ki-filled ki-check-circle text-sm"></i>
                        </div>
                    </div>
                @endforeach

            </div>

            <!-- Loading Spinner -->
            <div id="{{ $inputId }}_loadingSpinner" class="text-center hidden mt-4">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>


        <div class="modal-footer px-5 pb-4 flex justify-end gap-3">
            <button type="button" class="btn btn-secondary" id="{{ $inputId }}_moda"
                data-modal-dismiss="true">Close</button>
        </div>
    </div>
</div>
