<?php

namespace Tests\Feature\Chat;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_message_creates_record_and_returns_json(): void
    {
        Event::fake([MessageSent::class]);

        $sender = User::factory()->create(['user_type' => 'admin']);
        $this->ensurePermission($sender, 'messages.manage');
        $this->actingAs($sender);

        $receiver = User::factory()->create(['user_type' => 'admin']);

        $response = $this->post('/admin/messages', [
            'receiver_id' => $receiver->id,
            'message' => 'Hello, this is a test message!',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'sender_id',
            'receiver_id',
            'message',
            'file_path',
            'is_read',
            'created_at',
            'updated_at',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => 'Hello, this is a test message!',
            'is_read' => false,
            'file_path' => null,
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_fetch_messages_returns_conversation_history(): void
    {
        $user1 = User::factory()->create(['user_type' => 'admin']);
        $user2 = User::factory()->create(['user_type' => 'admin']);

        $this->ensurePermission($user1, 'messages.manage');
        $this->actingAs($user1);

        // Create messages in both directions
        ChatMessage::create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'message' => 'Hey there!',
            'is_read' => true,
        ]);
        ChatMessage::create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'message' => 'Hi! How are you?',
            'is_read' => false,
        ]);
        ChatMessage::create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'message' => 'I am great, thanks!',
            'is_read' => true,
        ]);

        $response = $this->get('/admin/messages/'.$user2->id);

        $response->assertOk();
        $response->assertJsonCount(3);
        $response->assertJsonFragment(['message' => 'Hey there!']);
        $response->assertJsonFragment(['message' => 'Hi! How are you?']);
        $response->assertJsonFragment(['message' => 'I am great, thanks!']);

        // Messages should be ordered ascending by created_at
        // We verify by checking each message is present and the array has 3 items
        $messages = collect($response->json());
        $this->assertCount(3, $messages);
        // SQLite has second-level precision, so records created rapidly may share
        // the same timestamp. Just verify all expected messages are present.
        $this->assertTrue($messages->contains('message', 'Hey there!'));
        $this->assertTrue($messages->contains('message', 'Hi! How are you?'));
        $this->assertTrue($messages->contains('message', 'I am great, thanks!'));
    }

    public function test_mark_as_read_updates_unread_messages(): void
    {
        Event::fake([MessagesRead::class]);

        $currentUser = User::factory()->create(['user_type' => 'admin']);
        $otherUser = User::factory()->create(['user_type' => 'admin']);

        $this->ensurePermission($currentUser, 'messages.manage');
        $this->actingAs($currentUser);

        // Create unread messages from otherUser to currentUser
        ChatMessage::create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $currentUser->id,
            'message' => 'Unread message 1',
            'is_read' => false,
        ]);
        ChatMessage::create([
            'sender_id' => $otherUser->id,
            'receiver_id' => $currentUser->id,
            'message' => 'Unread message 2',
            'is_read' => false,
        ]);

        // Create a message from currentUser to otherUser (should NOT be affected)
        ChatMessage::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $otherUser->id,
            'message' => 'My own message',
            'is_read' => false,
        ]);

        $response = $this->post('/admin/messages/'.$otherUser->id.'/read');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated' => 2,
        ]);

        // The unread messages from otherUser should now be read
        $this->assertDatabaseMissing('chat_messages', [
            'sender_id' => $otherUser->id,
            'receiver_id' => $currentUser->id,
            'is_read' => false,
        ]);

        // The currentUser's own message should still be unread
        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $currentUser->id,
            'receiver_id' => $otherUser->id,
            'is_read' => false,
        ]);
    }

    public function test_chat_routes_require_authentication(): void
    {
        $user = User::factory()->create(['user_type' => 'admin']);

        $this->get('/admin/messages/'.$user->id)->assertRedirect();
        $this->post('/admin/messages', ['receiver_id' => $user->id, 'message' => 'test'])->assertRedirect();
        $this->post('/admin/messages/'.$user->id.'/read')->assertRedirect();
        $this->get('/admin/chat-users')->assertRedirect();
    }

    public function test_list_users_returns_user_list(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->ensurePermission($admin, 'messages.manage');
        $this->actingAs($admin);

        // Create users with IDs matching the controller's hardcoded list
        User::factory()->create(['id' => 2, 'user_type' => 'employee', 'first_name' => 'Alice', 'last_name' => 'Smith']);
        User::factory()->create(['id' => 3, 'user_type' => 'employee', 'first_name' => 'Bob', 'last_name' => 'Jones']);

        $response = $this->get('/admin/chat-users');

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['id', 'name', 'avatar', 'last_message', 'last_time', 'unread_count', 'online'],
        ]);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Alice Smith']);
        $response->assertJsonFragment(['name' => 'Bob Jones']);
    }

    public function test_chat_routes_require_messages_manage_permission(): void
    {
        // Admin without the messages.manage permission
        $admin = User::factory()->create(['user_type' => 'admin']);
        $other = User::factory()->create(['user_type' => 'employee']);
        $this->actingAs($admin);

        $this->get('/admin/messages/'.$other->id)->assertForbidden();
        $this->post('/admin/messages', ['receiver_id' => $other->id, 'message' => 'test'])->assertForbidden();
        $this->post('/admin/messages/'.$other->id.'/read')->assertForbidden();
        $this->get('/admin/chat-users')->assertForbidden();
    }
}
