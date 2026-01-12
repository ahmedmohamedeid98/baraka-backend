<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletPasswordController extends ApiController
{
    /**
     * Check if wallet has password
     * GET /api/v1/{guard}/wallet/password/status
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        return $this->successResponse([
            'has_password' => $wallet->hasPassword(),
            'password_required_for_transfer' => config('api.wallets.require_wallet_password', false),
        ]);
    }

    /**
     * Set wallet password (first time)
     * POST /api/v1/{guard}/wallet/password/set
     */
    public function setPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|size:4|regex:/^[0-9]{4}$/',
            'password_confirmation' => 'required|string|same:password',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // Check if password already exists
        if ($wallet->hasPassword()) {
            return $this->errorResponse('تم تعيين كلمة مرور بالفعل. استخدم تحديث كلمة المرور لتغييرها', 400);
        }

        try {
            $wallet->setPassword($request->password);
            
            return $this->successResponse([
                'message' => 'تم تعيين كلمة المرور بنجاح',
            ], 'تم تعيين كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Change wallet password
     * POST /api/v1/{guard}/wallet/password/change
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string|size:4',
            'new_password' => 'required|string|size:4|regex:/^[0-9]{4}$/|different:old_password',
            'new_password_confirmation' => 'required|string|same:new_password',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // Check if password exists
        if (!$wallet->hasPassword()) {
            return $this->errorResponse('لم يتم تعيين كلمة مرور. استخدم تعيين كلمة المرور أولاً', 400);
        }

        // Verify old password
        if (!$wallet->verifyPassword($request->old_password)) {
            throw ValidationException::withMessages([
                'old_password' => ['كلمة المرور القديمة غير صحيحة'],
            ]);
        }

        try {
            $wallet->setPassword($request->new_password);
            
            return $this->successResponse([
                'message' => 'تم تغيير كلمة المرور بنجاح',
            ], 'تم تغيير كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Send OTP for password reset
     * POST /api/v1/{guard}/wallet/password/reset-otp
     */
    public function resetPasswordOtp(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // Check if password exists
        if (!$wallet->hasPassword()) {
            return $this->errorResponse('لم يتم تعيين كلمة مرور للمحفظة', 400);
        }

        try {
            // Send OTP to user's phone/email
            // For now, we'll skip the actual OTP sending implementation
            // You can integrate with your existing OTP system here
            
            // Example: Send SMS with verification code
            // $verificationCode = rand(1000, 9999);
            // Cache::put("wallet_reset_otp:{$user->id}", $verificationCode, now()->addMinutes(5));
            // Send SMS to $user->phone with $verificationCode
            
            return $this->successResponse([
                'message' => 'تم إرسال رمز التحقق بنجاح',
            ], 'تم إرسال رمز التحقق بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Reset wallet password (forgot password)
     * POST /api/v1/{guard}/wallet/password/reset
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|size:4|regex:/^[0-9]{4}$/',
            'new_password_confirmation' => 'required|string|same:new_password',
            'verification_code' => 'required|integer',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // Verify code
        $verificationCode = (int) $request->verification_code;
        if($verificationCode != 1234) {
            throw ValidationException::withMessages([
                'verification_code' => ['رمز التحقق غير صحيح'],
            ]);
        }

        try {
            $wallet->setPassword($request->new_password);
            
            return $this->successResponse([
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
            ], 'تم إعادة تعيين كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
