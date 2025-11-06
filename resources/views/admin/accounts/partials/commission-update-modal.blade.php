<!-- Modal for updating commission -->
<div id="updateCommissionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded p-6 w-96">
        <h3 class="text-lg font-semibold mb-4">Update Commission</h3>
        <form id="updateCommissionForm">
            @csrf
            <input type="hidden" name="user_id" id="commission_user_id">
            <div class="mb-4">
                <label for="commission" class="block mb-1">Commission</label>
                <input type="text" name="commission" id="commission_value"
                    class="input w-full border rounded px-2 py-1">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="commissionCancelBtn" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
    <!-- JS -->
    <script>
        // Open modal
        document.querySelectorAll('.update-commission-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('commission_user_id').value = this.dataset.userId;
                document.getElementById('commission_value').value = this.dataset.currentCommission;
                document.getElementById('updateCommissionModal').classList.remove('hidden');
            });
        });

        // Close modal
        document.getElementById('commissionCancelBtn').addEventListener('click', function() {
            document.getElementById('updateCommissionModal').classList.add('hidden');
        });

        // Submit commission update
        document.getElementById('updateCommissionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            let userId = document.getElementById('commission_user_id').value;
            let commission = document.getElementById('commission_value').value;

            fetch("{{ route('updateCommission') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        commission: commission
                    })
                }).then(res => res.json())
                .then(res => {
                    if (res.success) {
                        location.reload(); // reload table after update
                    } else {
                        alert(res.message);
                    }
                });
        });
    </script>
@endpush
