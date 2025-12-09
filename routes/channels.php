<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Private channel for individual users
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Conversation channel for two users (e.g. 1-5)
Broadcast::channel('conversation.{userIds}', function ($user, $userIds) {
    $ids = explode('-', $userIds);
    return in_array($user->id, $ids);
});

// Presence channel for online users
Broadcast::channel('presence.chat', function (User $user) {
    return [
        'id' => $user->id,
        'name' => $user->first_name . ' ' . $user->last_name,
        'avatar' => $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : $user->profile_photo_url,
    ];
});

// Private channel for typing indicators / messages
Broadcast::channel('private.chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
