<?php

namespace App\Services;

use App\Models\DeviceToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Psr\Log\LoggerInterface;

class FirebaseService
{
    protected $messaging;
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/arabianpay-b76ba-firebase-adminsdk-fbsvc-5a6b74b662.json'));
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
            ->withNotification(Notification::create($title, $body))
            ->withData($dataPayload);

        try {
            $this->messaging->send($message);
            return 'Notification sent successfully!';
        } catch (MessagingException | InvalidMessage $e) {
            // Handle token invalidation error and delete invalid token from DB
            if (str_contains($e->getMessage(), 'Requested entity was not found')) {
                DeviceToken::where('token', $deviceToken)->delete();
                $this->logger->info("Removed invalid FCM token: {$deviceToken}");
            }
            // Re-throw exception so it can be logged by caller if needed
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
                try {
                    $this->sendNotification(
                        $deviceToken,
                        $title,
                        $body,
                        $payloadData['click_action'] ?? null,
                        $payloadData
                    );
                } catch (\Throwable $e) {
                    // Log error per token, but continue with others
                    $this->logger->error("FCM notification failed for token {$deviceToken}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error("FCM notification failed for user ID {$userId}: " . $e->getMessage());
        }
    }
}
