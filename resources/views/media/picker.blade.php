<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Picker</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f7f9fc;
            font-family: 'Segoe UI', sans-serif;
        }

        h2 {
            font-weight: 600;
            color: #333;
        }

        .media-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.2s ease;
            cursor: pointer;
            background-color: #fff;
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
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 15px;
        }

        .media-card.selected {
            border: 2px solid #28a745;
        }

        .media-card .overlay-check {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            padding: 4px;
            font-size: 14px;
            display: none;
        }

        .media-card.selected .overlay-check {
            display: block;
        }

        #fileInput {
            display: none;
        }

        .modal-content {
            border-radius: 12px;
            overflow: hidden;
            border: none;
        }

        .modal-header {
            background-color: #f1f4f9;
            border-bottom: none;
        }

        .modal-footer {
            border-top: none;
        }

        .delete-selected-btn {
            background-color: #dc3545;
            border: none;
        }

        .delete-selected-btn.show {
            display: inline-block;
        }

        /* Preview Styling */
        .preview-card {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
            padding: 10px;
        }

        .preview-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
        }

        .preview-info {
            font-size: 14px;
        }

        .preview-info .name {
            font-weight: 500;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-info .size {
            font-size: 12px;
            color: #888;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <h4 class="mb-4">Select Media</h4>

        <!-- Media Input Field -->
        <div class="form-group">
            <label for="mediaInput">Select Image</label>
            <div class="input-group">
                <input type="text" id="mediaInput" class="form-control" placeholder="Click to select media" readonly>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-primary" data-toggle="modal"
                        data-target="#mediaModal">
                        <i class="fas fa-folder-open"></i> Browse
                    </button>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="mt-3 d-none" id="previewCard">
                <div class="media-card" style="width: 160px;">
                    <img id="previewImage" class="media-thumb" src="" alt="Preview">
                    <div class="media-info">
                        <div id="previewName" class="name"></div>
                        <div id="previewSize" class="size"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Media Modal -->
    <div class="modal fade" id="mediaModal" tabindex="-1" role="dialog" aria-labelledby="mediaModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaModalLabel">Select Media</h5>
                    <button class="btn btn-primary upload-btn" onclick="$('#fileInput').click()">Upload File</button>
                    <input type="file" id="fileInput" accept="image/*">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">

                    <div class="media-grid" id="mediaModalGrid">
                        @foreach ($media as $item)
                            <div class="media-card position-relative" data-id="{{ $item->id }}"
                                data-url="{{ asset('storage/media/' . $item->file_name) }}">
                                <img src="{{ asset('storage/media/' . $item->file_name) }}" class="media-thumb"
                                    alt="media">
                                <div class="media-info">
                                    <div class="name">{{ $item->name }}</div>
                                    <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                                </div>
                                <div class="overlay-check"><i class="ki-filled ki-check-circle"></i></div>
                            </div>
                        @endforeach

                    </div>

                    <div id="loadingSpinner" class="text-center d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            let offset = 0;
            let limit = 18;
            let isLoading = false;

            // Open Media Modal
            $('#mediaInput').on('click', function() {
                $('#mediaModal').modal('show');
                loadMoreMedia(); // Initial load when modal opens
            });

            // Media Card Selection
            $(document).on('click', '.media-card', function() {
                $('.media-card').removeClass('selected');
                $(this).addClass('selected');

                const imageUrl = $(this).data('url');
                const imageName = $(this).find('.name').text();
                const imageSize = $(this).find('.size').text();

                // Update preview card
                $('#previewImage').attr('src', imageUrl);
                $('#previewName').text(imageName);
                $('#previewSize').text(imageSize);
                $('#previewCard').removeClass('d-none');

                // Remove /storage/media/ from input value
                const displayName = imageUrl.replace('/storage/media/', '');
                $('#mediaInput').val(imageName);

                $('#mediaModal').modal('hide');
            });

            // Render media card
            function renderCard(media) {
                return `
                <div class="media-card position-relative" data-id="${media.id}" data-url="/storage/media/${media.file_name}">
                    <img src="/storage/media/${media.file_name}" class="media-thumb" alt="media">
                    <div class="media-info">
                        <div class="name">${media.name}</div>
                        <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                    </div>
                    <div class="overlay-check"><i class="ki-filled ki-check-circle"></i></div>
                </div>
            `;
            }

            // Upload Media
            $('#fileInput').on('change', function(e) {
                let file = e.target.files[0];
                if (!file) return;

                let formData = new FormData();
                formData.append('files[]', file); // Correct field name
                formData.append('_token', "{{ csrf_token() }}");

                $('#loadingSpinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('media.upload') }}",
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#loadingSpinner').addClass('d-none');

                        if (response.success) {
                            response.media.forEach(media => {
                                const cardHtml = renderCard(media);
                                $('#mediaModalGrid').prepend(cardHtml);
                            });

                            Swal.fire('Uploaded!', response.message ||
                                'File uploaded successfully.', 'success');
                        } else {
                            Swal.fire('Error', response.message || 'Upload failed.', 'error');
                        }
                    },
                    error: () => {
                        $('#loadingSpinner').addClass('d-none');
                        Swal.fire('Error', 'Upload failed.', 'error');
                    }
                });
            });

            // Lazy Loading on scroll
            $('#mediaModal .modal-body').on('scroll', function() {
                if (isLoading) return;

                const bottom = $(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight;
                if (bottom) {
                    isLoading = true;
                    loadMoreMedia();
                }
            });

            // Load more media (Lazy Loading)
            function loadMoreMedia() {
                $.ajax({
                    url: "{{ route('media.lazyLoad') }}",
                    method: 'GET',
                    data: {
                        offset: offset,
                        limit: limit
                    },
                    success: function(response) {
                        if (response.media.length > 0) {
                            offset += limit;

                            response.media.forEach(media => {
                                const cardHtml = renderCard(media);
                                $('#mediaModalGrid').append(cardHtml);
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
    </script>
