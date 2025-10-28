<div class="modal" data-modal="true" id="send_reminder_modal">
    <div class="modal-content max-w-[600px] top-[5%]">
        <div class="modal-header py-4 px-5">
            <h5 class="modal-title">{{ translate('Send Reminder') }}</h5>
            <button type="button" class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="modal-body p-5">
            <form action="#" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ translate('Message') }}</label>
                    <textarea name="message" class="form-control" rows="4" required>{{ translate('Dear user, please pay your installment on time.') }}</textarea>
                </div>
                <button type="submit" class="btn btn-success">{{ translate('Send Reminder') }}</button>
            </form>
        </div>
    </div>
</div>
