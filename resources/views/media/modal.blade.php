<div class="modal" data-modal="true" id="{{ $inputId }}_modal">
    <div class="modal-content max-w-[70%] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">Select Media</h5>
            <button type="button" class="btn btn-primary upload-btn" onclick="$('#{{ $inputId }}_fileInput').click()">Upload File</button>
            <input type="file" id="{{ $inputId }}_fileInput" accept="image/*" hidden>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-0 pb-5">
            <div class="media-grid" id="{{ $inputId }}_grid">
                @php $media = App\Models\Media::where('user_id', Auth::user()->id)->latest()->take(18)->get(); @endphp
                @foreach ($media as $item)
                    <div class="media-card position-relative"
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

            <div id="{{ $inputId }}_loadingSpinner" class="text-center d-none">
                <div class="spinner-border text-primary"></div>
            </div>
        </div>
    </div>
</div>