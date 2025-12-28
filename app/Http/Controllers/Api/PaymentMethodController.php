<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaymentMethodController extends ApiController
{
    /**
     * Get all active payment methods
     * GET /api/v1/payment-methods
     */
    public function index(Request $request)
    {
        $cacheKey = 'payment_methods:active:' . app()->getLocale();

        $paymentMethods = Cache::remember($cacheKey, 3600, function () {
            return PaymentMethod::active()
                ->with('instructions')
                ->ordered()
                ->get();
        });

        return $this->successResponse(PaymentMethodResource::collection($paymentMethods));
    }
}
