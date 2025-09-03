@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\File;

    $inputName = $name ?? 'media';
    $inputLabel = $label ?? 'Media';
    $inputRequired = $required ?? false;
    $inputValue = $value ?? '';
    $inputId = Str::slug($inputName, '_');
    $info = $info ?? null;

    $pdfImage = asset('assets/media/images/default-pdf.png');
    $mimeType = '';
    $isPdf = false;
    $isVideo = false;

    if ($inputValue) {
        $path = public_path(parse_url($inputValue, PHP_URL_PATH));
        if (file_exists($path)) {
            $mimeType = File::mimeType($path);
            $isPdf = strtolower(pathinfo($inputValue, PATHINFO_EXTENSION)) === 'pdf';
            $isVideo = str_starts_with($mimeType, 'video');
        }
    }
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
            <span class="text-xs text-gray-500">{{ $info }}</span>
        @endif
    </div>

    @error($inputName)
        <span class="text-danger text-sm">{{ $message }}</span>
    @enderror
</div>

<!-- Preview Card -->
<div class="mt-3 {{ $inputValue ? '' : 'hidden' }}" id="{{ $inputId }}_previewCard">
    <div class="media-card w-40">
        <img id="{{ $inputId }}_previewImage" class="media-thumb hidden" alt="Preview">
        <video id="{{ $inputId }}_previewVideo" class="media-thumb" controls muted preload="metadata"
            style="max-height: 160px; width: 100%;" hidden></video>

        <div class="media-info">
            <div id="{{ $inputId }}_previewName" class="name">{{ basename($inputValue) }}</div>
            <div id="{{ $inputId }}_previewSize" class="size text-sm text-gray-500"></div>
        </div>
    </div>
</div>

@include('media.modal', ['inputId' => $inputId])

<!-- Hidden file input for upload -->
<input type="file" id="{{ $inputId }}_fileInput" accept="image/*,video/*,.pdf,.svg" multiple class="hidden">

<!-- Upload Progress Bar -->
<div id="{{ $inputId }}_uploadProgressContainer" class="w-full max-w-md mt-2 hidden bg-gray-100 p-2 rounded">
    <div class="bg-gray-300 rounded h-2 overflow-hidden">
        <div id="{{ $inputId }}_uploadProgressBar" class="bg-blue-500 h-2 w-0 transition-all duration-300"></div>
    </div>
    <small id="{{ $inputId }}_uploadProgressText" class="text-sm text-gray-600">Uploading...</small>
</div>

@push('scripts')
    <script>
        $(function() {
            const prefix = @json($inputId);
            const pdfImage = @json($pdfImage);

            function resetPreview() {
                $(`#${prefix}_previewImage`).hide();
                $(`#${prefix}_previewVideo`).hide().attr('src', '');
            }

            function setPreview(fileUrl, name, size, mimeType) {
                const isPdf = fileUrl.toLowerCase().endsWith('.pdf');
                const isVideo = mimeType && mimeType.startsWith('video');

                if (isPdf) {
                    $(`#${prefix}_previewImage`).attr('src', pdfImage).show();
                    $(`#${prefix}_previewVideo`).hide();
                } else if (isVideo) {
                    $(`#${prefix}_previewVideo`).attr('src', fileUrl).show();
                    $(`#${prefix}_previewImage`).hide();
                } else {
                    $(`#${prefix}_previewImage`).attr('src', fileUrl).show();
                    $(`#${prefix}_previewVideo`).hide();
                }

                $(`#${prefix}_previewName`).text(name);
                $(`#${prefix}_previewSize`).text(`${(size / 1024).toFixed(1)} KB`);
                $(`#${prefix}_previewCard`).removeClass('hidden');
            }

            function loadMediaGrid() {
                $.get("{{ route('media.refresh') }}", {
                    limit: 18
                }, data => {
                    $(`#${prefix}_grid`).html(data.html);
                }).fail(() => {
                    Swal.fire('Error', 'Failed to refresh media list.', 'error');
                });
            }

            // Refresh media grid when modal is opened
            $(`#${prefix}_modal`).on('show.bs.modal', function() {
                loadMediaGrid();
            });

            // Modal media selection
            $(document).on('click', `#${prefix}_modal .media-card`, function() {
                const {
                    url,
                    name,
                    size,
                    mime
                } = $(this).data();

                $(`#${prefix}_display`).val(name);
                $(`#${prefix}`).val(url);

                setPreview(url, name, size, mime);

                const modal = document.getElementById(`${prefix}_modal`);
                if (modal) {
                    modal.classList.remove('show', 'open');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');
                }

                // Clean up backdrop and overflow
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());

            });

            // Upload handler with media refresh
            $(`#${prefix}_fileInput`).on('change', function(e) {
                const files = e.target.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(f => formData.append('files[]', f));
                formData.append('_token', "{{ csrf_token() }}");

                $(`#${prefix}_uploadProgressContainer`).removeClass('hidden');
                $(`#${prefix}_uploadProgressBar`).width('0%');
                $(`#${prefix}_uploadProgressText`).text('Uploading...');

                $.ajax({
                    url: "{{ route('media.upload') }}",
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    xhr: () => {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                $(`#${prefix}_uploadProgressBar`).width(`${percent}%`);
                                $(`#${prefix}_uploadProgressText`).text(
                                    `Uploading... ${percent}%`);
                            }
                        }, false);
                        return xhr;
                    },
                    success: response => {
                        $(`#${prefix}_uploadProgressContainer`).addClass('hidden');
                        if (response.success) {
                            // Refresh media grid
                            $.get("{{ route('media.refresh') }}", {
                                limit: 18
                            }, data => {
                                $(`#${prefix}_grid`).html(data.html);
                                Swal.fire('Uploaded!', response.message ||
                                    'File(s) uploaded successfully.', 'success');
                            }).fail(() => {
                                Swal.fire('Error', 'Failed to refresh media list.',
                                    'error');
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Upload failed.', 'error');
                        }
                    },
                    error: xhr => {
                        $(`#${prefix}_uploadProgressContainer`).addClass('hidden');
                        let errorMessage = 'Upload failed.';
                        if (xhr.responseJSON) {
                            errorMessage = xhr.responseJSON.message ||
                                Object.values(xhr.responseJSON.errors || {}).flat().join(
                                    '<br>');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMessage
                        });
                    }
                });
            });

            // Infinite scroll for media modal
            let offset = 18;
            const limit = 18;
            let isLoading = false;

            $(`#${prefix}_modal .modal-body`).on('scroll', function() {
                if (isLoading) return;

                const container = $(this);
                if (container.scrollTop() + container.innerHeight() >= container[0].scrollHeight - 10) {
                    isLoading = true;
                    $.get("{{ route('media.lazyLoad') }}", {
                        offset,
                        limit
                    }, response => {
                        if (response.media.length) {
                            offset += limit;
                            response.media.forEach(media => {
                                const isPdf = media.mime_type === 'application/pdf';
                                const isVideo = media.mime_type.startsWith('video');
                                const imageUrl = isPdf ? pdfImage :
                                    `/storage/media/${media.file_name}`;

                                $(`#${prefix}_grid`).append(`
                            <div class="media-card position-relative"
                                 data-id="${media.id}"
                                 data-url="/storage/media/${media.file_name}"
                                 data-name="${media.name}"
                                 data-size="${media.size}"
                                 data-mime="${media.mime_type}">
                                 ${isVideo
                                    ? `<video src="/storage/media/${media.file_name}" class="media-thumb" controls muted preload="metadata" style="max-height:150px; width:auto;"></video>`
                                    : `<img src="${imageUrl}" class="media-thumb" alt="media">`}
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
                    }).fail(() => {
                        isLoading = false;
                        Swal.fire('Error', 'Failed to load more media.', 'error');
                    });
                }
            });
        });
    </script>
@endpush
