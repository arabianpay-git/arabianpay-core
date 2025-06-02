@php
    $inputName = $name ?? 'media';
    $inputLabel = $label ?? 'Media';
    $inputRequired = $required ?? false;
    $inputValue = $value ?? '';
    $inputId = Str::slug($inputName, '_');
    $info = $info ?? null;

    // Check if the inputValue is a PDF
    $isPdf = $inputValue && pathinfo($inputValue, PATHINFO_EXTENSION) === 'pdf';
    // dd($isPdf);
    $pdfImage = 'assets/media/images/default-pdf.png';
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

        <img id="{{ $inputId }}_previewImage" class="media-thumb"
            src="{{ $isPdf ? asset($pdfImage) : $inputValue }}" alt="Preview">
        <div class="media-info">
            <div id="{{ $inputId }}_previewName" class="name">{{ basename($inputValue) }}</div>
            <div id="{{ $inputId }}_previewSize" class="size"></div>
        </div>
    </div>
</div>

<!-- Media Modal -->
@include('media.modal', ['inputId' => $inputId])

@push('scripts')
    <script>
        $(function() {
            const prefix = @json($inputId);

            function bindMediaModal() {
                $(document).on('click', `#${prefix}_modal .media-card`, function() {
                    const url = $(this).data('url');
                    const name = $(this).data('name');
                    const size = $(this).data('size');

                    // Fill inputs
                    $(`#${prefix}_display`).val(name);
                    $(`#${prefix}`).val(url);

                    // Preview: use default for PDFs
                    const isPdf = url.toLowerCase().endsWith('.pdf');
                    const thumb = isPdf ? '/assets/media/images/default-pdf.png' : url;
                    $(`#${prefix}_previewImage`).attr('src', thumb);
                    $(`#${prefix}_previewName`).text(name);
                    $(`#${prefix}_previewSize`).text(size);
                    $(`#${prefix}_previewCard`).removeClass('d-none');

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

                // Upload file handler
                $(`#${prefix}_fileInput`).on('change', function(e) {
                    let file = e.target.files[0];
                    if (!file) return;

                    let formData = new FormData();
                    formData.append('files[]', file);
                    formData.append('_token', "{{ csrf_token() }}");

                    $(`#${prefix}_loadingSpinner`).removeClass('d-none');

                    $.ajax({
                        url: "{{ route('media.upload') }}",
                        method: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            $(`#${prefix}_loadingSpinner`).addClass('d-none');

                            if (response.success) {
                                response.media.forEach(media => {
                                    $(`#${prefix}_grid`).prepend(`
                                <div class="media-card position-relative"
                                    data-id="${media.id}"
                                    data-url="/storage/media/${media.file_name}"
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

                                Swal.fire('Uploaded!', response.message ||
                                    'File uploaded successfully.', 'success');
                            } else {
                                Swal.fire('Error', response.message || 'Upload failed.',
                                    'error');
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

                // Lazy load more media on scroll
                let offset = 18;
                const limit = 18;
                let isLoading = false;

                $(`#${prefix}_modal .modal-body`).on('scroll', function() {
                    if (isLoading) return;

                    const bottom = $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight;
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
                                        // Check if file is PDF
                                        const isPdf = media.mime_type ===
                                            'application/pdf';
                                        const imageUrl = isPdf ?
                                            '/assets/media/images/default-pdf.png' :
                                            `/storage/media/${media.file_name}`;

                                        $(`#${prefix}_grid`).append(`
                                    <div class="media-card position-relative"
                                        data-id="${media.id}"
                                        data-url="/storage/media/${media.file_name}"
                                        data-name="${media.name}"
                                        data-size="${(media.size / 1024).toFixed(1)} KB">
                                        <img src="${imageUrl}" class="media-thumb" alt="media">
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

            }

            bindMediaModal();
        });
    </script>
@endpush
