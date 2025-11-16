<div class="modal" data-modal="true" id="payout_order_modal">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h1>Create a Payout</h1>
            <button class="btn btn-sm btn-icon btn-light btn-clear" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body px-5 pb-5">
            <!-- Error Messages -->
            @if($errors->any())
            <div class="alert alert-danger mb-5">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form id="payout-create-form" method="POST" action="{{ route('payouts.store') }}" onsubmit="console.log('Form submitting...', {order_id: document.getElementById('payout-order-id').value, amount: document.getElementById('payout-amount').value})">
                @csrf
                
                <!-- Hidden Order ID -->
                <input type="hidden" name="order_id" id="payout-order-id">
                
                <!-- Order Information Display -->
                <div class="mb-5 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-600 text-primary">Order ID:</span>
                            <span class="font-semibold ml-2" id="display-order-id">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600 text-primary">Customer:</span>
                            <span class="font-semibold ml-2" id="display-customer-name">-</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-600 text-primary">Seller:</span>
                            <span class="font-semibold ml-2" id="display-seller-name">-</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-600 text-primary">Order Amount:</span>
                            <span class="font-semibold ml-2" id="display-order-amount">-</span>
                        </div>
                    </div>
                </div>

                <!-- Payout Amount -->
                <div class="mb-5">
                    <label for="payout-amount" class="form-label required text-primary">
                        Payout Amount
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0.01"
                        class="input" 
                        id="payout-amount" 
                        name="amount" 
                        placeholder="Enter payout amount"
                        required>
                   
                </div>

                <!-- Notes -->
                <div class="mb-5">
                    <label for="payout-notes" class="form-label text-primary">
                        Notes
                    </label>
                    <textarea 
                        class="textarea" 
                        id="payout-notes" 
                        name="notes" 
                        rows="4"
                        maxlength="2000"
                        placeholder="Add any notes or comments about this payout..."></textarea>
                  
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3">
                    <button 
                        type="button" 
                        class="btn btn-light" 
                        data-modal-dismiss="true">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="btn btn-primary"
                        id="submit-payout-btn">
                        <i class="ki-filled ki-check mr-2"></i>
                        Create Payout
                    </button>
                </div>
            </form>
            
            <!-- Response Display -->
            <div id="payout-response" class="mt-3 p-3 rounded hidden"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payout-create-form');
    const submitBtn = document.getElementById('submit-payout-btn');
    const responseDiv = document.getElementById('payout-response');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevent default form submission
        
        console.log('=== Form submitting via AJAX ===');
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ki-filled ki-loading animate-spin mr-2"></i> Creating...';
        
        // Get form data
        const formData = new FormData(form);
        
        console.log('Form data:', Object.fromEntries(formData));
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            console.log('Response status:', response.status);
            
            const contentType = response.headers.get('content-type');
            let result;
            
            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
                console.log('JSON response:', result);
            } else {
                result = await response.text();
                console.log('Text response (first 500 chars):', result.substring(0, 500));
            }
            
            if (response.ok) {
               
                // Close the modal and reload the page
                setTimeout(() => {
                    // Close the modal
                    const modal = document.getElementById('payout_order_modal');
                    if (modal) {
                        modal.classList.remove('open');
                    }
                    // Reload the page
                    window.location.reload();
                }, 1000);
            } else {
                // Error
                responseDiv.className = 'mt-3 p-3 rounded bg-red-50 text-red-800';
                responseDiv.textContent = typeof result === 'object' ? JSON.stringify(result, null, 2) : result;
                responseDiv.classList.remove('hidden');
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ki-filled ki-check mr-2"></i> Create Payout';
            }
        } catch (error) {
            console.error('Error:', error);
            responseDiv.className = 'mt-3 p-3 rounded bg-red-50 text-red-800';
            responseDiv.textContent = 'Error: ' + error.message;
            responseDiv.classList.remove('hidden');
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ki-filled ki-check mr-2"></i> Create Payout';
        }
    });
});
</script>


