<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthController extends ApiController
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Request OTP
     * POST /api/v1/auth/request-otp
     */
    public function requestOtp(RequestOtpRequest $request)
    {
        try {
            $result = $this->otpService->requestOTP(
                $request->phone,
                $request->ip()
            );

            return $this->successResponse($result, 'OTP request processed');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 429);
        }
    }

    /**
     * Verify OTP and login
     * POST /api/v1/auth/verify-otp
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $method = $request->method ?? 'whatsapp';

        // Verify OTP
        $verified = $this->otpService->verifyOTP(
            $request->phone,
            $request->code,
            $method
        );

        if (!$verified) {
            return $this->errorResponse('Invalid or expired OTP code', 400);
        }

        // Create or get user
        $user = $this->otpService->createOrGetUser($request->phone);

        // Update FCM token if provided
        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        // Create token
        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Get authenticated user
     * GET /api/v1/me
     */
    public function me(Request $request)
    {
        return $this->successResponse(
            new UserResource($request->user())
        );
    }

    /**
     * Update user profile
     * PUT /api/v1/me
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'email']));

        return $this->successResponse(
            new UserResource($user),
            'Profile updated successfully'
        );
    }

    /**
     * Update user avatar
     * POST /api/v1/me/avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png|max:2048', // 2MB max
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('r2')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'r2');
        $user->update(['avatar' => $path]);

        return $this->successResponse(
            new UserResource($user),
            'Avatar updated successfully'
        );
    }

    /**
     * Delete user avatar
     * DELETE /api/v1/me/avatar
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return $this->successResponse(
            new UserResource($user),
            'Avatar deleted successfully'
        );
    }

    /**
     * Request phone number change (sends OTP to new number)
     * POST /api/v1/me/change-phone/request
     */
    public function requestPhoneChange(Request $request)
    {
        $request->validate([
            'new_phone' => 'required|string|unique:users,phone',
            'method' => 'sometimes|in:whatsapp,sms',
        ]);

        try {
            $result = $this->otpService->requestOTP(
                $request->new_phone,
                $request->ip()
            );

            return $this->successResponse($result, 'OTP sent to new phone number');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 429);
        }
    }

    /**
     * Verify and complete phone number change
     * POST /api/v1/me/change-phone/verify
     */
    public function verifyPhoneChange(Request $request)
    {
        $request->validate([
            'new_phone' => 'required|string|unique:users,phone',
            'code' => 'required|string|min:4|max:10',
            'method' => 'sometimes|in:whatsapp,sms',
        ]);

        $method = $request->method ?? 'whatsapp';

        // Verify OTP
        $verified = $this->otpService->verifyOTP(
            $request->new_phone,
            $request->code,
            $method
        );

        if (!$verified) {
            return $this->errorResponse('Invalid or expired OTP code', 400);
        }

        // Update phone number
        $user = $request->user();
        $user->update([
            'phone' => $request->new_phone,
            'phone_verified_at' => now(),
        ]);

        return $this->successResponse(
            new UserResource($user),
            'Phone number updated successfully'
        );
    }

    /**
     * Update FCM token
     * POST /api/v1/me/fcm-token
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update(['fcm_token' => $request->token]);

        return $this->successResponse(null, 'FCM token updated');
    }

    /**
     * Logout
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}
