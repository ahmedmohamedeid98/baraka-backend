<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\OtpService;
use Illuminate\Http\Request;

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
