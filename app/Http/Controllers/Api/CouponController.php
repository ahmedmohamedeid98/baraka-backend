<?php

namespace App\Http\Controllers\Api;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends ApiController
{
    /**
     * Apply coupon
     * POST /api/v1/coupons/apply
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->active()->first();

        if (!$coupon) {
            return $this->errorResponse('Invalid coupon code');
        }

        if (!$coupon->isValid()) {
            return $this->errorResponse('Coupon is not valid or has expired');
        }

        if (!$coupon->canBeUsedByUser($request->user())) {
            return $this->errorResponse('You have reached the usage limit for this coupon');
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        if ($discount == 0) {
            return $this->errorResponse('Minimum order amount not met');
        }

        return $this->successResponse([
            'code' => $coupon->code,
            'type' => $coupon->type,
            'discount' => $discount,
            'message' => "Coupon applied successfully. You save {$discount} EGP",
        ]);
    }
}
