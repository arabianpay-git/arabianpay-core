<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        /* .upload-btn {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .upload-btn:hover {
            background-color: #0056b3;
        } */

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

    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Media Manager</h2>

        <div class="left-side">
            <!-- Delete Selected -->
            <button class="btn btn-danger delete-selected-btn" id="deleteSelectedBtn" style="display: none;">Delete Selected</button>
            <button class="btn btn-primary upload-btn" onclick="$('#fileInput').click()">Upload File</button>
            <input type="file" id="fileInput" accept="image/*" multiple>
        </div>
    </div>

    <!-- Media Grid -->
    <div class="media-grid" id="mediaGrid">
        @foreach ($media as $item)
            <div class="media-card position-relative" data-id="{{ $item->id }}">
                <img src="{{ asset('storage/media/' . $item->file_name) }}" class="media-thumb" alt="media">
                <div class="media-info">
                    <div class="name">{{ $item->name }}</div>
                    <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                </div>
                <div class="overlay-check"><i class="fas fa-check"></i></div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Media Gallery</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="media-grid" id="mediaModalGrid">
                    @foreach ($media as $item)
                        <div class="media-card position-relative" data-id="{{ $item->id }}">
                            <img src="{{ asset('storage/media/' . $item->file_name) }}" class="media-thumb" alt="media">
                            <div class="media-info">
                                <div class="name">{{ $item->name }}</div>
                                <div class="size">{{ number_format($item->size / 1024, 1) }} KB</div>
                            </div>
                            <div class="overlay-check"><i class="fas fa-check"></i></div>
                        </div>
                    @endforeach
                </div>
                <div class="text-center my-4" id="lazyLoadSpinner" style="display: none;">
                    <div class="spinner-border text-primary"></div>
                </div>
                
            </div>
            <div class="modal-footer">
                <button class="upload-btn" onclick="$('#fileInput').click()">Upload More</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(function () {
        let selected = [];
        let offset = 18; // load 18 initially
        let loading = false;
        let noMoreMedia = false;
    
        function updateDeleteButton() {
            $('#deleteSelectedBtn').toggle(selected.length > 0);
        }
    
        function toggleCardSelection(card) {
            const id = $(card).data('id');
            if ($(card).hasClass('selected')) {
                selected = selected.filter(e => e !== id);
                $(card).removeClass('selected');
            } else {
                selected.push(id);
                $(card).addClass('selected');
            }
            updateDeleteButton();
        }
    
        $(document).on('click', '.media-card', function () {
            toggleCardSelection(this);
        });
    
        $('#deleteSelectedBtn').on('click', function () {
            if (selected.length === 0) return;
    
            Swal.fire({
                title: 'Delete selected?',
                text: 'These files will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Delete'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('media.bulkDelete') }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            ids: selected
                        },
                        success: function (res) {
                            selected.forEach(id => {
                                $('.media-card[data-id="' + id + '"]').remove();
                            });
                            selected = [];
                            updateDeleteButton();
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: () => {
                            Swal.fire('Error', 'Failed to delete media.', 'error');
                        }
                    });
                }
            });
        });
    
        function renderCard(media) {
            return `
                <div class="media-card position-relative" data-id="${media.id}" data-url="${media.url}" data-name="${media.name}" data-size="${media.size}">
                    <img src="/storage/media/${media.file_name}" class="media-thumb lazy-img" loading="lazy" alt="media">
                    <div class="media-info">
                        <div class="name">${media.name}</div>
                        <div class="size">${(media.size / 1024).toFixed(1)} KB</div>
                    </div>
                    <div class="overlay-check"><i class="fas fa-check"></i></div>
                </div>
            `;
        }
    
        function loadMoreMedia() {
            if (loading || noMoreMedia) return;
            loading = true;
            $('#lazyLoadSpinner').show();
    
            $.get("{{ route('media.lazyLoad') }}", { offset }, function (res) {
                if (res.media.length === 0) {
                    noMoreMedia = true;
                    $('#lazyLoadSpinner').hide();
                    return;
                }
    
                res.media.forEach(media => {
                    const cardHtml = renderCard(media);
                    $('#mediaGrid').append(cardHtml);
                    if ($('#mediaModalGrid').length) {
                        $('#mediaModalGrid').append(cardHtml);
                    }
                });
    
                offset += res.media.length;
                loading = false;
                $('#lazyLoadSpinner').hide();
            });
        }
    
        $(window).on('scroll', function () {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 300) {
                loadMoreMedia();
            }
        });
    
        $('#fileInput').on('change', function (e) {
            let files = e.target.files;
            if (!files.length) return;
    
            let formData = new FormData();
            for (let i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            formData.append('_token', "{{ csrf_token() }}");
    
            $('#loadingSpinner').removeClass('d-none');
    
            $.ajax({
                url: "{{ route('media.upload') }}",
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    $('#loadingSpinner').addClass('d-none');
    
                    if (response.success) {
                        response.media.forEach(function (media) {
                            let cardHtml = renderCard(media);
                            $('#mediaGrid').prepend(cardHtml);
                            if ($('#mediaModalGrid').length) {
                                $('#mediaModalGrid').prepend(cardHtml);
                            }
                        });
                        Swal.fire('Uploaded!', response.message || 'Files uploaded successfully.', 'success');
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
    });
    </script>
    