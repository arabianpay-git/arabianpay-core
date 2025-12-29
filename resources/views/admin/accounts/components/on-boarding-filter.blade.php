  <div class="relative" id="onboarding-dropdown">
      <!-- Hidden input to store the value -->
      <input type="hidden" id="filter-onboarding-step" name="onboarding_step" value="{{ request('onboarding_step', '') }}">

      <!-- Dropdown toggle button -->
      <button type="button" id="onboarding-dropdown-toggle"
          class="select select-sm flex items-center justify-between w-56 text-left" style="width: 14rem;">
          <span id="onboarding-selected-text">
              @php
                  $onboardingSteps = [
                      'basic-info' => '1. Basic Info',
                      'business-revenue' => '2. Business Revenue',
                      'business-verification' => '3. Business Verification',
                      'personal-details' => '4. Personal Details',
                      'bank-details' => '5. Bank Details',
                      'additional-details' => '6. Additional Details',
                  ];
                  $selectedStep = request('onboarding_step', '');
                  echo $selectedStep && isset($onboardingSteps[$selectedStep])
                      ? $onboardingSteps[$selectedStep]
                      : translate('All Onboarding Steps');
              @endphp
          </span>
          <i class="ki-solid ki-down ml-2"></i>
      </button>

      <!-- Dropdown menu -->
      <div id="onboarding-dropdown-menu"
          class="absolute z-50 hidden mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg">
          <div class="p-3">
              <div class="dropdown-option" data-value="">
                  <div class="font-medium text-gray-900">
                      {{ translate('All Onboarding Steps') }}</div>
                  <div class="text-xs text-gray-500 mt-1">
                      {{ translate('Show all suppliers regardless of onboarding progress') }}
                  </div>
              </div>

              <div class="border-t my-2"></div>

              @foreach ([
        'basic-info' => [
            'label' => translate('1. Basic Info'),
            'description' => translate('Users who registered but haven\'t provided business name'),
        ],
        'business-revenue' => [
            'label' => translate('2. Business Revenue'),
            'description' => translate('Users with business name but haven\'t provided revenue details'),
        ],
        'business-verification' => [
            'label' => translate('3. Business Verification'),
            'description' => translate('Users with business name & revenue but haven\'t completed business verification'),
        ],
        'personal-details' => [
            'label' => translate('4. Personal Details'),
            'description' => translate('Merchants with CR number but haven\'t completed Nafath verification'),
        ],
        'bank-details' => [
            'label' => translate('5. Bank Details'),
            'description' => translate('Merchants with approved Nafath but haven\'t added bank details'),
        ],
        'additional-details' => [
            'label' => translate('6. Additional Details'),
            'description' => translate('Merchants with bank details but missing required documents'),
        ],
    ] as $key => $data)
                  <div class="dropdown-option" data-value="{{ $key }}">
                      <div class="font-medium text-gray-900">{{ $data['label'] }}</div>
                      <div class="text-xs text-gray-500 mt-1">
                          {{ $data['description'] }}
                      </div>
                  </div>
              @endforeach
          </div>
      </div>
  </div>
