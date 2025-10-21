<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Scripts for Firebase Notification -->
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js"></script>
<script src="{{ asset('assets/js/firebase-notifications.js') }}"></script>


<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/js/widgets/general.js') }}"></script>
<script src="{{ asset('assets/js/layouts/demo1.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '{{ translate('Success') }}',
            text: "{{ session('success') }}",
            toast: true,
            position: 'bottom',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    </script>
@endif

@if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: '{{ translate('Error') }}',
            html: `{!! session('error') !!}`,
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    </script>
@endif


@if ($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: '{{ translate('Validation Errors!') }}',
            html: `{!! implode('<br>', $errors->all()) !!}`,
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    </script>
@endif


<!-- Tabs JS -->
<script>
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));

            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');
        });
    });
</script>

<script>
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();

        const url = $(this).attr('href');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a hidden form dynamically
                const form = $('<form>', {
                    method: 'POST',
                    action: url
                });

                // Add CSRF token input
                const token = $('meta[name="csrf-token"]').attr('content');
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: token
                }));

                // Add _method input to spoof DELETE
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_method',
                    value: 'DELETE'
                }));

                // Append form to body and submit
                form.appendTo('body').submit();
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>


@stack('scripts')
