@if ($paginator->hasPages())
    <div class="card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-gray-600 text-2sm font-medium">

        <!-- Pagination Links -->
        <div class="flex items-center gap-4 order-1 md:order-2">
            <span>
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
            </span>

            <nav class="flex items-center gap-1">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-1 text-gray-400">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}{{ request()->has('perpage') ? '&perpage=' . request('perpage') : '' }}"
                       class="px-3 py-1 text-blue-600 hover:underline">Previous</a>
                @endif

                {{-- Pagination Number Links --}}
                @foreach ($paginator->linkCollection() as $element)
                    @php $label = strip_tags($element['label']); @endphp

                    @if (is_numeric($label))
                        @if ($element['active'])
                            <span class="px-3 py-1 bg-gray-300 text-gray-800 rounded shadow-sm">{{ $label }}</span>
                        @else
                            <a href="{{ $element['url'] }}{{ request()->has('perpage') ? '&perpage=' . request('perpage') : '' }}"
                               class="px-3 py-1 text-blue-600 hover:underline rounded">{{ $label }}</a>
                        @endif
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}{{ request()->has('perpage') ? '&perpage=' . request('perpage') : '' }}"
                       class="px-3 py-1 text-blue-600 hover:underline">Next</a>
                @else
                    <span class="px-3 py-1 text-gray-400">Next</span>
                @endif
            </nav>
        </div>
    </div>
@endif
