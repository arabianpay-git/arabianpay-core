@php
    use Illuminate\Support\Str;

    $inputName = $name ?? 'media';
    $inputLabel = $label ?? 'Media';
    $inputRequired = $required ?? false;
    $inputValue = $value ?? '';
    $inputId = Str::slug($inputName, '_');
    $info = $info ?? null;

    $isPdf = $inputValue && strtolower(pathinfo($inputValue, PATHINFO_EXTENSION)) === 'pdf';
    $pdfImage = asset('assets/media/images/default-pdf.png');
@endphp

<div class="w-full">
    <div class="flex items-baseline flex-wrap gap-2.5">
        <label class="form-label flex items-center gap-1">
            {{ $inputLabel }}
            @if ($inputRequired)
                <span class="text-danger">*</span>
            @endif
        </label>

        <div class="flex items-center w-full max-w-md relative">
            <button type="button"
                class="absolute top-0 bottom-0 px-3 flex items-center justify-center hover:bg-primary-light hover:text-primary text-gray-500 rounded-r"
                data-modal-toggle="#{{ $inputId }}_modal">
                <i class="ki-filled ki-folder text-xl"></i>
            </button>
            <input type="text" id="{{ $inputId }}_display"
                class="input w-full @error($inputName) border-red-500 @enderror" placeholder="Click to select media"
                readonly value="{{ basename($inputValue) }}" data-modal-toggle="#{{ $inputId }}_modal"
                style="padding-inline-start: 2.75rem;">
            <input type="hidden" id="{{ $inputId }}" name="{{ $inputName }}" value="{{ $inputValue }}">

        </div>
        @if ($info)
            <span style="font-size: 10px; margin-top: -.625rem;">{{ $info }}</span>
        @endif
    </div>
    @error($inputName)
        <span class="text-danger text-sm">{{ $message }}</span>
    @enderror
</div>

<!-- Preview Card -->
<div class="mt-3 {{ $inputValue ? '' : 'd-none' }}" id="{{ $inputId }}_previewCard">
    <div class="media-card" style="width: 160px;">
        <img id="{{ $inputId }}_previewImage" class="media-thumb" alt="Preview" style="display:none;">
        <video id="{{ $inputId }}_previewVideo" class="media-thumb" controls muted preload="metadata"
            style="max-height: 160px; width: 100%; display:none;"></video>
        @if ($isPdf)
            <img id="{{ $inputId }}_previewImage" src="{{ $pdfImage }}" style="display:block;"
                alt="PDF Preview">
        @elseif($inputValue && preg_match('/^video\//', \File::mimeType(public_path($inputValue))))
            <script>
                // On page load, if the initial value is a video, set video src and show video element
                window.addEventListener('DOMContentLoaded', () => {
                    const video = document.getElementById('{{ $inputId }}_previewVideo');
                    const img = document.getElementById('{{ $inputId }}_previewImage');
                    video.src = "{{ $inputValue }}";
                    video.style.display = 'block';
                    img.style.display = 'none';
                });
            </script>
        @else
            <img id="{{ $inputId }}_previewImage" src="{{ $inputValue }}" style="display:block;"
                alt="Preview">
        @endif
        <div class="media-info">
            <div id="{{ $inputId }}_previewName" class="name">{{ basename($inputValue) }}</div>
            <div id="{{ $inputId }}_previewSize" class="size"></div>
        </div>
    </div>
</div>

<!-- Media Modal -->
@include('media.modal', ['inputId' => $inputId])

<!-- Hidden file input for upload -->
<input type="file" id="{{ $inputId }}_fileInput" accept="image/*,video/*,.pdf,.svg" multiple class="hidden">

<!-- Upload Progress Bar -->
<div id="{{ $inputId }}_uploadProgressContainer" class="w-full max-w-md mt-2 hidden"
    style="background: #eee; padding: 8px; border-radius: 4px;">
    <div class="bg-gray-200 rounded h-2 overflow-hidden" style="background: #ccc;">
        <div id="{{ $inputId }}_uploadProgressBar"
            class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"
            style="background: #007bff; height: 8px; width: 0;"></div>
    </div>
    <small id="{{ $inputId }}_uploadProgressText" class="text-sm text-gray-600">Uploading...</small>
</div>


@push('scripts')
    <script>
        $(function() {
            const prefix = @json($inputId);

            function resetPreview() {
                $(`#${prefix}_previewImage`).hide();
                $(`#${prefix}_previewVideo`).hide().attr('src', '');
            }

            function setPreview(fileUrl, name, size, mimeType) {
                const isPdf = fileUrl.toLowerCase().endsWith('.pdf');
                const isVideo = mimeType && mimeType.startsWith('video');

                if (isPdf) {
                    $(`#${prefix}_previewImage`).attr('src', '{{ $pdfImage }}').show();
                    $(`#${prefix}_previewVideo`).hide().attr('src', '');
                } else if (isVideo) {
                    $(`#${prefix}_previewVideo`).attr('src', fileUrl).show();
                    $(`#${prefix}_previewImage`).hide();
                } else {
                    $(`#${prefix}_previewImage`).attr('src', fileUrl).show();
                    $(`#${prefix}_previewVideo`).hide().attr('src', '');
                }

                $(`#${prefix}_previewName`).text(name);
                $(`#${prefix}_previewSize`).text(`${(size/1024).toFixed(1)} KB`);
                $(`#${prefix}_previewCard`).removeClass('d-none');
            }

            // Media card click inside modal
            $(document).on('click', `#${prefix}_modal .media-card`, function() {
                const url = $(this).data('url');
                const name = $(this).data('name');
                const size = $(this).data('size');
                const mimeType = $(this).data('mime');

                // Fill inputs
                $(`#${prefix}_display`).val(name);
                $(`#${prefix}`).val(url);

                setPreview(url, name, size, mimeType);

                // Close modal
                const modal = document.getElementById(`${prefix}_modal`);
                if (modal) {
                    modal.classList.remove('show', 'open');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');
                }
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            });

            // Upload file input handler (unchanged, but see media-card render below)
            $(`#${prefix}_fileInput`).on('change', function(e) {
                let files = e.target.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(f => formData.append('files[]', f));
                formData.append('_token', "{{ csrf_token() }}");

                $(`#${prefix}_uploadProgressContainer`).removeClass('hidden');

                $(`#${prefix}_uploadProgressBar`).css('width', '0%');
                $(`#${prefix}_uploadProgressText`).text('Uploading...');
                $(`#${prefix}_loadingSpinner`).removeClass('d-none');

                $.ajax({
                    url: "{{ route('media.upload') }}",
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                $(`#${prefix}_uploadProgressBar`).css('width', percent +
                                    '%');
                                $(`#${prefix}_uploadProgressText`).text(
                                    `Uploading... ${percent}%`);
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        $(`#${prefix}_loadingSpinner`).addClass('d-none');
                        $(`#${prefix}_uploadProgressContainer`).addClass('d-none');

                        if (response.success) {
                            response.media.forEach(media => {
                                const isPdf = media.mime_type === 'application/pdf';
                                const isVideo = media.mime_type.startsWith('video');
                                const imageUrl = isPdf ? '{{ $pdfImage }}' :
                                    `/storage/media/${media.file_name}`;

                                $(`#${prefix}_grid`).prepend(`
                                <div class="media-card position-relative"
                                    data-id="${media.id}"
                                    data-url="/storage/media/${media.file_name}"
                                    data-name="${media.name}"
                                    data-size="${media.size}"
                                    data-mime="${media.mime_type}">
                                    ${
                                        isVideo
                                        ? `<video src="/storage/media/${media.file_name}" class="media-thumb" controls muted preload="metadata" style="max-height:150px; width:auto;"></video>`
                                        : `<img src="${imageUrl}" class="media-thumb" alt="media">`
                                    }
                                    <div class="media-info">
                                        <div class="name">${media.name}</div>
                                        <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                                    </div>
                                    <div class="overlay-check"><i class="fas fa-check"></i></div>
                                </div>
                            `);
                            });

                            Swal.fire('Uploaded!', response.message ||
                                'File(s) uploaded successfully.', 'success');
                        } else {
                            Swal.fire('Error', response.message || 'Upload failed.', 'error');
                        }
                    },
                    error: function(xhr) {
                        $(`#${prefix}_loadingSpinner`).addClass('d-none');
                        $(`#${prefix}_uploadProgressContainer`).addClass('d-none');

                        let errorMessage = 'Upload failed.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                const errors = Object.values(xhr.responseJSON.errors).flat();
                                errorMessage = errors.join('<br>');
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMessage
                        });
                    }
                });
            });

            // Lazy load more media on modal scroll (unchanged)
            let offset = 18;
            const limit = 18;
            let isLoading = false;

            $(`#${prefix}_modal .modal-body`).on('scroll', function() {
                if (isLoading) return;

                const bottom = $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight - 10;
                if (bottom) {
                    isLoading = true;
                    $.ajax({
                        url: "{{ route('media.lazyLoad') }}",
                        method: 'GET',
                        data: {
                            offset,
                            limit
                        },
                        success: function(response) {
                            if (response.media.length > 0) {
                                offset += limit;
                                response.media.forEach(media => {
                                    const isPdf = media.mime_type === 'application/pdf';
                                    const isVideo = media.mime_type.startsWith('video');
                                    const imageUrl = isPdf ? '{{ $pdfImage }}' :
                                        `/storage/media/${media.file_name}`;

                                    $(`#${prefix}_grid`).append(`
                                    <div class="media-card position-relative"
                                        data-id="${media.id}"
                                        data-url="/storage/media/${media.file_name}"
                                        data-name="${media.name}"
                                        data-size="${media.size}"
                                        data-mime="${media.mime_type}">
                                        ${
                                            isVideo
                                            ? `<video src="/storage/media/${media.file_name}" class="media-thumb" controls muted preload="metadata" style="max-height:150px; width:auto;"></video>`
                                            : `<img src="${imageUrl}" class="media-thumb" alt="media">`
                                        }
                                        <div class="media-info">
                                            <div class="name">${media.name}</div>
                                            <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                                        </div>
                                        <div class="overlay-check"><i class="fas fa-check"></i></div>
                                    </div>
                                `);
                                });
                            }
                            isLoading = false;
                        },
                        error: function() {
                            isLoading = false;
                            Swal.fire('Error', 'Failed to load more media.', 'error');
                        }
                    });
                }
            });

        });
    </script>
@endpush
