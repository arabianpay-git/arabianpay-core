<div class="flex items-start gap-4">
    <!-- Content of evaluation -->
    <div class="flex-1">
        <!-- Breakdown of score components -->
        <div class="mt-4 p-3">
            <div class="flex items-center gap-2.5 mt-3" style="float: right;">
                <a class="btn btn-sm btn-light" data-modal-dismiss="true" href="#">
                Close
                </a>
            </div>
            <h5 class="text-lg font-semibold text-gray-800 mb-3">📊 Score Breakdown (Fahman Analysis)</h5>
            <ul class="list-group list-group-flush gap-2.5">
                @foreach($scoreComponents as $label => $value)
                    @php
                        $max = $scoreMaxValues[$label] ?? 25;
                        $avg = $value / $max * 100;
                        $message = match (true) {
                            $avg >= 90 => 'Excellent — this boosts Fahman’s confidence.',
                            $avg >= 75 => 'Good performance, but could still be improved.',
                            $avg >= 60 => 'Some instability detected — better to monitor closely.',
                            $avg >= 50  => 'Moderate risk — caution is advised.',
                            default      => 'Very weak — this raises serious concern.',
                        };

                        $color = match (true) {
                            $avg >= 90 => 'success',
                            $avg >= 75 => 'primary',
                            $avg >= 60 => 'warning',
                            $avg >= 50  => 'danger',
                            default      => 'dark',
                        };
                    @endphp
                <div class="card p-1.5 mt-1">
                    <div class="flex flex-wrap justify-between items-center gap-5">
                        <div class="flex items-center gap-4">
                            <div class="flex justify-center items-center size-8 shrink-0 rounded-full ring-1 ring-gray-300 bg-gray-100">
                                <i class="ki-filled ki-information-2 text-{{$color}}"></i>
                            </div>
                            <div class="flex justify-center items-center size-8 shrink-0 rounded-full bg-gray-100">
                                <span class="text-primary font-semibold">
                                {{ $value }}
                                </span>/
                                <span class="text-grey-700 font-semibold text-2sm">
                                {{ $max }}
                                </span>
                            </div>
                            <div class="grid grid-col gap-1">
                                <a class="text-base font-medium text-gray-900 hover:text-primary-active mb-px" href="#">
                                {{ $label }}
                                </a>
                                <span class="text-2sm text-gray-700">
                                {{ $message }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>                    
                @endforeach
            </ul>
        </div>
    </div>
</div>