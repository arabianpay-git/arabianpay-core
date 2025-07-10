@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
        <!-- Container -->
        <div class="container-fixed" id="content_container"></div>

        <div class="container-fixed">
            <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                <div class="flex flex-col justify-center gap-2">
                    <h1 class="text-xl font-medium leading-none text-gray-900">
                        Media Manager
                    </h1>
                </div>
                <div class="flex items-center gap-2.5">
                    <button class="btn btn-sm btn-danger delete-selected-btn d-none" id="deleteSelectedBtn">Delete
                        Selected</button>
                    <button class="btn btn-sm btn-primary upload-btn" id="uploadBtn">Upload File</button>
                    <input type="file" id="fileInput" accept="image/*,video/*,.pdf,.svg" multiple class="hidden">
                    <div id="uploadSpinner" class="spinner-border spinner-border-sm d-none ml-2" role="status">
                        <span class="sr-only">Uploading...</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Container -->

        <!-- Upload Progress Bar -->
        <div class="container-fixed">
            <div id="uploadProgressContainer" class="w-full mt-2 d-none">
                <div class="bg-gray-200 rounded h-2 overflow-hidden">
                    <div id="uploadProgressBar" class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"></div>
                </div>
                <small id="uploadProgressText" class="text-sm text-gray-600">Uploading...</small>
            </div>
        </div>

        <!-- Media Grid -->
        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="media-grid" id="mediaGrid" style="padding:0">
                    @foreach ($media as $item)
                        <div class="media-card position-relative" data-id="{{ $item->id }}"
                            data-url="{{ asset('storage/media/' . $item->file_name) }}" data-name="{{ $item->name }}"
                            data-size="{{ $item->size }}" data-mime="{{ $item->mime_type }}"
                            style="overflow: visible; padding: 0.25rem;">
                            @php
                                $isVideo = str_starts_with($item->mime_type, 'video');
                            @endphp
                            @if ($isVideo)
                                <video src="{{ asset('storage/media/' . $item->file_name) }}" class="media-thumb" controls
                                    muted preload="metadata" style="max-height: 150px; width: auto;"></video>
                            @else
                                <img src="{{ asset('storage/media/' . $item->file_name) }}" class="media-thumb"
                                    loading="lazy" alt="media">
                            @endif
                            <div class="media-info">
                                <div class="name">{{ $item->name }}</div>
                                <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                            </div>
                            <div class="overlay-check"><i class="fas fa-check"></i></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection
@push('scripts')
    <style>
        body.dragging::before {
            content: "📤 Drop files to upload";
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 3px dashed #0d6efd;
            color: #0d6efd;
            font-size: 1.75rem;
            font-weight: 600;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            pointer-events: none;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 0 30px rgba(13, 110, 253, 0.3);
            animation: pulse-border 1.5s infinite;
        }

        @keyframes pulse-border {
            0% {
                border-color: #0d6efd;
            }

            50% {
                border-color: #6ea8fe;
            }

            100% {
                border-color: #0d6efd;
            }
        }
    </style>

    <script>
        $(function() {
            const $window = $(window),
                $document = $(document),
                $deleteBtn = $('#deleteSelectedBtn'),
                $uploadBtn = $('#uploadBtn'),
                $fileInput = $('#fileInput'),
                $uploadSpinner = $('#uploadSpinner'),
                $uploadProgressContainer = $('#uploadProgressContainer'),
                $uploadProgressBar = $('#uploadProgressBar'),
                $uploadProgressText = $('#uploadProgressText'),
                $mediaGrid = $('#mediaGrid');

            let selected = [],
                offset = {{ count($media) }},
                loading = false,
                noMoreMedia = false;

            // === Full Page Drag and Drop ===
            $(document).on('dragenter dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('body').addClass('dragging');
            });

            $(document).on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('body').removeClass('dragging');
            });

            $(document).on('drop', function(e) {
                const dt = e.originalEvent.dataTransfer;
                const files = dt.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(f => formData.append('files[]', f));
                formData.append('_token', "{{ csrf_token() }}");

                $uploadSpinner.removeClass('d-none');
                $uploadProgressContainer.removeClass('d-none');
                $uploadProgressBar.css('width', '0%');
                $uploadProgressText.text('Uploading...');

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
                                    $uploadProgressBar.css('width', percent + '%');
                                    $uploadProgressText.text(`Uploading... ${percent}%`);
                                }
                            }, false);
                            return xhr;
                        }
                    })
                    .done(res => {
                        if (res.success) {
                            res.media.forEach(m => updateGrids(renderCard(m), true));
                            Swal.fire('Uploaded!', res.message || 'Files uploaded successfully.',
                                'success');
                        } else {
                            Swal.fire('Error', res.message || 'Upload failed.', 'error');
                        }
                    })
                    .fail(xhr => {
                        let msg = 'Upload failed.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: msg
                        });
                    })
                    .always(() => {
                        $uploadSpinner.addClass('d-none');
                        $uploadProgressContainer.addClass('d-none');
                        $fileInput.val('');
                    });
            });

            function throttle(fn, limit) {
                let waiting = false;
                return function(...args) {
                    if (!waiting) {
                        fn.apply(this, args);
                        waiting = true;
                        setTimeout(() => waiting = false, limit);
                    }
                };
            }

            function updateDeleteButton() {
                $deleteBtn.toggle(selected.length > 0);
            }

            function toggleCardSelection(el) {
                const id = $(el).data('id');
                if ($(el).hasClass('selected')) {
                    selected = selected.filter(i => i !== id);
                    $(el).removeClass('selected');
                } else {
                    selected.push(id);
                    $(el).addClass('selected');
                }
                updateDeleteButton();
            }

            $document.on('click', '.media-card', function() {
                toggleCardSelection(this);
            });

            $deleteBtn.on('click', function() {
                if (!selected.length) return;
                Swal.fire({
                    title: 'Delete selected?',
                    text: 'These files will be permanently removed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Delete'
                }).then(res => {
                    if (res.isConfirmed) {
                        $.post("{{ route('media.bulkDelete') }}", {
                                _token: "{{ csrf_token() }}",
                                ids: selected
                            })
                            .done(r => {
                                selected.forEach(id => $(`.media-card[data-id="${id}"]`)
                                    .remove());
                                selected = [];
                                updateDeleteButton();
                                Swal.fire('Deleted!', r.message, 'success');
                            })
                            .fail(() => Swal.fire('Error', 'Failed to delete media.', 'error'));
                    }
                });
            });

            function renderCard(media) {
                const isVideo = media.mime_type && media.mime_type.startsWith('video');
                let thumbHtml = '';

                if (isVideo) {
                    thumbHtml =
                        `<video src="/storage/media/${media.file_name}" class="media-thumb" controls muted preload="metadata" style="max-height:150px; width:auto;"></video>`;
                } else {
                    thumbHtml =
                        `<img src="/storage/media/${media.file_name}" class="media-thumb" loading="lazy" alt="media">`;
                }

                return `
                    <div class="media-card position-relative" data-id="${media.id}" data-url="${media.url}" data-name="${media.name}" data-size="${media.size}" data-mime="${media.mime_type}">
                        ${thumbHtml}
                        <div class="media-info">
                            <div class="name">${media.name}</div>
                            <div class="size">${(media.size/1024).toFixed(1)} KB</div>
                        </div>
                        <div class="overlay-check"><i class="fas fa-check"></i></div>
                    </div>`;
            }

            function updateGrids(html, prepend = false) {
                if (prepend) {
                    $mediaGrid.prepend(html);
                } else {
                    $mediaGrid.append(html);
                }
            }

            function loadMoreMedia() {
                if (loading || noMoreMedia) return;
                loading = true;

                $.get("{{ route('media.lazyLoad') }}", {
                        offset
                    })
                    .done(res => {
                        if (!res.media.length) {
                            noMoreMedia = true;
                        } else {
                            res.media.forEach(m => updateGrids(renderCard(m)));
                            offset += res.media.length;
                        }
                    })
                    .always(() => {
                        loading = false;
                    });
            }

            $window.on('scroll', throttle(() => {
                if ($window.scrollTop() + $window.height() >= $document.height() - 300) loadMoreMedia();
            }, 200));

            function startUpload() {
                $fileInput.click();
            }

            $uploadBtn.on('click', startUpload);

            $fileInput.on('change', function() {
                const files = this.files;
                if (!files.length) return;

                const formData = new FormData();
                Array.from(files).forEach(f => formData.append('files[]', f));
                formData.append('_token', "{{ csrf_token() }}");

                $uploadSpinner.removeClass('d-none');
                $uploadProgressContainer.removeClass('d-none');
                $uploadProgressBar.css('width', '0%');
                $uploadProgressText.text('Uploading...');

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
                                    $uploadProgressBar.css('width', percent + '%');
                                    $uploadProgressText.text(`Uploading... ${percent}%`);
                                }
                            }, false);
                            return xhr;
                        }
                    })
                    .done(res => {
                        if (res.success) {
                            res.media.forEach(m => updateGrids(renderCard(m), true));
                            Swal.fire('Uploaded!', res.message || 'Files uploaded successfully.',
                                'success');
                        } else {
                            Swal.fire('Error', res.message || 'Upload failed.', 'error');
                        }
                    })
                    .fail(xhr => {
                        let msg = 'Upload failed.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: msg
                        });
                    })
                    .always(() => {
                        $uploadSpinner.addClass('d-none');
                        $uploadProgressContainer.addClass('d-none');
                        $fileInput.val('');
                    });
            });
        });
    </script>
@endpush
