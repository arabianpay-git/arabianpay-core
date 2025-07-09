<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', sans-serif;
    }

    .media-card {
        cursor: pointer;
        transition: transform .2s, box-shadow .2s, border-color .2s;
        /* border: 2px solid transparent; */
    }

    .media-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        transition: box-shadow 0.2s ease;
        cursor: pointer;
        background-color: #fff;
        position: relative;
    }

    .media-card:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .media-card.selected {
        border-color: #3b82f6;
    }

    .media-thumb {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }

    .media-info {
        padding: 8px 10px;
        font-size: 14px;
    }

    .media-info .name {
        font-weight: 500;
        color: #333;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .media-info .size {
        font-size: 12px;
        color: #888;
    }

    .media-grid {
        padding: 1rem;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
    }

    .media-card.selected {
        border: 2px solid #152230;
    }

    .media-card .overlay-check {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(59, 130, 246, 0.9);
        color: #fff;
        border-radius: 50%;
        width: 14px;
        height: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity .2s;
    }

    /* when card is selected, fade the check in */
    .media-card.selected .overlay-check {
        opacity: 1;
    }

    .overlay-check {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #007bff;
        color: white;
        border-radius: 50%;
        padding: 4px;
        font-size: 14px;
        display: none;
    }

    /* .media-card.selected .overlay-check {
        display: block;
    } */

    .preview-card {
        width: 160px;
        margin: 10px;
        position: relative;
    }

    .preview-thumb {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .remove-btn {
        position: absolute;
        top: 6px;
        right: 6px;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 14px;
        width: 24px;
        height: 24px;
        line-height: 20px;
        text-align: center;
        cursor: pointer;
    }

    .preview-info {
        padding: 8px;
        font-size: 12px;
        text-align: center;
    }

    .preview-info .name {
        font-weight: bold;
        color: #333;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    .preview-info .size {
        color: #888;
    }
</style>
@php
    $isMultipleSelect = isset($multiple_select) && $multiple_select;
    $mediaName = $name ?? 'media';
    $inputId = $inputId ?? 'media_picker_' . uniqid();
    $isRequired = $required ?? false;
    $infoText = $info ?? '';
    $label = $label ?? '';
    $inputValue = $value ?? ($isMultipleSelect ? [] : '');
    $inputValue = is_array($inputValue) ? $inputValue : [$inputValue];
    $pdfImage = asset('assets/media/images/default-pdf.png');
@endphp

<div class="w-full media-picker" data-input-id="{{ $inputId }}">
    <div class="flex items-baseline flex-wrap gap-2.5">
        <label class="form-label flex items-center gap-1 max-w-56">
            {{ $label }}
            @if ($isRequired)
                <span class="text-danger">*</span>
            @endif
        </label>

        <div class="flex items-center w-full max-w-md relative">
            <button type="button"
                class="absolute top-0 bottom-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-r"
                data-modal-toggle="#{{ $inputId }}_modal">
                <i class="ki-filled ki-folder text-xl"></i>
            </button>
            <input type="text" id="{{ $inputId }}_display" class="input w-full" readonly
                placeholder="Click to select media"
                value="{{ $isMultipleSelect ? implode(', ', array_map('basename', $inputValue)) : basename($inputValue[0]) }}"
                data-modal-toggle="#{{ $inputId }}_modal" style="padding-inline-start: 2.75rem;">
            @foreach ($inputValue as $val)
                <input type="hidden" name="{{ $mediaName }}{{ $isMultipleSelect ? '[]' : '' }}"
                    value="{{ $val }}">
            @endforeach
        </div>
        @if ($infoText)
            <span style="font-size: 10px; margin-top: -.625rem; line-height: 14px;">{{ $infoText }}</span>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap gap-3" id="{{ $inputId }}_previewCard">
        @foreach ($inputValue as $val)
            @if ($val)
                <div class="media-card relative" style="width: 160px;" data-url="{{ $val }}">
                    @php
                        $ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
                        $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                        $isPdf = $ext === 'pdf';
                    @endphp

                    @if ($isPdf)
                        <img class="media-thumb" src="{{ $pdfImage }}" alt="PDF Preview">
                    @elseif ($isVideo)
                        <video class="media-thumb" src="{{ $val }}" controls muted preload="metadata"
                            style="max-height:160px;"></video>
                    @else
                        <img class="media-thumb" src="{{ $val }}" alt="Preview">
                    @endif

                    <div class="media-info">
                        <div class="name">{{ basename($val) }}</div>
                    </div>
                    <button
                        class="remove-btn absolute top-1 right-1 bg-white text-black rounded-full text-xs w-5 h-5 leading-5 text-center">&times;</button>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Upload Progress -->
    <div class="w-full max-w-md mt-2 d-none" id="{{ $inputId }}_uploadProgressContainer">
        <div class="bg-gray-200 rounded h-2 overflow-hidden">
            <div id="{{ $inputId }}_uploadProgressBar"
                class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"></div>
        </div>
        <small id="{{ $inputId }}_uploadProgressText" class="text-sm text-gray-600">Uploading...</small>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="{{ $inputId }}_modal" data-modal="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Select Media</h5>
                <button type="button" class="btn btn-primary"
                    onclick="$('#{{ $inputId }}_fileInput').click()">Upload File</button>
                <input type="file" id="{{ $inputId }}_fileInput" accept="image/*,video/*,.pdf" multiple hidden>
                <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0"
                    data-modal-dismiss="true">
                    <i class="ki-filled ki-cross"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Upload Progress Bar -->
                <div id="{{ $inputId }}_uploadProgressContainer" class="w-full max-w-md mb-4 hidden"
                    style="background: #eee; padding: 8px; border-radius: 4px;">
                    <div class="bg-gray-200 rounded h-2 overflow-hidden" style="background: #ccc;">
                        <div id="{{ $inputId }}_uploadProgressBar"
                            class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"
                            style="background: #007bff; height: 8px; width: 0;"></div>
                    </div>
                    <small id="{{ $inputId }}_uploadProgressText"
                        class="text-sm text-gray-600">Uploading...</small>
                </div>

                <!-- Media Grid -->
                <div class="media-grid" id="{{ $inputId }}_grid">
                    @php
                        $media = App\Models\Media::where('user_id', Auth::id())->latest()->take(18)->get();
                    @endphp
                    @foreach ($media as $item)
                        @php
                            $ext = strtolower(pathinfo($item->file_name, PATHINFO_EXTENSION));
                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg']);
                            $isPdf = $ext === 'pdf';
                            $url = asset('storage/media/' . $item->file_name);
                        @endphp
                        <div class="media-card" data-id="{{ $item->id }}" data-url="{{ $url }}"
                            data-name="{{ $item->name }}" data-size="{{ $item->size }}"
                            data-mime="{{ $item->mime_type }}">
                            @if ($isPdf)
                                <img src="{{ $pdfImage }}" class="media-thumb" alt="PDF">
                            @elseif ($isVideo)
                                <video src="{{ $url }}" class="media-thumb" controls muted preload="metadata"
                                    style="max-height:150px;"></video>
                            @else
                                <img src="{{ $url }}" class="media-thumb" alt="media">
                            @endif
                            <div class="media-info">
                                <div class="name">{{ $item->name }}</div>
                                <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                            </div>
                            <div class="overlay-check"><i class="fas fa-check"></i></div>
                        </div>
                    @endforeach
                </div>

                <div id="{{ $inputId }}_loadingSpinner" class="text-center hidden mt-3">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>

            <div class="modal-footer justify-end border-0"
                style="margin-right: 3rem; padding-bottom: 1rem; display: flex;">
                <button type="button" class="btn btn-primary"
                    onclick="confirmMediaSelection('{{ $inputId }}', '{{ $mediaName }}', {{ $isMultipleSelect ? 'true' : 'false' }})"
                    data-modal-dismiss="true">Select</button>
                <button type="button" class="btn btn-light" data-modal-dismiss="true">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(function() {
            const prefix = @json($inputId);

            $(`#${prefix}_fileInput`).on('change', function(e) {
                const files = e.target.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(file => formData.append('files[]', file));
                formData.append('_token', '{{ csrf_token() }}');

                // Show progress
                $(`#${prefix}_uploadProgressContainer`).removeClass('hidden');
                $(`#${prefix}_uploadProgressBar`).css('width', '0%');
                $(`#${prefix}_uploadProgressText`).text('Uploading...');

                $.ajax({
                    url: "{{ route('media.upload') }}",
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    xhr: function() {
                        const xhr = new XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                $(`#${prefix}_uploadProgressBar`).css('width', percent +
                                    '%');
                                $(`#${prefix}_uploadProgressText`).text(
                                    `Uploading... ${percent}%`);
                            }
                        });
                        return xhr;
                    },
                    success: function(res) {
                        setTimeout(() => {
                            $(`#${prefix}_uploadProgressContainer`).addClass('hidden');
                        }, 500);

                        if (res.success && res.media.length) {
                            res.media.forEach(media => {
                                const isVideo = media.mime_type.startsWith('video');
                                const isPdf = media.mime_type === 'application/pdf';
                                const url = `/storage/media/${media.file_name}`;
                                const thumb = isPdf ?
                                    `<img src="{{ $pdfImage }}" class="media-thumb" alt="${media.name}">` :
                                    isVideo ?
                                    `<video src="${url}" class="media-thumb" controls muted preload="metadata" style="max-height:150px;"></video>` :
                                    `<img src="${url}" class="media-thumb" alt="${media.name}">`;

                                $(`#${prefix}_grid`).prepend(`
                                <div class="media-card" data-id="${media.id}" data-url="${url}" data-name="${media.name}" data-size="${media.size}" data-mime="${media.mime_type}">
                                    ${thumb}
                                    <div class="media-info">
                                        <div class="name">${media.name}</div>
                                        <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                                    </div>
                                    <div class="overlay-check"><i class="fas fa-check"></i></div>
                                </div>
                            `);
                            });

                            Swal.fire('Uploaded!', res.message || 'File(s) uploaded.',
                                'success');
                        }
                    },
                    error: function() {
                        $(`#${prefix}_uploadProgressContainer`).addClass('hidden');
                        Swal.fire('Error', 'Upload failed.', 'error');
                    }
                });
            });
        });
    </script>
    <script>
        function confirmMediaSelection(prefix, mediaName, isMultiple) {
            const selected = $(`#${prefix}_grid .media-card.selected`);

            if (!selected.length) {
                Swal.fire('No Selection', 'Please select at least one media item.', 'info');
                return;
            }

            const values = [];
            const previews = [];

            selected.each(function() {
                const url = $(this).data('url');
                const name = $(this).data('name');
                const mime = $(this).data('mime');
                const size = $(this).data('size');
                const isVideo = mime.startsWith('video');
                const isPdf = mime === 'application/pdf';

                values.push(url);

                let preview = '';
                if (isPdf) {
                    preview = `<img class="media-thumb" src="{{ $pdfImage }}" alt="PDF Preview">`;
                } else if (isVideo) {
                    preview =
                        `<video class="media-thumb" src="${url}" controls muted preload="metadata" style="max-height:160px;"></video>`;
                } else {
                    preview = `<img class="media-thumb" src="${url}" alt="Preview">`;
                }

                previews.push(`
                <div class="media-card relative" style="width: 160px;" data-url="${url}">
                    ${preview}
                    <div class="media-info"><div class="name">${name}</div></div>
                    <button class="remove-btn absolute top-1 right-1 bg-white text-black rounded-full text-xs w-5 h-5 leading-5 text-center">&times;</button>
                </div>
            `);
            });

            // Set hidden inputs and preview
            const previewContainer = $(`#${prefix}_previewCard`);
            previewContainer.empty();

            values.forEach(url => {
                const input = `<input type="hidden" name="${mediaName}${isMultiple ? '[]' : ''}" value="${url}">`;
                previewContainer.closest('.media-picker').append(input);
            });

            previewContainer.append(previews.join(''));
            $(`#${prefix}_display`).val(values.map(v => v.split('/').pop()).join(', '));
        }

        // Toggle selection
        $(document).on('click', '.media-card', function() {
            const container = $(this).closest('.media-grid');
            const isMultiple = container.closest('.media-picker').data('multiple');

            if (!isMultiple) {
                container.find('.media-card').removeClass('selected');
            }

            $(this).toggleClass('selected');
        });

        // Remove preview
        $(document).on('click', '.remove-btn', function(e) {
            e.stopPropagation();
            const card = $(this).closest('.media-card');
            const url = card.data('url');

            card.remove();
            $(`input[value="${url}"]`).remove();

            const prefix = card.closest('.media-picker').data('input-id');
            const names = $(`#${prefix}_previewCard .media-card`).map(function() {
                return $(this).data('url').split('/').pop();
            }).get();

            $(`#${prefix}_display`).val(names.join(', '));
        });
    </script>
@endpush
