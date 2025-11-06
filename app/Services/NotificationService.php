<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create and save a notification.
     *
     * @param int|null $userId
     * @param string|null $type
     * @param string|array $data
     * @return Notification
     */
    public function createNotification(?int $userId, ?string $type, $data): Notification
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
