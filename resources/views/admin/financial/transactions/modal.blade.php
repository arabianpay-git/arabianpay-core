<!-- Transaction Details Modal -->
<div class="modal" data-modal="true" id="transaction_modal" data-modal-disable-scroll="false">
    <div class="modal-content max-w-[800px]">
        <div class="modal-header pr-2.5">
            <h3 class="modal-title">
                 Transaction Details - #<span id="modal-transaction-id"></span>
            </h3>
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-black-left"></i>
            </button>
        </div>
        <div class="modal-body p-0">
            <div class="p-5 pb-4">
                <!-- Transaction Information -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-gray-600 text-xs">UUID</label>
                        <span class="text-gray-900 font-mono text-sm" id="modal-uuid"></span>
                    </div>
                    
                 
                    
                    <div class="flex flex-col gap-1">
                        <label class="text-gray-600 text-xs">Amount</label>
                        <span class="text-gray-900 font-medium text-lg" id="modal-amount"></span>
                    </div>
                    
                  
                    <div class="flex flex-col gap-1">
                        <label class="text-gray-600 text-xs">Transaction Date</label>
                        <span class="text-gray-900" id="modal-date"></span>
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <label class="text-gray-600 text-xs">User</label>
                        <span class="text-gray-900" id="modal-user"></span>
                    </div>
                    
                    <div class="flex flex-col gap-1" id="modal-reference-container" style="display: none;">
                        <label class="text-gray-600 text-xs">Reference ID</label>
                        <span class="text-gray-900" id="modal-reference"></span>
                    </div>
                    
                    <div class="flex flex-col gap-1" id="modal-customer-container" style="display: none;">
                        <label class="text-gray-600 text-xs">Customer</label>
                        <span class="text-gray-900" id="modal-customer"></span>
                    </div>
                    
                    <div class="flex flex-col gap-1" id="modal-supplier-container" style="display: none;">
                        <label class="text-gray-600 text-xs">Supplier</label>
                        <span class="text-gray-900" id="modal-supplier"></span>
                    </div>
                    
                    <div class="flex flex-col gap-1" id="modal-order-container" style="display: none;">
                        <label class="text-gray-600 text-xs">Order</label>
                        <span class="text-gray-900" id="modal-order"></span>
                    </div>
                </div>
                
                <div class="flex flex-col gap-1 mt-4" id="modal-notes-container" style="display: none;">
                    <label class="text-gray-600 text-xs">Notes</label>
                    <span class="text-gray-900" id="modal-notes"></span>
                </div>
            </div>
            
            <!-- Journal Entries Section -->
            <div class="border-t border-gray-200">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-medium">Journal Entries</h4>
                        <span class="badge badge-light" id="modal-entries-count">0 entries</span>
                    </div>
                    
                    <div class="scrollable-x-auto">
                        <table class="table table-auto table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[120px]">Account</th>
                                    <th class="min-w-[80px]">Debit</th>
                                    <th class="min-w-[80px]">Credit</th>
                                    <th class="min-w-[100px]">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="modal-entries-table">
                                <!-- Entries will be loaded here -->
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50">
                                    <td colspan="1" class="font-medium">Total</td>
                                    <td class="font-medium text-red-600" id="modal-total-debit">0.00 SR</td>
                                    <td class="font-medium text-green-600" id="modal-total-credit">0.00 SR</td>
                                    <td colspan="2" class="font-medium">
                                        <span id="modal-balance-status">Balanced</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div id="modal-no-entries" class="flex flex-col items-center gap-3 py-6" style="display: none;">
                        <i class="ki-filled ki-file-sheet text-2xl text-gray-400"></i>
                        <span class="text-gray-600">No journal entries found</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer justify-center">
            <div class="flex gap-4">
                <button class="btn btn-light" data-modal-dismiss="true">
                    Close
                </button>
               
            </div>
        </div>
    </div>
</div>

<script>
function showTransactionModal(transactionId) {
    // Show loading state
    const modal = document.getElementById('transaction_modal');
    const modalContent = modal.querySelector('.modal-body');
    
    // Show modal
    KTModal.getInstance(modal).show();
    
    // Load transaction data via AJAX
    fetch(`/admin/financial/transactions/${transactionId}/modal-data`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateTransactionModal(data.transaction, data.entries);
            } else {
                console.error('Failed to load transaction data');
            }
        })
        .catch(error => {
            console.error('Error loading transaction data:', error);
        });
}

function populateTransactionModal(transaction, entries) {
    // Populate basic transaction info
    document.getElementById('modal-transaction-id').textContent = transaction.id;
    document.getElementById('modal-uuid').textContent = transaction.uuid || '-';
    document.getElementById('modal-amount').textContent = parseFloat(transaction.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' SR';
    document.getElementById('modal-date').textContent = transaction.transaction_date || '-';
    document.getElementById('modal-user').textContent = transaction.user ? transaction.user.name : '-';
    
    // Transaction type badge
    //const typeElement = document.getElementById('modal-type');
   // typeElement.innerHTML = `<span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block badge-info">${transaction.transaction_type.charAt(0).toUpperCase() + transaction.transaction_type.slice(1)}</span>`;
    
    // Status badge
   // const statusElement = document.getElementById('modal-status');
   //// let statusClass = 'badge-secondary';
  //  if (transaction.status === 'completed') statusClass = 'badge-success';
  //  else if (transaction.status === 'pending') statusClass = 'badge-warning';
  //  else if (transaction.status === 'failed') statusClass = 'badge-danger';
    
   // statusElement.innerHTML = `<span class="badge badge-xs text-xs px-2 py-1 w-fit inline-block ${statusClass}">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span>`;
    
    // Optional fields
    const showHideField = (containerId, valueId, value) => {
        const container = document.getElementById(containerId);
        const valueElement = document.getElementById(valueId);
        if (value) {
            container.style.display = 'flex';
            valueElement.textContent = value;
        } else {
            container.style.display = 'none';
        }
    };
    
    showHideField('modal-reference-container', 'modal-reference', transaction.reference_id ? `#${transaction.reference_id}` : null);
    showHideField('modal-customer-container', 'modal-customer', transaction.customer ? transaction.customer.name : null);
    showHideField('modal-supplier-container', 'modal-supplier', transaction.supplier ? transaction.supplier.name : null);
    showHideField('modal-order-container', 'modal-order', transaction.order ? `#${transaction.order.id}` : null);
    showHideField('modal-notes-container', 'modal-notes', transaction.notes);
    
    // Update edit button
   // document.getElementById('modal-edit-btn').href = `/admin/financial/transactions/${transaction.id}/edit`;
    
    // Populate entries
    populateEntriesTable(entries);
}

function populateEntriesTable(entries) {
    const tableBody = document.getElementById('modal-entries-table');
    const noEntriesDiv = document.getElementById('modal-no-entries');
    const entriesCount = document.getElementById('modal-entries-count');
    
    entriesCount.textContent = `${entries.length} entries`;
    
    if (entries.length === 0) {
        tableBody.innerHTML = '';
        noEntriesDiv.style.display = 'flex';
        return;
    }
    
    noEntriesDiv.style.display = 'none';
    
    let totalDebit = 0;
    let totalCredit = 0;
    let tableHTML = '';
    
    entries.forEach(entry => {
        const debit = parseFloat(entry.debit || 0);
        const credit = parseFloat(entry.credit || 0);
        totalDebit += debit;
        totalCredit += credit;
        
        let statusClass = 'badge-secondary';
        if (entry.status === 'completed') statusClass = 'badge-success';
        else if (entry.status === 'pending') statusClass = 'badge-warning';
        else if (entry.status === 'failed') statusClass = 'badge-danger';
        
        tableHTML += `
            <tr>
                <td>
                    ${entry.account ? `
                        <span class="text-sm font-medium">${entry.account.account_name}</span>
                    ` : (entry.account_name || '-')}
                </td>
                <td>${debit > 0 ? `<span class="text-red-600 font-medium">${debit.toLocaleString('en-US', {minimumFractionDigits: 2})} SR</span>` : '<span class="text-gray-400">-</span>'}</td>
                <td>${credit > 0 ? `<span class="text-green-600 font-medium">${credit.toLocaleString('en-US', {minimumFractionDigits: 2})} SR</span>` : '<span class="text-gray-400">-</span>'}</td>
               <td>${entry.notes ? `<span class="text-sm">${entry.notes.substring(0, 50)}${entry.notes.length > 50 ? '...' : ''}</span>` : '<span class="text-gray-400">-</span>'}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = tableHTML;
    
    // Update totals
    document.getElementById('modal-total-debit').textContent = `${totalDebit.toLocaleString('en-US', {minimumFractionDigits: 2})} SR`;
    document.getElementById('modal-total-credit').textContent = `${totalCredit.toLocaleString('en-US', {minimumFractionDigits: 2})} SR`;
    
    const balanceStatus = document.getElementById('modal-balance-status');
    const difference = totalDebit - totalCredit;
    if (Math.abs(difference) < 0.01) {
        balanceStatus.innerHTML = '<span class="text-green-600">Balanced</span>';
    } else {
        balanceStatus.innerHTML = `<span class="text-red-600">${Math.abs(difference).toLocaleString('en-US', {minimumFractionDigits: 2})} SR Unbalanced</span>`;
    }
}
</script>