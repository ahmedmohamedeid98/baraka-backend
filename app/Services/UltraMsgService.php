<?php

namespace App\Services;

use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UltraMsgService
{
    protected $baseUrl;
    protected $instanceId;
    protected $token;
    protected $enabled;

    public function __construct()
    {
        $this->baseUrl = config('ultramsg.base_url');
        $this->instanceId = config('ultramsg.instance_id');
        $this->token = config('ultramsg.token');
        $this->enabled = config('ultramsg.enabled');
    }

    /**
     * Send WhatsApp OTP
     */
    public function sendOTP(string $phone, string $code): bool
    {
        if (!$this->enabled) {
            Log::info('UltraMsg is disabled. Skipping WhatsApp OTP.');
            return false;
        }

        $message = "كود التحقق الخاص بك: {$code}\n\nYour verification code: {$code}\n\nصالح لمدة 5 دقائق / Valid for 5 minutes";

        return $this->sendMessage($phone, $message, 'otp');
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(string $phone, array $orderData): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $trackingUrl = $orderData['tracking_url'] ?? '';
        $orderNumber = $orderData['order_number'] ?? '';
        $total = $orderData['total'] ?? '';

        $message = "طلب جديد #{$orderNumber}\n";
        $message .= "المبلغ الإجمالي: {$total} جنيه\n\n";
        $message .= "تتبع طلبك:\n{$trackingUrl}\n\n";
        $message .= "شكراً لتسوقك معنا!";

        return $this->sendMessage($phone, $message, 'order_notification');
    }

    /**
     * Send WhatsApp message
     */
    protected function sendMessage(string $phone, string $message, string $type = 'other'): bool
    {
        try {
            // Format phone number (remove + and ensure it starts with country code)
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (!str_starts_with($phone, '20')) {
                $phone = '20' . $phone;
            }

            $url = "{$this->baseUrl}/{$this->instanceId}/messages/chat";

            $response = Http::asForm()->post($url, [
                'token' => $this->token,
                'to' => $phone,
                'body' => $message,
            ]);

            $responseData = $response->json();
            $success = $response->successful() && ($responseData['sent'] ?? false);

            // Log the attempt
            WhatsappLog::create([
                'phone' => $phone,
                'type' => $type,
                'message' => $message,
                'status' => $success ? 'sent' : 'failed',
                'response' => $responseData,
            ]);

            return $success;

        } catch (\Exception $e) {
            Log::error('UltraMsg Error: ' . $e->getMessage());

            WhatsappLog::create([
                'phone' => $phone,
                'type' => $type,
                'message' => $message,
                'status' => 'error',
                'response' => ['error' => $e->getMessage()],
            ]);

            return false;
        }
    }
}
