@php
    // Round floor to near value
    $scoreRounded = floor($creditScore / 10) * 10;

    // Max is 95 (last image)
    if ($scoreRounded > 95) {
        $scoreRounded = 95;
    }
@endphp

<div class="flex items-start gap-4">
    <!-- Fahman Image according to score -->
    <div class="shrink-0">
        <img src="{{ asset('assets/media/fahman/' . $fahman['type'] . '.png') }}" alt="Fahman" class="rounded-full w-24 h-80">
    </div>

    <!-- Content of evaluation -->
    <div class="flex-1">
        <!-- Image for pointer and Score-->
        <div class="flex items-center justify-between mb-3">
            <img src="{{ asset('assets/media/pointer/' . $scoreRounded . '.png') }}" alt="Score Pointer" class="h-12">
            <div class="text-3xl font-bold text-gray-800">
                <strong class="text-{{ $fahman['type'] }}" style="font-size: larger">{{ $creditScore }}</strong>
            </div>
        </div>

        <!-- Text -->
        <div class="alert alert-{{ $fahman['type'] }} text-left" role="alert">
            <strong>{{ $fahman['title'] }}</strong><br>
            <span class="text-sm text-gray-600">{{ $fahman['message'] }}</span>
            <br>
            <button type="button" class="btn btn-sm btn-primary mt-2 float-right"
                data-modal-dismiss="true"
                    onclick="showDetails({{ $customer->id }})">
                Why? View more
            </button>
        </div>
    </div>
</div>

    
