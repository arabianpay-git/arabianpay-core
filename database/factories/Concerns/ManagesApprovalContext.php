<?php

namespace Database\Factories\Concerns;

use App\Models\Settlement;

trait ManagesApprovalContext
{
    protected bool $enteredApprovalContext = false;

    protected function ensureApprovalContext(): void
    {
        if (! Settlement::isInApprovalContext()) {
            Settlement::enterApprovalContext();
            $this->enteredApprovalContext = true;
        }
    }

    protected function restoreApprovalContext(): void
    {
        if ($this->enteredApprovalContext) {
            Settlement::exitApprovalContext();
            $this->enteredApprovalContext = false;
        }
    }
}
