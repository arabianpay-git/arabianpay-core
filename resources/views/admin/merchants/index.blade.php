<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Merchants</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="bg-gray-100 text-gray-900">

    <main class="max-w-7xl mx-auto py-10 px-5">

        <div class="flex flex-wrap items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Merchants</h1>
            <div class="flex">
                <label class="relative w-72">
                    <input id="merchant-search" type="text" placeholder="Search merchants..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        value="{{ request('query', '') }}">
                    <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
                </label>
            </div>
        </div>

        <div id="merchant-table-container">
            @include('admin.merchants.partials.table', ['users' => $users])
        </div>

    </main>

    <div id="editModal" class="fixed inset-0 hidden flex items-center justify-center z-50"
        style="background-color: rgba(0,0,0,0.25);">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative animate-fadeIn">
            <button onclick="closeEditModal()"
                class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-xl">✖</button>
            <h2 class="text-2xl font-bold mb-5 text-gray-800">Edit Merchant</h2>
            <form id="editForm" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="page" id="edit_page_number">

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                    <input type="text" name="business_name" id="edit_business_name"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border rounded-lg bg-gray-200 hover:bg-gray-300 transition">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const escapeHtml = unsafe => unsafe ? String(unsafe).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll(
            '>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", "&#039;") : '';

        async function refreshTable(page = 1, query = '') {
            const url = `{{ route('merchants.search') }}?query=${encodeURIComponent(query)}&page=${page}`;
            try {
                const resp = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const html = await resp.text();
                document.getElementById('merchant-table-container').innerHTML = html;
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname +
                    `?query=${encodeURIComponent(query)}&page=${page}`;
                window.history.pushState({
                    path: newUrl
                }, '', newUrl);

            } catch (err) {
                console.error(err);
            }
        }

        function openEditModalFromData(id, first, last, business) {
            document.getElementById('edit_first_name').value = first || '';
            document.getElementById('edit_last_name').value = last || '';
            document.getElementById('edit_business_name').value = business || '';
            document.getElementById('editForm').action = "{{ url('/merchants') }}/" + id + "/update";

            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = urlParams.get('page') || '1';
            document.getElementById('edit_page_number').value = currentPage;

            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        let searchTimer = null;
        const searchInput = document.getElementById('merchant-search');
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const q = searchInput.value || '';
                refreshTable(1, q);
            }, 250);
        });

        document.addEventListener('click', function(e) {
            const el = e.target.closest('.edit-btn');
            if (el) openEditModalFromData(el.dataset.id, el.dataset.first, el.dataset.last, el.dataset.business);

            const a = e.target.closest('#merchant-table-container a');
            if (a && a.href.includes('page=')) {
                e.preventDefault();
                const url = new URL(a.href);
                const page = url.searchParams.get('page');
                const q = searchInput.value || '';
                refreshTable(page, q);
            }
        });

        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const action = form.action;
            const formData = new FormData(form);
            const csrfToken = form.querySelector('input[name="_token"]').value;

            try {
                Swal.fire({
                    title: 'Saving...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const resp = await fetch(action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });

                if (resp.status === 422) {
                    const body = await resp.json();
                    let html = '<ul class="text-left">';
                    for (const key in body.errors) body.errors[key].forEach(msg => html +=
                        `<li>${escapeHtml(msg)}</li>`);
                    html += '</ul>';
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation error',
                        html
                    });
                    return;
                }

                const body = await resp.json();
                const currentPage = body.page;
                const currentQuery = searchInput.value || '';

                Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: 'Merchant updated'
                });

                closeEditModal();
                refreshTable(currentPage, currentQuery);

            } catch (err) {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Save failed',
                    text: err.message || 'Unknown error'
                });
            }
        });
    </script>
</body>

</html>
