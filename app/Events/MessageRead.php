<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessagesRead implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $readerId;
    public $senderId;
    public $updatedCount;

    /**
     * Create a new event instance.
     *
     * @param  int  $readerId
     * @param  int  $senderId
     * @param  int  $updatedCount
     * @return void
     */
    public function __construct($readerId, $senderId, $updatedCount)
    {
        $this->readerId = $readerId;
        $this->senderId = $senderId;
        $this->updatedCount = $updatedCount;
    }

    /**
     * Channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Notify both users about the read status
        return [
            new PrivateChannel('chat.' . $this->senderId),  // Notify sender
            new PrivateChannel('chat.' . $this->readerId),  // Notify reader
        ];
    }

    /**
     * Customize broadcast payload.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'readerId' => $this->readerId,
            'senderId' => $this->senderId,
            'updatedCount' => $this->updatedCount,
            'timestamp' => now()->toISOString(),
        ];
    }
}
