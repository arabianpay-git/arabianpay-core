<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Create and save a notification.
     *
     * @param  string|array  $data
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
