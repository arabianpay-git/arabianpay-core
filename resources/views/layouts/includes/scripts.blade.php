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

<!-- Lightweight modal toggler (Bootstrap-free) -->
<script>
    (function () {
        // Open modal when clicking elements with data-modal-toggle="#selector"
        document.addEventListener('click', function (e) {
            const toggle = e.target.closest('[data-modal-toggle]');
            if (!toggle) return;
            e.preventDefault();
            const targetSel = toggle.getAttribute('data-modal-toggle');
            if (!targetSel) return;
            const modal = document.querySelector(targetSel);
            if (!modal) return;

            // Show modal
            modal.style.display = 'block';
            modal.classList.add('show', 'open');
            modal.setAttribute('aria-modal', 'true');
            modal.removeAttribute('aria-hidden');

            // If this is the order details modal, kick off content loading (fallback path)
            if (targetSel === '#order_details_modal') {
                console.log('Modal toggle detected for order_details_modal');
                const id = toggle.getAttribute('data-model-id');
                const url = toggle.getAttribute('data-url');
                console.log('Order ID from toggle:', id, 'URL:', url);
                if (id) {
                    modal.setAttribute('data-order-id', id);
                    if (url) modal.setAttribute('data-url', url);
                    console.log('Checking if loadOrderDetailsIntoModal exists:', typeof window.loadOrderDetailsIntoModal);
                    if (typeof window.loadOrderDetailsIntoModal === 'function') {
                        console.log('Calling loadOrderDetailsIntoModal from modal toggle handler');
                        try {
                            window.loadOrderDetailsIntoModal(String(id), url || null);
                        } catch (e) {
                            console.error('Error calling loadOrderDetailsIntoModal:', e);
                        }
                    } else {
                        console.warn('loadOrderDetailsIntoModal function not found - it may not be defined yet');
                    }
                }
            }

            // Backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.position = 'fixed';
            backdrop.style.inset = '0';
            backdrop.style.backgroundColor = 'rgba(0,0,0,0.5)';
            document.body.appendChild(backdrop);
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';

            // Fire jQuery Bootstrap-like event if jQuery present
            if (typeof window.jQuery !== 'undefined') {
                window.jQuery(modal).trigger('show.bs.modal');
            }
        }, false);

        // Close modal for elements with [data-modal-dismiss]
        document.addEventListener('click', function (e) {
            const dismiss = e.target.closest('[data-modal-dismiss]');
            if (!dismiss) return;
            e.preventDefault();
            const modal = e.target.closest('.modal');
            if (!modal) return;
            closeModal(modal);
        }, false);

        // Close when clicking backdrop area if modal structure allows
        document.addEventListener('mousedown', function (e) {
            const modal = e.target.classList && e.target.classList.contains('modal') ? e.target : null;
            if (modal) {
                closeModal(modal);
            }
        }, false);

        function closeModal(modal) {
            modal.classList.remove('show', 'open');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            // Fire hide event if jQuery present
            if (typeof window.jQuery !== 'undefined') {
                window.jQuery(modal).trigger('hide.bs.modal');
            }
        }
    })();
    </script>

@if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: '{{ translate('Error') }}',
            text: "{{ session('error') }}",
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


<script>
    // Ensure order details load when the modal "show" event is fired
    // (our toggler triggers a Bootstrap-like event for compatibility)
    (function() {
        if (typeof window !== 'undefined') {
            document.addEventListener('show.bs.modal', function (e) {
                const modal = e.target || e.detail || null;
                const el = modal && modal.id === 'order_details_modal' ? modal : null;
                if (!el) return;
                try {
                    const id = el.getAttribute('data-order-id');
                    const url = el.getAttribute('data-url');
                    if (id && typeof window.loadOrderDetailsIntoModal === 'function') {
                        window.loadOrderDetailsIntoModal(String(id), url || null);
                    }
                } catch (_) {}
            });
        }
    })();
</script>


@stack('scripts')
