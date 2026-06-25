<?php

namespace App\Services;

use App\Models\DeviceToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Psr\Log\LoggerInterface;
use Throwable;

class FirebaseService
{
    protected $messaging;

    protected $projectId;

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $serviceAccountPath = storage_path('app/firebase/arabianpay-b76ba-firebase-adminsdk-fbsvc-5a6b74b662.json');

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
        $this->logger = $logger;

        // Extract project_id manually from JSON file
        $config = json_decode(file_get_contents($serviceAccountPath), true);
        $this->projectId = $config['project_id'] ?? 'unknown';

        $this->logger->info('FirebaseService initialized for project: '.$this->projectId);
    }

    public function sendNotification(
        string $deviceToken,
        string $title,
        string $body,
        ?string $clickAction = null,
        array $additionalData = []
    ): string {
        try {
            $dataPayload = [
                'title' => $title,
                'body' => $body,
            ];

            if ($clickAction) {
                $dataPayload['click_action'] = $clickAction;
            }

            // Merge any additional data passed explicitly
            $dataPayload = array_merge($dataPayload, $additionalData);

            // Log before sending
            $this->logger->info('🚀 Sending FCM to token: '.substr($deviceToken, 0, 10).'...'.substr($deviceToken, -10));
            $this->logger->info('Using Firebase Project: '.$this->projectId);

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData($dataPayload);

            $this->messaging->send($message);

            return 'Notification sent successfully via project: '.$this->projectId;
        } catch (Throwable $e) {
            $this->logger->error('FCM Error: '.$e->getMessage());
            throw $e;
        }
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
        } catch (Throwable $e) {
            $this->logger->error("FCM notification failed for user ID {$userId}: ".$e->getMessage());
        }
    }
}
