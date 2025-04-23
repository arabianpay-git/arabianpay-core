<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', sans-serif;
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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
        border: 2px solid #007bff;
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

    .media-card.selected .overlay-check {
        display: block;
    }

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
        box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }

    .remove-btn {
        position: absolute;
        top: 6px;
        right: 6px;
        background: rgba(0,0,0,0.6);
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
@endphp

<div class="w-full">
    <div class="flex items-baseline flex-wrap gap-2.5">
        <label class="form-label flex items-center gap-1 max-w-56">
            {{ $label }}
            @if ($isRequired)
                <span class="text-danger">*</span>
            @endif
        </label>

        <div class="flex items-center w-full max-w-md relative">
            <button type="button" class="absolute top-0 bottom-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-r" data-modal-toggle="#{{ $inputId }}_modal">
                <i class="ki-filled ki-folder text-xl"></i>
            </button>
            <input
                type="text"
                id="{{ $inputId }}_display"
                class="input w-full"
                placeholder="Click to select media"
                readonly
                value="{{ $isMultipleSelect ? implode(', ', array_map('basename', $inputValue)) : basename($inputValue[0]) }}"
                data-modal-toggle="#{{ $inputId }}_modal"
                style="padding-inline-start: 2.75rem;"
            >
            @foreach ($inputValue as $val)
                <input type="hidden" name="{{ $mediaName }}{{ $isMultipleSelect ? '[]' : '' }}" value="{{ $val }}">
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
                    <img class="media-thumb" src="{{ $val }}" alt="Preview">
                    <div class="media-info">
                        <div class="name">{{ basename($val) }}</div>
                    </div>
                    <button class="remove-btn absolute top-1 right-1 bg-white text-black rounded-full text-xs w-5 h-5 leading-5 text-center">&times;</button>
                </div>
            @endif
        @endforeach
    </div>
</div>

<!-- Media Modal -->
<div class="modal" data-modal="true" id="{{ $inputId }}_modal">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Select Media</h5>
                <button type="button" class="btn btn-primary" onclick="$('#fileInput').click()">Upload File</button>
                <input type="file" id="fileInput" accept="image/*" multiple hidden>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="media-grid" id="mediaModalGrid">
                    @php $media = App\Models\Media::latest()->take(18)->get(); @endphp
                    @foreach ($media as $item)
                        <div class="media-card"
                            data-id="{{ $item->id }}"
                            data-url="{{ asset('storage/media/' . $item->file_name) }}"
                            data-name="{{ $item->name }}"
                            data-size="{{ number_format($item->size / 1024, 1) }} KB">
                            <img src="{{ asset('storage/media/' . $item->file_name) }}" class="media-thumb" alt="media">
                            <div class="media-info">
                                <div class="name">{{ $item->name }}</div>
                                <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                            </div>
                            <div class="overlay-check"><i class="fas fa-check"></i></div>
                        </div>
                    @endforeach
                </div>
                <div id="loadingSpinner" class="text-center d-none">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer justify-end border-0" style="display: flex; margin-right: 3rem; padding-bottom: 1rem;">
                <button type="button" class="btn btn-primary" onclick="confirmMediaSelection('{{ $inputId }}', '{{ $mediaName }}', {{ $isMultipleSelect ? 'true' : 'false' }})" data-modal-dismiss="true" style="margin-right: 1rem">Select</button>
                <button type="button" class="btn btn-light" data-modal-dismiss="true">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let offset = {{ $media->count() }};
    let limit = 18;
    let isLoading = false;

    // Close modal after selecting media
    function confirmMediaSelection(inputId, mediaName, isMultiple) {
        const selectedCards = document.querySelectorAll(`#${inputId}_modal .media-card.selected`);
        const display = document.getElementById(`${inputId}_display`);
        const previewContainer = document.getElementById(`${inputId}_previewCard`);
        let urls = [], names = [];

        selectedCards.forEach(card => {
            urls.push(card.dataset.url);
            names.push(card.dataset.name);
        });

        // Clear previews and hidden inputs
        previewContainer.innerHTML = '';
        $(`input[name="${mediaName}${isMultiple ? '[]' : ''}"]`).remove();

        // Set display field
        display.value = names.join(', ');

        // Update previews
        urls.forEach((url, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = mediaName + (isMultiple ? '[]' : '');
            input.value = url;
            display.insertAdjacentElement('afterend', input);

            const card = document.createElement('div');
            card.className = 'media-card relative';
            card.style.width = '160px';
            card.setAttribute('data-url', url);
            card.innerHTML = `
                <img class="media-thumb" src="${url}" alt="Preview">
                <div class="media-info"><div class="name">${names[index]}</div></div>
                ${isMultiple ? `<button class="remove-btn absolute top-1 right-1 bg-white text-black rounded-full text-xs w-5 h-5 leading-5 text-center">&times;</button>` : ''}
            `;
            previewContainer.appendChild(card);
        });

        // Close modal after selection
        $(`#${inputId}_modal`).modal('hide');
    }

    // Remove preview and unselect the media in modal
    $(document).on('click', '.remove-btn', function (e) {
        e.preventDefault();
        const card = $(this).closest('.media-card');
        const url = card.data('url');

        // Remove the hidden input
        $(`input[type="hidden"][value="${url}"]`).remove();
        card.remove();

        const updatedNames = [];
        $('#{{ $inputId }}_previewCard .name').each(function () {
            updatedNames.push($(this).text().trim());
        });
        $('#{{ $inputId }}_display').val(updatedNames.join(', '));

        // Unselect the media card in the modal
        const modalCard = $(`#{{ $inputId }}_modal .media-card[data-url="${url}"]`);
        modalCard.removeClass('selected');
    });

    // Toggle selection for media card in modal
    $(document).on('click', '.media-card', function () {
        const isMultiple = {{ $isMultipleSelect ? 'true' : 'false' }};
        if (!isMultiple) {
            $(this).siblings().removeClass('selected');
        }
        $(this).toggleClass('selected');
    });

    // File upload
    $('#fileInput').on('change', function (e) {
        const files = e.target.files;
        if (!files.length) return;

        $('#loadingSpinner').removeClass('d-none');

        const formData = new FormData();
        Array.from(files).forEach(file => formData.append('files[]', file));
        formData.append('_token', "{{ csrf_token() }}");

        $.ajax({
            url: "{{ route('media.upload') }}",
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                $('#loadingSpinner').addClass('d-none');
                if (response.success && response.media.length) {
                    response.media.forEach(media => {
                        $('#mediaModalGrid').prepend(`
                            <div class="media-card"
                                data-id="${media.id}"
                                data-url="${media.url}"
                                data-name="${media.name}"
                                data-size="${(media.size / 1024).toFixed(1)} KB">
                                <img src="/storage/media/${media.file_name}" class="media-thumb" alt="media">
                                <div class="media-info">
                                    <div class="name">${media.name}</div>
                                    <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                                </div>
                                <div class="overlay-check"><i class="fas fa-check"></i></div>
                            </div>
                        `);
                    });
                }
            },
            error: (xhr) => {
                $(`#${prefix}_loadingSpinner`).addClass('d-none');

                let errorMessage = 'Upload failed.';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage
                });
            }
        });
    });

    // Infinite scroll in modal
    $(`#{{ $inputId }}_modal .modal-body`).on('scroll', function () {
        const modalBody = $(this);
        if (modalBody.scrollTop() + modalBody.innerHeight() >= modalBody[0].scrollHeight - 100 && !isLoading) {
            isLoading = true;
            $('#loadingSpinner').removeClass('d-none');

            $.ajax({
                url: "{{ route('media.lazyLoad') }}",
                method: 'GET',
                data: { offset, limit },
                success: function (response) {
                    $('#loadingSpinner').addClass('d-none');
                    if (response.length) {
                        response.forEach(media => {
                            $('#mediaModalGrid').append(`
                                <div class="media-card"
                                    data-id="${media.id}"
                                    data-url="${media.url}"
                                    data-name="${media.name}"
                                    data-size="${(media.size / 1024).toFixed(1)} KB">
                                    <img src="${media.url}" class="media-thumb" alt="media">
                                    <div class="media-info">
                                        <div class="name">${media.name}</div>
                                        <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                                    </div>
                                    <div class="overlay-check"><i class="fas fa-check"></i></div>
                                </div>
                            `);
                        });
                        offset += limit;
                        isLoading = false;
                    }
                },
                error: function () {
                    $('#loadingSpinner').addClass('d-none');
                    console.error('Failed to load more media.');
                }
            });
        }
    });
</script>
@endpush
