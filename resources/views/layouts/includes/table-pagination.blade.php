@php
    if (! function_exists('appendQueryParams')) {
        function appendQueryParams($url)
        {
            $query = request()->except('page');
            if (count($query)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
            }
            return $url;
        }
    }
@endphp

@if ($paginator->hasPages())
    <div
        class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">

        <!-- Pagination Info -->
        <div class="flex items-center gap-4 order-1 md:order-2">
            <span>
                {{ translate('Showing') }} {{ $paginator->firstItem() }} {{ translate('to') }}
                {{ $paginator->lastItem() }} {{ translate('of') }} {{ $paginator->total() }} {{ translate('entries') }}
            </span>

            <nav class="flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-1 text-gray-400">{{ translate('Previous') }}</span>
                @else
                    <a href="{{ appendQueryParams($paginator->previousPageUrl()) }}{{ request()->has('perpage') ? '&perpage=' . request('perpage') : '' }}"
                        class="px-3 py-1 text-blue-600 hover:underline">{{ translate('Previous') }}</a>
                @endif

                {{-- Pagination Number Links --}}
                @foreach ($paginator->linkCollection() as $element)
                    @php $label = strip_tags($element['label']); @endphp

                    @if (is_numeric($label))
                        @if ($element['active'])
                            <span
                                class="px-3 py-1 bg-gray-300 text-gray-800 rounded shadow-sm">{{ $label }}</span>
                        @else
                            <a href="{{ appendQueryParams($element['url']) }}{{ request()->has('perpage') ? '&perpage=' . request('perpage') : '' }}"
                                class="px-3 py-1 text-blue-600 hover:underline rounded">{{ $label }}</a>
                        @endif
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ appendQueryParams($paginator->nextPageUrl()) }}{{ request()->has('perpage') ? '&perpage=' . request('perpage') : '' }}"
                        class="px-3 py-1 text-blue-600 hover:underline">{{ translate('Next') }}</a>
                @else
                    <span class="px-3 py-1 text-gray-400">{{ translate('Next') }}</span>
                @endif
            </nav>
        </div>
    </div>
@endif
