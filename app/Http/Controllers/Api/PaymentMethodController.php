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
     * GET /api/v1/payment-methods?context=order|wallet_charge
     */
    public function index(Request $request)
    {
        $request->validate([
            'context' => 'nullable|in:order,wallet_charge',
        ]);

        $context = $request->get('context');
        $cacheKey = 'payment_methods:active:' . app()->getLocale() . ':' . ($context ?? 'all');

        $paymentMethods = Cache::remember($cacheKey, 3600, function () use ($context) {
            $query = PaymentMethod::active()
                ->with('instructions')
                ->ordered();

            if ($context) {
                $query->forContext($context);
            }

            return $query->get();
        });

        return $this->successResponse(PaymentMethodResource::collection($paymentMethods));
    }
}
