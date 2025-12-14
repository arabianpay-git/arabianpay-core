@extends('layouts.base')

@section('content')
    <main class="grow content pt-5" id="content" role="content">
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
        <div class="container-fixed">
            <div id="uploadProgressContainer" class="w-full mt-2 d-none">
                <div class="bg-gray-200 rounded h-2 overflow-hidden">
                    <div id="uploadProgressBar" class="bg-primary h-full w-0 transition-all duration-300 ease-in-out"></div>
                </div>
                <small id="uploadProgressText" class="text-sm text-gray-600">Uploading...</small>
            </div>
        </div>

        <div class="container-fixed">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="media-grid" id="mediaGrid">
                    @foreach ($media as $item)
                        @php
                            $mediaUrl = getMediaUrl($item->file_name, asset('assets/media/images/default-image.png'));
                            $isVideo = str_starts_with($item->mime_type, 'video');
                            $isPdf = $item->mime_type === 'application/pdf';
                            $userName = $item->user
                                ? ($item->user->business_name ?:
                                $item->user->first_name . ' ' . $item->user->last_name)
                                : 'Unknown User';
                            $sizeKB = number_format($item->size / 1024, 1) . ' KB';

                        @endphp

                        <div class="media-card" data-id="{{ $item->id }}" data-url="{{ $mediaUrl }}"
                            data-file-name="{{ $item->file_name }}" data-name="{{ $item->name }}"
                            data-size="{{ $item->size }}" data-mime="{{ $item->mime_type }}"
                            data-full-name="{{ $item->name }}" data-user-name="{{ $userName }}"
                            data-formatted-size="{{ $sizeKB }}">

                            {{-- Media Thumbnail --}}
                            @if ($isVideo)
                                <video src="{{ $mediaUrl }}" class="media-thumb" controls muted
                                    preload="metadata"></video>
                            @elseif ($isPdf)
                                <img src="{{ asset('assets/media/images/default-pdf.png') }}" class="media-thumb"
                                    alt="PDF file">
                            @else
                                <img src="{{ $mediaUrl }}" class="media-thumb" loading="lazy"
                                    alt="{{ $item->name }}">
                            @endif

                            <div class="media-info">
                                <div class="name">{{ $item->name }}</div>
                                <div class="size">{{ $sizeKB }}</div>
                            </div>

                            <div class="user-info">
                                User: {{ $userName }}
                            </div>

                            {{-- New: Open in New Tab Button --}}
                            <a href="{{ $mediaUrl }}" target="_blank" class="open-media-btn" title="Open in New Tab">
                                <i class="ki-filled ki-paper-plane"></i>
                            </a>

                            <div class="overlay-check"><i class="ki-filled ki-check-circle"></i></div>
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

        {{-- Tooltip Container --}}
        <div id="mediaTooltip" class="media-tooltip d-none"></div>

    </main>
@endsection

@push('styles')
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 0;
        }

        .media-card {
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 2px;
            background: white;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 220px;
        }

        .media-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .media-card.selected {
            border-color: #3b82f6;
            background-color: #f0f9ff;
        }

        .media-thumb {
            width: 100%;
            height: 120px;
            object-fit: contain;
            border-radius: 0.375rem;
            background: #f9fafb;
        }

        .media-info {
            min-height: 3rem;
        }

        .media-info .name {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .media-info .size {
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Renamed and restyled for clarity */
        .user-info {
            font-size: 0.75rem;
            font-weight: 400;
            color: #4b5563;
            /* Darker for better visibility */
            /* margin-top: 0.5rem; */
            padding-left: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .overlay-check {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 1.5rem;
            height: 1.5rem;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.2s ease-in-out;
            z-index: 10;
            /* Ensure it's above the button if they overlap */
        }

        .media-card.selected .overlay-check {
            opacity: 1;
            transform: scale(1);
        }

        /* New styles for the Open in New Tab button */
        .open-media-btn {
            position: absolute;
            bottom: 0.95rem;
            right: 0.5rem;
            color: #4f46e5;
            /* Indigo color for the icon */
            font-size: 1rem;
            padding: 0.25rem;
            line-height: 1;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            z-index: 5;
            /* Below the overlay-check */
        }

        .open-media-btn:hover {
            color: #3730a3;
            transform: scale(1.1);
        }

        /* --- Updated Tooltip Styles --- */
        .media-tooltip {
            position: absolute;
            z-index: 999;
            /* Dark, semi-transparent background */
            background-color: rgba(30, 41, 59, 0.95);
            color: #e5e7eb;
            /* Light gray text */
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            max-width: 300px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
            transform: translateY(5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -4px rgba(0, 0, 0, 0.15);
            line-height: 1.4;
            display: flex;
            flex-direction: column;
        }

        .media-tooltip.show {
            opacity: 1;
            transform: translateY(0);
        }

        .media-tooltip .tooltip-item {
            display: flex;
            margin-bottom: 0.4rem;
        }

        .media-tooltip .tooltip-item:last-child {
            margin-bottom: 0;
        }

        .media-tooltip .label {
            font-weight: 400;
            color: #9ca3af;
            /* Muted label color */
            margin-right: 0.5rem;
            flex-shrink: 0;
            /* Prevent label from shrinking */
            min-width: 60px;
        }

        .media-tooltip .value {
            font-weight: 500;
            color: #ffffff;
            word-break: break-word;
            /* Allow long filenames to wrap */
        }

        /* --- End Tooltip Styles --- */


        /* ... Drag and drop styles remain the same ... */
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

        /* Responsive adjustments */
        @media (max-width: 640px) {
            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .media-card {
                min-height: 200px;
                padding: 0.5rem;
            }

            .media-thumb {
                height: 100px;
            }

            .open-media-btn {
                font-size: 0.9rem;
            }
        }
    </style>
@endpush

{{-- prettier-ignore-start --}}
@push('scripts')
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
                $loadMoreEnd = $('#loadMoreEnd'),
                $mediaTooltip = $('#mediaTooltip'); // Tooltip element

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

            // Function to format bytes to KB
            function formatBytesToKB(bytes) {
                return (bytes / 1024).toFixed(1) + ' KB';
            }

            function renderCard(media) {
                const mediaUrl = media.url ?? '#';  
                const isVideo = media.mime_type && media.mime_type.startsWith('video');
                const isPdf = media.mime_type === 'application/pdf';
                // Lazyload response for user object is simple
                const userName = media.user ? (media.user.business_name || (media.user.first_name + ' ' + media.user
                        .last_name)) :
                    'Unknown User';
                const formattedSize = formatBytesToKB(media.size);
                let thumbHtml = '';

                if (isVideo) {
                    thumbHtml =
                        `<video src="${mediaUrl}" class="media-thumb" controls muted preload="metadata"></video>`;
                } else if (isPdf) {
                    // Use a placeholder image for PDF
                    thumbHtml =
                        `<img src="/assets/media/images/default-pdf.png" class="media-thumb" alt="PDF File">`;
                } else {
                    thumbHtml =
                        `<img src="${mediaUrl}" class="media-thumb" loading="lazy" alt="${media.name}">`;
                }

                return `
                <div class="media-card" 
                    data-id="${media.id}"
                    data-url="${mediaUrl}"
                    data-name="${media.name}"
                    data-size="${media.size}"
                    data-mime="${media.mime_type}"
                    data-full-name="${media.name}"
                    data-user-name="${userName}"
                    data-formatted-size="${formattedSize}">
                    ${thumbHtml}
                    <div class="media-info">
                        <div class="name">${media.name}</div>
                        <div class="size">${formattedSize}</div>
                    </div>
                    <div class="user-info">
                        User: ${userName}
                    </div>
                    
                    <a href="${mediaUrl}" target="_blank" class="open-media-btn" title="Open in New Tab">
                        <i class="ki-filled ki-paper-plane"></i>
                    </a>
                    
                    <div class="overlay-check"><i class="ki-filled ki-check-circle"></i></div>
                </div>`;
            }

            function appendCards(list) {
                const fragment = document.createDocumentFragment();

                list.forEach(media => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = renderCard(media).trim();
                    fragment.appendChild(tempDiv.firstChild);
                });

                $mediaGrid[0].appendChild(fragment);
            }

            async function loadMoreMedia() {
                if (loading || noMoreMedia) return;
                loading = true;
                attemptedLoad = true;

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

                    if (received < limit) {
                        noMoreMedia = true;
                        $loadMoreBtn.hide();
                        $loadMoreEnd.removeClass('d-none');
                    } else {
                        $loadMoreBtn.prop('disabled', false);
                    }
                } catch (err) {
                    console.error('lazyLoad error', err);
                    Swal.fire('Error', 'Failed to load more media. Try again.');
                    $loadMoreBtn.prop('disabled', false);
                } finally {
                    loading = false;
                    $loadMoreSpinner.addClass('d-none');
                }
            }

            // Load more button event
            $loadMoreBtn.on('click', loadMoreMedia);

            // Selection management
            function updateDeleteButton() {
                $deleteBtn.toggleClass('d-none', selected.length === 0);
            }

            // Modified click handler to prevent selection when clicking the new button
            $(document).on('click', '.media-card', function(e) {
                // If the click originated from the 'open-media-btn' or its icon, do nothing (let the anchor link handle it)
                if ($(e.target).closest('.open-media-btn').length) {
                    return;
                }

                const $card = $(this);
                const id = $card.data('id');

                if ($card.hasClass('selected')) {
                    selected = selected.filter(i => i !== id);
                    $card.removeClass('selected');
                } else {
                    selected.push(id);
                    $card.addClass('selected');
                }
                updateDeleteButton();
            });


            // Delete selected media
            $deleteBtn.on('click', function() {
                if (!selected.length) return;

                Swal.fire({
                    title: 'Delete selected?',
                    text: `${selected.length} file(s) will be permanently removed.`,
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

            // Upload functionality
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
                                    // Ensure user data is available for renderCard
                                    if (!m.user && m.user_id) {
                                        // This is a common issue: media model includes user_id but not the user object.
                                        // You might need to update your upload controller to eagerly load the user.
                                        // For now, setting a placeholder if it's not present:
                                        m.user = {
                                            business_name: 'Recently Uploaded'
                                        };
                                    }

                                    // Prepend new cards using the same rendering method
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = renderCard(m).trim();
                                    $mediaGrid.prepend(tempDiv.firstChild);
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

            // Drag and drop functionality
            function setupDragAndDrop() {
                const $body = $('body');

                $(document).on('dragover', function(e) {
                    e.preventDefault();
                    if (!e.originalEvent.dataTransfer.types.includes('Files')) return;
                    $body.addClass('dragging');
                });

                $(document).on('dragleave', function(e) {
                    if (e.originalEvent.clientX <= 0 || e.originalEvent.clientY <= 0 ||
                        e.originalEvent.clientX >= window.innerWidth || e.originalEvent.clientY >= window
                        .innerHeight) {
                        $body.removeClass('dragging');
                    }
                });

                $(document).on('drop', function(e) {
                    e.preventDefault();
                    $body.removeClass('dragging');

                    const files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0) {
                        uploadFiles(files);
                    }
                });

                // Prevent default behavior for drag events
                $(document).on('dragenter dragover drop', function(e) {
                    e.preventDefault();
                });
            }

            setupDragAndDrop();

            // --- Tooltip functionality ---

            // Hide tooltip when mouse leaves the grid
            $mediaGrid.on('mouseleave', function() {
                $mediaTooltip.removeClass('show').addClass('d-none');
            });

            // Show tooltip when mouse hovers over a media card
            $mediaGrid.on('mouseenter', '.media-card', function(e) {
                const $card = $(this);
                const name = $card.data('fullName');
                const user = $card.data('userName');
                const size = $card.data('formattedSize');

                const content = `
                    <div class="tooltip-item"><span class="label">File Name:</span> <span class="value">${name}</span></div>
                    <div class="tooltip-item"><span class="label">User:</span> <span class="value">${user}</span></div>
                    <div class="tooltip-item"><span class="label">Size:</span> <span class="value">${size}</span></div>
                `;

                $mediaTooltip.html(content);
                $mediaTooltip.removeClass('d-none');

                // Update position on mousemove
                $mediaGrid.on('mousemove.tooltip', '.media-card', function(e) {
                    let top = e.pageY + 10;
                    let left = e.pageX + 10;

                    // Check if tooltip goes off the right edge of the viewport
                    if (left + $mediaTooltip.outerWidth() > $(window).width() - 10) {
                        left = e.pageX - $mediaTooltip.outerWidth() - 10;
                    }

                    // Check if tooltip goes off the bottom edge of the viewport
                    if (top + $mediaTooltip.outerHeight() > $(window).height() + $(window)
                        .scrollTop() - 10) {
                        top = e.pageY - $mediaTooltip.outerHeight() - 10;
                    }

                    $mediaTooltip.css({
                        top: top,
                        left: left
                    });
                    $mediaTooltip.addClass('show'); // Show with transition
                });
            });

            // Hide tooltip when mouse leaves a media card
            $mediaGrid.on('mouseleave', '.media-card', function() {
                $mediaGrid.off('mousemove.tooltip');
                $mediaTooltip.removeClass('show').addClass('d-none');
            });

            // --- End Tooltip functionality ---
        });
    </script>
@endpush
{{-- prettier-ignore-end --}}
