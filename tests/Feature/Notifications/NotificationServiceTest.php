<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_notification_persists_record(): void
    {
        $user = User::factory()->create();
        $service = new NotificationService;

        $notification = $service->createNotification($user->id, 'order_updated', [
            'order_id' => 123,
            'status' => 'shipped',
        ]);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame('order_updated', $notification->type);
        $this->assertNull($notification->read_at);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'order_updated',
        ]);
    }

    public function test_create_notification_accepts_null_user_id(): void
    {
        $service = new NotificationService;

        $notification = $service->createNotification(null, 'broadcast', ['message' => 'System maintenance']);

        $this->assertNull($notification->user_id);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => null,
            'type' => 'broadcast',
        ]);
    }

    public function test_create_notification_stores_array_data_as_json_and_reads_it_back(): void
    {
        $user = User::factory()->create();
        $service = new NotificationService;

        $payload = [
            'title' => 'Payment received',
            'amount' => 250.50,
            'metadata' => ['currency' => 'SAR', 'reference' => 'REF-001'],
        ];

        $notification = $service->createNotification($user->id, 'payment_received', $payload);
        $fresh = Notification::find($notification->id);

        $this->assertIsArray($fresh->data);
        $this->assertSame('Payment received', $fresh->data['title']);
        $this->assertSame(250.50, $fresh->data['amount']);
        $this->assertSame(['currency' => 'SAR', 'reference' => 'REF-001'], $fresh->data['metadata']);
        $this->assertJsonStringEqualsJsonString(
            json_encode($payload),
            $fresh->getAttributes()['data']
        );
    }

    public function test_create_notification_accepts_string_data(): void
    {
        $service = new NotificationService;

        $notification = $service->createNotification(null, 'log', 'Raw string message');

        $fresh = Notification::find($notification->id);
        $this->assertSame('Raw string message', $fresh->data);
    }
}
