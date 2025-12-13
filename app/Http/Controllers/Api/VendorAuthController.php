<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RequestOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\OtpService;
use Illuminate\Http\Request;

class VendorAuthController extends ApiController
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Request OTP for vendor login
     * POST /api/v1/vendor/auth/request-otp
     */
    public function requestOtp(RequestOtpRequest $request)
    {
        try {
            // Check if vendor exists with this phone
            $vendor = Vendor::where('phone', $request->phone)->first();
            
            if (!$vendor) {
                return $this->errorResponse('No vendor account found with this phone number', 404);
            }

            if (!$vendor->is_active) {
                return $this->errorResponse('Your vendor account is inactive', 403);
            }

            if (!$vendor->is_approved) {
                return $this->errorResponse('Your vendor account is pending approval', 403);
            }

            $result = $this->otpService->requestOTP(
                $request->phone,
                $request->ip()
            );

            return $this->successResponse($result, 'OTP sent successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 429);
        }
    }

    /**
     * Verify OTP and login vendor
     * POST /api/v1/vendor/auth/verify-otp
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

        // Get vendor
        $vendor = Vendor::where('phone', $request->phone)->first();

        if (!$vendor) {
            return $this->errorResponse('No vendor account found', 404);
        }

        if (!$vendor->is_active) {
            return $this->errorResponse('Your vendor account is inactive', 403);
        }

        if (!$vendor->is_approved) {
            return $this->errorResponse('Your vendor account is pending approval', 403);
        }

        // Create token
        $token = $vendor->createToken('vendor-mobile-app', ['vendor'])->plainTextToken;

        return $this->successResponse([
            'vendor' => new VendorResource($vendor),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Get authenticated vendor
     * GET /api/v1/vendor/me
     */
    public function me(Request $request)
    {
        return $this->successResponse(
            new VendorResource($request->user())
        );
    }

    /**
     * Update vendor profile
     * PUT /api/v1/vendor/me
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name_ar' => 'sometimes|string|max:255',
            'description_ar' => 'sometimes|string',
            'email' => 'sometimes|email|nullable|unique:vendors,email,' . $request->user()->id,
            'address' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
        ]);

        $vendor = $request->user();
        $vendor->update($request->only([
            'name_ar',
            'description_ar',
            'email',
            'address',
            'latitude',
            'longitude',
        ]));

        return $this->successResponse(
            new VendorResource($vendor),
            'Profile updated successfully'
        );
    }

    /**
     * Update FCM token for push notifications
     * POST /api/v1/vendor/fcm-token
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $vendor = $request->user();
        $vendor->update(['fcm_token' => $request->fcm_token]);

        return $this->successResponse(null, 'FCM token updated successfully');
    }

    /**
     * Logout vendor
     * POST /api/v1/vendor/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}
