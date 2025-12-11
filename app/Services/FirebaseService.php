<?php

namespace App\Services;

use App\Models\FirebaseSmsLog;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = storage_path(env('FIREBASE_CREDENTIALS', 'firebase-credentials.json'));
        
        if (!file_exists($credentialsPath)) {
            Log::warning('Firebase credentials file not found: ' . $credentialsPath);
            $this->messaging = null;
            return;
        }
        
        try {
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Log Firebase SMS attempt (Client-side verification)
     * Backend just logs the attempt, actual verification happens on client
     */
    public function logSmsAttempt(string $phone, ?string $verificationId = null, string $status = 'sent'): void
    {
        try {
            FirebaseSmsLog::create([
                'phone' => $phone,
                'verification_id' => $verificationId,
                'status' => $status,
                'response' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Firebase SMS Log Error: ' . $e->getMessage());
        }
    }

    /**
     * Send push notification via FCM
     */
    public function sendPushNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return false;
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            
            return true;

        } catch (\Exception $e) {
            Log::error('FCM Push Notification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendMulticastPushNotification(array $fcmTokens, string $title, string $body, array $data = []): array
    {
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return ['success' => 0, 'failure' => count($fcmTokens)];
        }

        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->sendMulticast($message, $fcmTokens);
            
            return [
                'success' => $result->successes()->count(),
                'failure' => $result->failures()->count(),
            ];

        } catch (\Exception $e) {
            Log::error('FCM Multicast Error: ' . $e->getMessage());
            return ['success' => 0, 'failure' => count($fcmTokens)];
        }
    }
}
