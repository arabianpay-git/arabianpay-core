<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    public function run()
    {
        $admin = User::whereEncrypted('email', 'admin@gmail.com')->first();
        $asad = User::whereEncrypted('email', 'asadbala41@gmail.com')->first();

        ChatMessage::create([
            'sender_id' => $admin->id,
            'receiver_id' => $asad->id,
            'message' => 'Hi Asad! This is a test message.',
        ]);

        ChatMessage::create([
            'sender_id' => $asad->id,
            'receiver_id' => $admin->id,
            'message' => 'Hello Admin! Message received.',
        ]);
    }
}
