    @php

        $mediaUrl = getMediaUrl($media->file_name, asset('assets/media/images/default-image.png'));

        $isVideo = Illuminate\Support\Str::startsWith($media->mime_type, 'video');
        $isPdf = $media->mime_type === 'application/pdf';

        $pdfImage = asset('assets/media/images/default-pdf.png');
    @endphp

    <div class="media-card relative border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition cursor-pointer"
        data-id="{{ $media->id }}" data-url="{{ $mediaUrl }}" data-name="{{ $media->name }}"
        data-size="{{ $media->size }}" data-mime="{{ $media->mime_type }}" role="button" tabindex="0"
        aria-label="Select media {{ $media->name }}">
        @if ($isVideo)
            <video src="{{ $mediaUrl }}" class="media-thumb w-full object-cover max-h-[150px]" controls muted
                preload="metadata" loading="lazy" aria-label="Video preview of {{ $media->name }}">
                Sorry, your browser doesn't support embedded videos.
            </video>
        @elseif ($isPdf)
            <img src="{{ $pdfImage }}" class="media-thumb w-full object-contain max-h-[150px]"
                alt="PDF icon representing {{ $media->name }}" loading="lazy">
        @else
            <img src="{{ $mediaUrl }}" class="media-thumb w-full object-cover max-h-[150px]"
                alt="Image preview of {{ $media->name }}" loading="lazy">
        @endif

        <div class="media-info p-2 bg-white border-t">
            <div class="name text-sm font-medium text-gray-700 truncate" title="{{ $media->name }}">
                {{ $media->name }}
            </div>
            <div class="size text-xs text-gray-500">
                {{ number_format($media->size / 1024, 1) }} KB
            </div>
        </div>

        <div class="overlay-check absolute top-2 right-2 bg-primary text-white rounded-full p-1 hidden">
            <i class="ki-filled ki-check-circle text-sm"></i>
        </div>
    </div>
