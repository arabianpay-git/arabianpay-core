<div class="modal" data-modal="true" id="transfer_legal_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Transfer to Legal Department') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <form action="#" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ translate('Reason') }}</label>
                    <textarea name="reason" class="textarea" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-danger">{{ translate('Transfer') }}</button>
            </form>
        </div>
    </div>
</div>
