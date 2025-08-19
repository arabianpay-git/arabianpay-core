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
                        @php
                            $mediaUrl = supplierMedia(
                                'storage/media/' . $item->file_name,
                                asset('assets/media/images/default-image.png'),
                            );
                        @endphp

                        <div class="media-card position-relative" data-id="{{ $item->id }}"
                            data-url="{{ $mediaUrl }}" data-file-name="{{ $item->file_name }}"
                            data-name="{{ $item->name }}" data-size="{{ $item->size }}"
                            data-mime="{{ $item->mime_type }}" style="overflow: visible; padding: 0.25rem;">
                            @php
                                $isVideo = str_starts_with($item->mime_type, 'video');
                            @endphp

                            @if ($isVideo)
                                <video src="{{ $mediaUrl }}" class="media-thumb" controls muted preload="metadata"
                                    style="max-height: 150px; width: auto;"></video>
                            @else
                                <img src="{{ $mediaUrl }}" class="media-thumb" loading="lazy" alt="{{ $item->name }}">
                            @endif

                            <div class="media-info">
                                <div class="name">{{ $item->name }}</div>
                                <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                            </div>

                            <div class="supplier-name">
                                User:
                                {{ $item->user ? $item->user->business_name : 'Unknown Supplier' }}
                            </div>

                            <div class="overlay-check"><i class="fas fa-check"></i></div>
                        </div>
                    @endforeach
                </div>

                {{-- Load more button + loader + end message --}}
                <div class="w-full text-center py-3" id="loadMoreWrapper">
                    <button id="loadMoreBtn" class="btn btn-sm btn-outline btn-secondary">
                        Load more
                        <span id="loadMoreSpinner" class="spinner-border spinner-border-sm d-none ml-2" role="status"
                            aria-hidden="true"></span>
                    </button>
                    <div id="loadMoreEnd" class="text-muted small mt-2 d-none">No more media</div>
                </div>

            </div>
        </div>
        <!-- End of Container -->
    </main>
@endsection

@push('scripts')
    <style>
        .supplier-name {
            font-size: 0.75rem;
            font-weight: 300;
            font-style: italic;
            color: #999;
            margin-top: 0.3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

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
            const $deleteBtn = $('#deleteSelectedBtn'),
                $uploadBtn = $('#uploadBtn'),
                $fileInput = $('#fileInput'),
                $uploadSpinner = $('#uploadSpinner'),
                $uploadProgressContainer = $('#uploadProgressContainer'),
                $uploadProgressBar = $('#uploadProgressBar'),
                $uploadProgressText = $('#uploadProgressText'),
                $mediaGrid = $('#mediaGrid'),
                $loadMoreBtn = $('#loadMoreBtn'),
                $loadMoreSpinner = $('#loadMoreSpinner'),
                $loadMoreEnd = $('#loadMoreEnd');

            // initial offset based on server-rendered items
            let selected = [],
                offset = parseInt({{ count($media) ?? 0 }}, 10) || 0,
                limit = 18,
                loading = false,
                noMoreMedia = false,
                attemptedLoad = false;

            // If initial set is less than limit, hide load more and show "No more" immediately
            if (offset < limit) {
                noMoreMedia = true;
                $loadMoreBtn.hide();
                $loadMoreEnd.removeClass('d-none');
            }

            function renderCard(media) {
                const isVideo = media.mime_type && media.mime_type.startsWith('video');
                const url = media.url || (`/storage/media/${media.file_name}`);
                let thumbHtml = '';

                if (isVideo) {
                    thumbHtml =
                        `<video src="${url}" class="media-thumb" controls muted preload="metadata" style="max-height:150px; width:auto;"></video>`;
                } else {
                    thumbHtml =
                        `<img src="${url}" class="media-thumb" loading="lazy" alt="media">`;
                }

                // Compose user full name or fallback text
                const userBusinessName = media.user ?
                    (media.user.business_name || '') :
                    'Unknown Supplier';

                return `
                    <div class="media-card position-relative" data-id="${media.id}" data-url="${url}" data-name="${media.name}" data-size="${media.size}" data-mime="${media.mime_type}" style="overflow: visible; padding: 0.25rem;">
                        ${thumbHtml}
                        <div class="media-info">
                            <div class="name">${media.name}</div>
                            <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                        </div>
                        <div class="supplier-name">
                            User: ${userBusinessName}
                        </div>
                        <div class="overlay-check"><i class="fas fa-check"></i></div>
                    </div>`;
            }

            function appendCards(list) {
                list.forEach(m => $mediaGrid.append(renderCard(m)));
            }

            async function loadMoreMedia() {
                if (loading || noMoreMedia) return;
                loading = true;
                attemptedLoad = true;

                // UI: disable button & show spinner
                $loadMoreBtn.prop('disabled', true);
                $loadMoreSpinner.removeClass('d-none');

                try {
                    const res = await $.get("{{ route('media.lazyLoad') }}", {
                        offset
                    });

                    if (!res || !Array.isArray(res.media)) {
                        console.error('Unexpected lazyLoad response', res);
                        Swal.fire('Error', 'Failed to load more media.', 'error');
                        return;
                    }

                    const received = res.media.length || 0;

                    if (received === 0) {
                        noMoreMedia = true;
                        $loadMoreBtn.hide();
                        $loadMoreEnd.removeClass('d-none');
                        return;
                    }

                    appendCards(res.media);
                    offset += received;

                    // if returned less than a full page, mark end
                    if (received < limit) {
                        noMoreMedia = true;
                        $loadMoreBtn.hide();
                        $loadMoreEnd.removeClass('d-none');
                    } else {
                        // re-enable button for next page
                        $loadMoreBtn.prop('disabled', false);
                    }
                } catch (err) {
                    console.error('lazyLoad error', err);
                    Swal.fire('Error', 'Failed to load more media. Try again.');
                    // re-enable button so user can retry
                    $loadMoreBtn.prop('disabled', false);
                } finally {
                    loading = false;
                    $loadMoreSpinner.addClass('d-none');
                }
            }

            // wire button
            $loadMoreBtn.on('click', function() {
                loadMoreMedia();
            });

            // selection / delete handlers (unchanged)
            function updateDeleteButton() {
                $deleteBtn.toggle(selected.length > 0);
            }

            $(document).on('click', '.media-card', function() {
                const id = $(this).data('id');
                if ($(this).hasClass('selected')) {
                    selected = selected.filter(i => i !== id);
                    $(this).removeClass('selected');
                } else {
                    selected.push(id);
                    $(this).addClass('selected');
                }
                updateDeleteButton();
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

            // upload handlers (unchanged)
            function startUpload() {
                $fileInput.click();
            }
            $uploadBtn.on('click', startUpload);

            $fileInput.on('change', function() {
                if (!this.files.length) return;
                uploadFiles(this.files);
            });

            function uploadFiles(files) {
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
                            if (res.media && Array.isArray(res.media)) {
                                res.media.forEach(m => {
                                    if (!m.url && m.file_name) m.url = `/storage/media/${m.file_name}`;
                                    $mediaGrid.prepend(renderCard(m));
                                });
                                offset += res.media.length;
                            }
                            Swal.fire('Uploaded!', res.message || 'Files uploaded successfully.', 'success');
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
            }
        });
    </script>
@endpush
