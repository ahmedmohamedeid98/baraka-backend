<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtpService
{
    protected $ultraMsgService;
    protected $firebaseService;

    public function __construct(UltraMsgService $ultraMsgService, FirebaseService $firebaseService)
    {
        $this->ultraMsgService = $ultraMsgService;
        $this->firebaseService = $firebaseService;
    }

    /**
     * Request OTP
     * Returns ['method' => 'whatsapp|sms', 'use_sms' => true|false]
     */
    public function requestOTP(string $phone, string $ipAddress): array
    {
        // Rate limiting
        $rateLimitKey = 'otp:' . $phone;
        // if (RateLimiter::tooManyAttempts($rateLimitKey, config('ultramsg.otp.rate_limit_per_hour'))) {
        //     $seconds = RateLimiter::availableIn($rateLimitKey);
        //     throw new \Exception("Too many OTP requests. Please try again in " . ceil($seconds / 60) . " minutes.");
        // }

        // Generate OTP code
        $code = 1234;// $this->generateCode();
        $expiresAt = now()->addMinutes(config('ultramsg.otp.expiry_minutes'));

        // Try WhatsApp first
        $whatsappSent = true;//$this->ultraMsgService->sendOTP($phone, $code);

        if ($whatsappSent) {
            // Store OTP for WhatsApp verification
            OtpVerification::create([
                'phone' => $phone,
                'code' => $code,
                'method' => 'whatsapp',
                'expires_at' => $expiresAt,
                'ip_address' => $ipAddress,
            ]);

            RateLimiter::hit($rateLimitKey, 300); // 5 minutes

            return [
                'method' => 'whatsapp',
                'use_sms' => false,
                'message' => 'OTP sent via WhatsApp',
            ];
        }

        // WhatsApp failed, return flag to use SMS (client-side Firebase)
        $this->firebaseService->logSmsAttempt($phone, null, 'fallback_requested');

        RateLimiter::hit($rateLimitKey, 300);

        return [
            'method' => 'sms',
            'use_sms' => true,
            'message' => 'Please use SMS verification',
        ];
    }

    /**
     * Verify OTP
     */
    public function verifyOTP(string $phone, string $code, string $method = 'whatsapp'): bool
    {
        if ($method === 'whatsapp') {
            return $this->verifyWhatsAppOTP($phone, $code);
        }

        // For SMS, we just log - actual verification is done client-side with Firebase
        // Backend accepts the verification token/result from client
        return true;
    }

    /**
     * Verify WhatsApp OTP
     */
    protected function verifyWhatsAppOTP(string $phone, string $code): bool
    {
        $otp = OtpVerification::where('phone', $phone)
            ->where('code', $code)
            ->where('method', 'whatsapp')
            ->valid()
            ->first();

        if (!$otp) {
            return false;
        }

        if ($otp->isExpired()) {
            return false;
        }

        $otp->markAsVerified();

        return true;
    }

    /**
     * Create or get user after OTP verification
     */
    public function createOrGetUser(string $phone): User
    {
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $user = User::create([
                'phone' => $phone,
                'phone_verified_at' => now(),
                'is_active' => true,
            ]);

            // Assign default role
            $user->assignRole('customer');
        } else {
            // Update verification timestamp
            $user->update([
                'phone_verified_at' => now(),
            ]);
        }

        return $user;
    }

    /**
     * Generate random OTP code
     */
    protected function generateCode(): string
    {
        $length = config('ultramsg.otp.length', 4);
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Clean expired OTPs
     */
    public function cleanExpiredOTPs(): int
    {
        return OtpVerification::where('expires_at', '<', now())
            ->where('verified', false)
            ->delete();
    }
}
