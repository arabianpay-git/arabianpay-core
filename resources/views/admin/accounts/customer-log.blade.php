@extends('layouts.base')

@section('content')
<main class="grow content pt-5" id="content" role="content">
    <!-- Container -->
    <div class="container-fixed" id="content_container"></div>
    <!-- End of Container -->
    <style>
        .hero-bg {
            background-image: url("{{ asset('assets/media/images/2600x1200/bg-1.png') }}");
        }
        .dark .hero-bg {
            background-image: url("{{ asset('assets/media/images/2600x1200/bg-1-dark.png') }}");
        }
    </style>
    <div class="bg-center bg-cover bg-no-repeat hero-bg">
        <!-- Container -->
        @include('admin.accounts.includes.customer')
        <!-- End of Container -->
    </div>
    <!-- Container -->
        @include('admin.accounts.includes.customer-header')
    <!-- End of Container -->
    <!-- Container -->
    <!-- Container -->
     <div class="container-fixed">
      <!-- begin: activity -->
      <div class="flex gap-5 lg:gap-7.5">
       <div class="card grow" id="activity_2024">
        <div class="card-header">
         <h3 class="card-title">
          Activity log
            <span class="text-muted text-sm">({{ $logs->total() }} records)</span>
         </h3>
        </div>
        <div class="card-body">
         <div class="flex flex-col">
          @foreach($logs as $log)
            <div class="flex items-start relative">
                <div class="w-9 start-0 top-9 absolute bottom-0 rtl:-translate-x-1/2 translate-x-1/2 border-s border-s-gray-300"></div>
                @php
                    switch ($log->event) {
                        case 'create':
                            $txtColor = 'text-primary';
                            $icon = 'ki-filled ki-add-folder';
                            break;
                        case 'view':
                            $txtColor = 'text-success';
                            $icon = 'ki-filled ki-eye';
                            break;
                        case 'update':
                            $txtColor = 'text-warning';
                            $icon = 'ki-filled ki-update-folder';
                            break;
                        case 'delete':
                            $txtColor = 'text-danger';
                            $icon = 'ki-filled ki-delete-folder';
                            break;
                        default:
                            $txtColor = 'text-gray-600';
                            $icon = 'ki-filled ki-update-folder';
                            break;
                    }
                @endphp
                <div class="flex items-center justify-center shrink-0 rounded-full bg-gray-100 border border-gray-300 size-9 text-gray-600">
                    <i class="ki-filled {{$icon}} {{$txtColor}}"></i>
                </div>

                <div class="ps-2.5 mb-7 text-md grow">
                    <div class="flex flex-col">
                        <div class="{{$txtColor}} font-semibold">
                            [{{ $log->event }}]
                            <p class="text-sm font-small text-gray-800 text-xs">
                                {{ $log->description }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-600">
                            {{ $log->created_at->diffForHumans() }} [{{$log->created_at}}] - {{ $log->causer->first_name ?? 'System' }}
                        </span>

                        <!-- Show details button -->
                        <button type="button"
                            class="text-blue-500 text-xs mt-1 hover:underline focus:outline-none text-left"
                            onclick="toggleDetails('{{ $log->id }}')">
                            Show details
                        </button>

                        <!-- Details (Hidden by default) -->
                        <div id="details-{{ $log->id }}" class="hidden mt-2 bg-gray-100 p-2 rounded text-xs font-mono whitespace-pre-wrap">
                            @if($log->properties)
                               @foreach($log->properties->toArray() as $key => $value)
                                    <div class="flex justify-between text-xs text-gray-700 border-b py-1">
                                        <span class="font-medium">{{ $key }}</span>
                                        <span>
                                            @if(is_array($value))
                                                {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
          @endforeach

          

         </div>
        </div>
        <div class="card-footer justify-center">
         {{ $logs->links()}}
        </div>
       </div>
       
      </div>
      <!-- end: activity -->
     </div>
     <!-- End of Container -->
    <!-- End of Container -->
</main>

<script>
    function toggleDetails(id) {
        const el = document.getElementById('details-' + id);
        el.classList.toggle('hidden');
    }
</script>
@endsection