@foreach ($media as $mediaItem)
    @include('media._media_card', ['media' => $mediaItem])
@endforeach
