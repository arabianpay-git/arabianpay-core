<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TypingEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $senderId;
    public int $receiverId;

    public function __construct(int $senderId, int $receiverId)
    {
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn(): array
    {
        // Broadcast to both users
        return [
            new PrivateChannel('chat.' . $this->senderId),
            new PrivateChannel('chat.' . $this->receiverId)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'senderId' => $this->senderId,
            'receiverId' => $this->receiverId
        ];
    }
}
