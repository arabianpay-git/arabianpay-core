@extends('layouts.base')

@section('content')
    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="card max-w-[370px] w-full">
            <form action="{{ route('otp.verify.confirm') }}" method="POST" class="card-body flex flex-col gap-5 p-10"
                id="verify_form">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}" />
                <input type="hidden" name="code" id="full_otp_code" />

                <div class="text-center mb-2.5">
                    <h3 class="text-lg font-medium text-gray-900">Enter OTP Code</h3>
                    <p class="text-sm text-gray-500">A 6-digit code was sent to <strong>{{ $phone }}</strong></p>
                </div>

                @if ($errors->any())
                    <div class="grid gap-5 lg:gap-7.5 mb-5">
                        <div class="card rounded-xl">
                            <div
                                class="flex items-center flex-wrap sm:flex-wrap justify-between grow gap-2 p-5 rtl:[background-position:-30%_41%] [background-position:121%_41%] bg-no-repeat bg-[length:660px_310px] upgrade-bg">
                                <div class="flex items-center gap-4">
                                    <div class="relative size-[50px] shrink-0">
                                        <svg class="w-full h-full stroke-brand-clarity fill-brand-light" fill="none"
                                            height="48" viewBox="0 0 44 48" width="44"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M16 2.4641C19.7128 0.320509 24.2872 0.320508 28 2.4641L37.6506 8.0359C41.3634 10.1795 43.6506 14.141 43.6506
                                                            18.4282V29.5718C43.6506 33.859 41.3634 37.8205 37.6506 39.9641L28 45.5359C24.2872 47.6795 19.7128 47.6795 16 45.5359L6.34937
                                                            39.9641C2.63655 37.8205 0.349365 33.859 0.349365 29.5718V18.4282C0.349365 14.141 2.63655 10.1795 6.34937 8.0359L16 2.4641Z"
                                                fill=""></path>
                                            <path
                                                d="M16.25 2.89711C19.8081 0.842838 24.1919 0.842837 27.75 2.89711L37.4006 8.46891C40.9587 10.5232 43.1506 14.3196 43.1506
                                                            18.4282V29.5718C43.1506 33.6804 40.9587 37.4768 37.4006 39.5311L27.75 45.1029C24.1919 47.1572 19.8081 47.1572 16.25 45.1029L6.59937
                                                            39.5311C3.04125 37.4768 0.849365 33.6803 0.849365 29.5718V18.4282C0.849365 14.3196 3.04125 10.5232 6.59937 8.46891L16.25 2.89711Z"
                                                stroke=""></path>
                                        </svg>
                                        <div
                                            class="absolute leading-none start-2/4 top-2/4 -translate-y-2/4 -translate-x-2/4 rtl:translate-x-2/4">
                                            <i class="ki-filled ki-information-4 text-xl text-brand"> </i>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center flex-wrap gap-2.5">
                                            <a class="text-base font-medium text-gray-900 hover:text-primary-active"
                                                href="#">
                                                Please fix the following errors:
                                            </a>
                                        </div>
                                        <div class="text-2sm text-gray-800">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li class="text-danger">{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                {{-- OTP Inputs --}}
                <div class="flex justify-between gap-2">
                    @for ($i = 0; $i < 6; $i++)
                        <input id="otp-{{ $i }}" maxlength="1"
                            class="w-10 h-12 text-center text-xl border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:outline-none otp-input"
                            type="text" inputmode="numeric" pattern="\d*" required
                            @if ($i === 0) autofocus @endif>
                    @endfor
                </div>

                {{-- OTP Expiry --}}
                <div class="text-center mt-2 text-sm text-gray-500">
                    <span id="otp_expire_timer" class="font-medium">OTP expires in: --:--</span>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-primary w-full mt-4 flex justify-center items-center" id="submit_btn">
                    <span id="submit_text">Verify OTP</span>
                    <div class="hidden" id="spinner" class="spinner-border"></div>
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Auto-tab between OTP inputs
            const inputs = [...document.querySelectorAll('.otp-input')];
            inputs.forEach((el, i) => {
                el.addEventListener('input', () => {
                    if (/\d/.test(el.value) && i < inputs.length - 1) inputs[i + 1].focus();
                    else if (!/\d/.test(el.value)) el.value = '';
                });
                el.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !el.value && i > 0) inputs[i - 1].focus();
                });
            });

            // Check if all inputs are filled
            inputs.forEach(input => {
                input.addEventListener('input', checkAndSubmit);
            });

            function checkAndSubmit() {
                const allFilled = inputs.every(input => input.value.trim() !== "");

                if (allFilled) {
                    // Combine OTP code
                    const fullCode = inputs.map(i => i.value).join('');
                    document.getElementById('full_otp_code').value = fullCode;

                    // Disable button and show spinner
                    const submitButton = document.getElementById('submit_btn');
                    const submitText = document.getElementById('submit_text');
                    const spinner = document.getElementById('spinner');

                    submitButton.disabled = true;
                    submitText.classList.add('hidden');
                    spinner.classList.remove('hidden');

                    // Submit the form automatically
                    document.getElementById('verify_form').submit();
                }
            }

            // Countdown timer
            let expireLeft = {{ $secondsUntilExpire }};
            const EXPIRE_EL = document.getElementById('otp_expire_timer');

            function pad(n) {
                return n.toString().padStart(2, '0');
            }

            const tid = setInterval(() => {
                if (expireLeft > 0) {
                    const m = Math.floor(expireLeft / 60);
                    const s = Math.floor(expireLeft % 60);
                    EXPIRE_EL.textContent = `OTP expires in: ${m}:${pad(s)}`;
                    expireLeft--;
                } else {
                    EXPIRE_EL.textContent = '❌ OTP expired';
                    clearInterval(tid);
                    setTimeout(() => {
                        window.location.href = "{{ route('risk.score') }}";
                    }, 3000);
                }
            }, 1000);
        </script>
    @endpush
@endsection
