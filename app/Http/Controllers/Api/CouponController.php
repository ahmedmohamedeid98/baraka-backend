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
            return $this->errorResponse(__('messages.coupon.invalid'));
        }

        if (!$coupon->isValid()) {
            return $this->errorResponse(__('messages.coupon.expired'));
        }

        if (!$coupon->canBeUsedByUser($request->user())) {
            return $this->errorResponse(__('messages.coupon.usage_limit_reached'));
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        if ($discount == 0) {
            return $this->errorResponse(__('messages.coupon.minimum_not_met'));
        }

        return $this->successResponse([
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => $coupon->value,
            ],
            'subtotal' => $request->subtotal,
            'discount' => $discount,
            'message' => __('messages.coupon.applied_successfully', ['discount' => $discount]),
        ]);
    }

    /**
     * Remove applied coupon
     * POST /api/v1/coupons/misapply
     */
    public function misapply(Request $request)
    {
        return $this->successResponse([
            'message' => __('messages.coupon.removed_successfully'),
        ]);
    }
}
