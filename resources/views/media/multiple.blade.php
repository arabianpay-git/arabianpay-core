<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', sans-serif;
    }

    .media-card {
        cursor: pointer;
        transition: transform .2s, box-shadow .2s, border-color .2s;
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
        border-width: 2px;
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

    .media-card.selected .overlay-check {
        opacity: 1;
    }

    .preview-card {
        width: 160px;
        margin: 10px 0;
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
        /* text-align: center; */
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

<div class="w-full media-picker" data-input-id="{{ $inputId }}"
    data-multiple="{{ $isMultipleSelect ? 'true' : 'false' }}">
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
                {{-- Media items will be dynamically injected here --}}
            </div>

            <!-- Loading Spinner -->
            <div id="{{ $inputId }}_loadingSpinner" class="text-center hidden mt-4">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer px-5 pb-4 flex justify-end gap-3">
            <button type="button" class="btn btn-primary"
                onclick="confirmMediaSelection('{{ $inputId }}', '{{ $mediaName }}', {{ $isMultipleSelect ? 'true' : 'false' }})"
                data-modal-dismiss="true">Select</button>

            <button type="button" class="btn btn-secondary" data-modal-dismiss="true">Close</button>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        $(function() {
            const prefix = @json($inputId);
            const multipleSelect = @json($isMultipleSelect);
            const pdfImage = @json($pdfImage);
            const mediaRefreshUrl = "{{ route('media.refresh') }}";

            // You need to define mediaName for input hidden fields
            // Make sure to replace 'media' below with the actual input name if different
            const mediaName = 'media';

            // Refresh media grid function
            function refreshMediaGrid() {
                const $grid = $(`#${prefix}_grid`);
                const $spinner = $(`#${prefix}_loadingSpinner`);
                $spinner.removeClass('hidden');

                $.ajax({
                    url: mediaRefreshUrl,
                    method: "GET",
                    data: {
                        limit: 18
                    },
                    success: function(res) {
                        $spinner.addClass('hidden');
                        if (res.html) {
                            $grid.html(res.html);

                            // Reset any previously selected cards on reload
                            $grid.find('.media-card').removeClass('selected');
                        } else {
                            $grid.html('<p class="text-center text-gray-500">No media found.</p>');
                        }
                    },
                    error: function() {
                        $spinner.addClass('hidden');
                        $grid.html('<p class="text-center text-danger">Failed to load media.</p>');
                    }
                });
            }

            // Initial load
            refreshMediaGrid();

            // Refresh media grid on custom event (useful for uploads)
            window.addEventListener('refresh.media', function(e) {
                if (e.detail && e.detail.inputId === prefix) {
                    refreshMediaGrid();
                }
            });

            // Refresh media grid on modal open (replace '#mediaPickerModal' with your modal's ID)
            $(`#${prefix}_modal`).on('show.bs.modal', function() {
                refreshMediaGrid();
            });

            // Upload files
            $(`#${prefix}_fileInput`).on('change', function(e) {
                const files = e.target.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(file => formData.append('files[]', file));
                formData.append('_token', '{{ csrf_token() }}');

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

                        if (res.success) {
                            Swal.fire('Uploaded!', res.message || 'File(s) uploaded.',
                                'success');

                            // Refresh media grid using the custom event
                            window.dispatchEvent(new CustomEvent('refresh.media', {
                                detail: {
                                    inputId: prefix
                                }
                            }));

                            // Clear input to allow re-upload of same files
                            $(`#${prefix}_fileInput`).val('');
                        }
                    },
                    error: function() {
                        $(`#${prefix}_uploadProgressContainer`).addClass('hidden');
                        Swal.fire('Error', 'Upload failed.', 'error');
                    }
                });
            });

            // Select media card in grid
            $(document).on('click', `#${prefix}_grid .media-card`, function() {
                const $this = $(this);
                if (!multipleSelect) {
                    $(`#${prefix}_grid .media-card`).removeClass('selected');
                    $this.addClass('selected');
                } else {
                    $this.toggleClass('selected');
                }
            });

            // Confirm media selection from modal
            window.confirmMediaSelection = function(prefix, mediaNameParam, isMultiple) {
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
                    const mime = $(this).data('mime_type');
                    const size = $(this).data('size');
                    const isVideo = mime && mime.startsWith('video');
                    const isPdf = mime === 'application/pdf';

                    values.push(url);

                    let previewHtml = '';
                    if (isPdf) {
                        previewHtml = `<img src="${pdfImage}" alt="PDF" class="preview-thumb">`;
                    } else if (isVideo) {
                        previewHtml =
                            `<video class="preview-thumb" src="${url}" controls muted preload="metadata"></video>`;
                    } else {
                        previewHtml = `<img src="${url}" alt="preview" class="preview-thumb">`;
                    }

                    previews.push(`
                    <div class="preview-card" data-url="${url}">
                        ${previewHtml}
                        <button type="button" class="remove-btn">&times;</button>
                        <div class="preview-info">
                            <div class="name">${name}</div>
                            <div class="size">${(size / 1024).toFixed(1)} KB</div>
                        </div>
                    </div>
                `);
                });

                const $mainPicker = $(`.media-picker[data-input-id="${prefix}"]`);
                $mainPicker.find(`input[type=hidden][name='${mediaNameParam}${isMultiple ? "[]" : ""}']`)
                    .remove();

                values.forEach(val => {
                    $('<input>').attr({
                        type: 'hidden',
                        name: `${mediaNameParam}${isMultiple ? '[]' : ''}`,
                        value: val,
                    }).appendTo($mainPicker);
                });

                $(`#${prefix}_display`).val(isMultiple ? values.map(v => v.split('/').pop()).join(', ') :
                    values[0].split('/').pop());
                $(`#${prefix}_previewCard`).html(previews.join(''));
            };

            // Remove preview card & corresponding input on clicking remove button
            $(document).on('click', `#${prefix}_previewCard .remove-btn`, function() {
                const $card = $(this).closest('.preview-card');
                const urlToRemove = $card.data('url');
                $card.remove();

                const $mainPicker = $(`.media-picker[data-input-id="${prefix}"]`);
                $mainPicker.find(`input[type=hidden][value="${urlToRemove}"]`).remove();

                const remaining = $mainPicker.find(
                        `input[type=hidden][name='${mediaName}${multipleSelect ? "[]" : ""}']`)
                    .map(function() {
                        return this.value;
                    }).get();

                $(`#${prefix}_display`).val(multipleSelect ? remaining.map(v => v.split('/').pop()).join(
                    ', ') : (remaining[0] || ''));
            });
        });
    </script>
@endpush
