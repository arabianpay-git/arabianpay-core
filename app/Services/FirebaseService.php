<?php

namespace App\Services;

use App\Models\DeviceToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;

class FirebaseService
{
    protected $messaging;
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $factory = (new Factory)->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        $this->messaging = $factory->createMessaging();
        $this->logger = $logger;
    }

    public function sendNotification(
        string $deviceToken,
        string $title,
        string $body,
        ?string $clickAction = null,
        array $additionalData = []
    ): string {
        $dataPayload = [
            'title' => $title,
            'body' => $body,
        ];

        if ($clickAction) {
            $dataPayload['click_action'] = $clickAction;
        }

        // Merge any additional data passed explicitly
        $dataPayload = array_merge($dataPayload, $additionalData);

        $message = CloudMessage::withTarget('token', $deviceToken)
            // ->withNotification(Notification::create($title, $body))
            ->withData($dataPayload);

        $this->messaging->send($message);

        return 'Notification sent successfully!';
    }

    public function sendCustomNotification(
        $userId,
        string $title,
        string $body,
        array $data = []
    ): void {
        try {
            $deviceTokens = DeviceToken::where('user_id', $userId)->pluck('token');

            if ($deviceTokens->isEmpty()) {
                $this->logger->info("No FCM device tokens found for user ID {$userId}");
                return;
            }

            $payloadData = array_merge([
                'title' => $title,
                'body' => $body,
            ], $data);

            foreach ($deviceTokens as $deviceToken) {
                $this->sendNotification(
                    $deviceToken,
                    $title,
                    $body,
                    $payloadData['click_action'] ?? null,
                    $payloadData
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error("FCM notification failed for user ID {$userId}: " . $e->getMessage());
        }
    }
}
