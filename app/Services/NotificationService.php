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
        // The model's $casts handles array -> JSON serialization;
        // pass raw values so the cast doesn't double-encode.
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
