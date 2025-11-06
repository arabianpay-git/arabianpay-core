<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi Media Picker</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

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
</head>
<body>

<div class="container py-5">
    <h4 class="mb-4">Select Multiple Images</h4>

    <div class="form-group">
        <label>Select Images</label>
        <div class="input-group">
            <input type="text" id="multiMediaInput" class="form-control" placeholder="Click to select media" readonly>
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#mediaModal">
                    <i class="fas fa-folder-open"></i> Browse
                </button>
            </div>
        </div>

        <div class="mt-3 d-flex flex-wrap" id="multiPreviewContainer"></div>
    </div>
</div>

<!-- Media Modal -->
<div class="modal fade" id="mediaModal" tabindex="-1" role="dialog" aria-labelledby="mediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title">Select Media</h5>
                <button class="btn btn-primary" onclick="$('#fileInput').click()">Upload File</button>
                <input type="file" id="fileInput" accept="image/*" multiple hidden>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="media-grid" id="mediaModalGrid">
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
            <div class="modal-footer border-0">
                <button class="btn btn-primary" id="confirmSelection">Select</button>
                <button class="btn btn-light" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- CHANGES: File input is now multiple, JS updated for multiple uploads -->
<script>
    let selectedMedia = [];
    let offset = {{ $media->count() }};
    let limit = 18;
    let isLoading = false;
    
    $(document).ready(function () {
        // Media Card Click
        $(document).on('click', '.media-card', function () {
            const id = $(this).data('id');
            const url = $(this).data('url');
            const name = $(this).data('name');
            const size = $(this).data('size');
    
            const alreadySelected = selectedMedia.find(item => item.id === id);
    
            if (alreadySelected) {
                selectedMedia = selectedMedia.filter(item => item.id !== id);
                $(this).removeClass('selected');
            } else {
                selectedMedia.push({ id, url, name, size });
                $(this).addClass('selected');
            }
        });
    
        // Confirm Selection
        $('#confirmSelection').on('click', function () {
            $('#multiPreviewContainer').html('');
            selectedMedia.forEach(media => {
                $('#multiPreviewContainer').append(`
                    <div class="preview-card" data-id="${media.id}">
                        <div class="media-card">
                            <img src="${media.url}" class="media-thumb" alt="Preview">
                            <div class="media-info">
                                <div class="name">${media.name}</div>
                                <div class="size">${media.size}</div>
                            </div>
                            <button class="remove-btn" data-id="${media.id}">&times;</button>
                        </div>
                    </div>
                `);
            });
            $('#multiMediaInput').val(selectedMedia.map(item => item.name).join(', '));
            $('#mediaModal').modal('hide');
        });
    
        // Remove Preview
        $(document).on('click', '.remove-btn', function () {
            const id = $(this).data('id');
            selectedMedia = selectedMedia.filter(item => item.id !== id);
            $(`.preview-card[data-id="${id}"]`).remove();
            $(`.media-card[data-id="${id}"]`).removeClass('selected');
            $('#multiMediaInput').val(selectedMedia.map(item => item.name).join(', '));
        });
    
        // Upload Multiple Files
        // Upload Multiple Files
$('#fileInput').on('change', function (e) {
    const files = e.target.files;
    if (!files.length) return;

    $('#loadingSpinner').removeClass('d-none');

    const formData = new FormData();
    Array.from(files).forEach(file => {
        formData.append('files[]', file);
    });
    formData.append('_token', "{{ csrf_token() }}");

    $.ajax({
        url: "{{ route('media.upload') }}",
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            $('#loadingSpinner').addClass('d-none');

            if (response.success && response.media.length > 0) {
                response.media.forEach(media => {
                    const isSelected = selectedMedia.find(item => item.id == media.id) ? 'selected' : '';
                    $('#mediaModalGrid').prepend(`
                        <div class="media-card ${isSelected}"
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

                Swal.fire('Uploaded!', 'All files uploaded successfully.', 'success');
            } else {
                Swal.fire('Error', response.message || 'Upload failed.', 'error');
            }
        },
        error: function (xhr) {
            $('#loadingSpinner').addClass('d-none');
            const res = xhr.responseJSON;
            if (res && res.errors && res.errors.files) {
                Swal.fire('Error', res.errors.files[0], 'error');
            } else {
                Swal.fire('Error', 'Upload failed.', 'error');
            }
        }
    });
});

    
        // Lazy Load Media
        $('#mediaModal .modal-body').on('scroll', function () {
            if (isLoading) return;
    
            const container = $(this);
            const bottom = container.scrollTop() + container.innerHeight() >= this.scrollHeight - 100;
    
            if (bottom) {
                isLoading = true;
                $.ajax({
                    url: "{{ route('media.lazyLoad') }}",
                    method: 'GET',
                    data: { offset },
                    success: function (response) {
                        if (response.media.length > 0) {
                            offset += limit;
                            response.media.forEach(item => {
                                const isSelected = selectedMedia.find(m => m.id === item.id) ? 'selected' : '';
                                $('#mediaModalGrid').append(`
                                    <div class="media-card ${isSelected}"
                                        data-id="${item.id}"
                                        data-url="/storage/media/${item.file_name}"
                                        data-name="${item.name}"
                                        data-size="${(item.size / 1024).toFixed(1)} KB">
                                        <img src="/storage/media/${item.file_name}" class="media-thumb" alt="media">
                                        <div class="media-info">
                                            <div class="name">${item.name}</div>
                                            <div class="size">${(item.size / 1024).toFixed(1)} KB</div>
                                        </div>
                                        <div class="overlay-check"><i class="fas fa-check"></i></div>
                                    </div>
                                `);
                            });
                        }
                        isLoading = false;
                    },
                    error: function () {
                        isLoading = false;
                        Swal.fire('Error', 'Failed to load more media.', 'error');
                    }
                });
            }
        });
    
        // When modal is reopened, reselect previously selected items
        $('#mediaModal').on('shown.bs.modal', function () {
            $('.media-card').each(function () {
                const id = $(this).data('id');
                if (selectedMedia.find(item => item.id === id)) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        });
    });
    </script>
    