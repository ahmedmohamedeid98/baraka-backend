<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\OrderResource;
use App\Jobs\SendOrderNotification;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\OrderCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends ApiController
{
    protected OrderCalculationService $orderCalculation;

    public function __construct(OrderCalculationService $orderCalculation)
    {
        $this->orderCalculation = $orderCalculation;
    }
    /**
     * Get user orders
     * GET /api/v1/orders
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 20), config('api.pagination.max_per_page'));

        $orders = $request->user()
            ->orders()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse(OrderResource::collection($orders));
    }

    /**
     * Get single order
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()
            ->orders()
            ->with(['items.product', 'items.variant', 'items.vendor', 'statusHistories'])
            ->findOrFail($id);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * Create order (checkout)
     * POST /api/v1/checkout
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'delivery_address_id' => 'required|exists:addresses,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'coupon_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'payment_screenshot' => 'nullable|string', // base64 image
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $address = $user->addresses()->findOrFail($request->delivery_address_id);
                
                // Get area for delivery fee
                $areaId = $address->area_id ?? null;

                // Backend recalculates everything - never trust mobile data
                $calculation = $this->orderCalculation->calculateOrder(
                    $request->items,
                    $request->coupon_code,
                    $areaId
                );

                if (empty($calculation['items'])) {
                    return $this->errorResponse(__('messages.cart.empty'));
                }

                // Validate payment method
                $paymentMethod = PaymentMethod::active()->findOrFail($request->payment_method_id);
                
                // Check if payment screenshot is required
                if ($paymentMethod->required_transaction_screenshot && !$request->payment_screenshot) {
                    return $this->errorResponse(__('messages.order.payment_screenshot_required'));
                }

                // Store payment screenshot if provided
                $paymentScreenshotPath = null;
                if ($request->payment_screenshot) {
                    $paymentScreenshotPath = $this->savePaymentScreenshot($request->payment_screenshot, $user->id);
                }

                // Apply payment method discount
                $paymentDiscount = 0;
                if ($paymentMethod->discount_amount > 0) {
                    if ($paymentMethod->discount_type === 'percentage') {
                        $paymentDiscount = ($calculation['order_details']['subtotal'] * $paymentMethod->discount_amount) / 100;
                    } else {
                        $paymentDiscount = $paymentMethod->discount_amount;
                    }
                }

                // Recalculate total with payment discount
                $finalTotal = $calculation['order_details']['total'] - $paymentDiscount;
                $finalTotal = max(0, $finalTotal);

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'vendor_id' => null, // Multi-vendor orders don't belong to single vendor
                    'address_id' => $address->id,
                    'status' => 'pending',
                    'delivery_address' => $address->full_address,
                    'delivery_latitude' => $address->latitude,
                    'delivery_longitude' => $address->longitude,
                    'subtotal' => $calculation['order_details']['subtotal'],
                    'delivery_fee' => $calculation['order_details']['delivery_fee'],
                    'discount' => $calculation['order_details']['discount'] + $paymentDiscount,
                    'total' => $finalTotal,
                    'coupon_id' => $calculation['coupon_id'],
                    'coupon_code' => $request->coupon_code,
                    'payment_method' => $paymentMethod->code,
                    'payment_screenshot' => $paymentScreenshotPath,
                    'payment_status' => $paymentScreenshotPath ? 'pending_verification' : 'pending',
                    'notes' => $request->notes,
                ]);

                // Create order items
                foreach ($calculation['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'product_name' => $item['product_name'],
                        'variant_name' => $item['variant_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['total'],
                    ]);

                    // Decrement stock
                    if ($item['variant_id']) {
                        $variant = \App\Models\ProductVariation::find($item['variant_id']);
                        $variant?->decrement('stock', $item['quantity']);
                    } else {
                        $product = \App\Models\Product::find($item['product_id']);
                        $product?->decrement('stock', $item['quantity']);
                    }
                }

                // Update coupon usage
                if ($calculation['coupon_id']) {
                    $coupon = Coupon::find($calculation['coupon_id']);
                    if ($coupon) {
                        $coupon->increment('usage_count');
                        
                        // Check if user already has a pivot record
                        $existingPivot = $coupon->users()->where('user_id', $user->id)->first();
                        if ($existingPivot) {
                            $coupon->users()->updateExistingPivot($user->id, [
                                'usage_count' => DB::raw('usage_count + 1')
                            ]);
                        } else {
                            $coupon->users()->attach($user->id, ['usage_count' => 1]);
                        }
                    }
                }

                // Clear cart
                $cart = Cart::where('user_id', $user->id)->first();
                $cart?->clear();

                // Create status history
                $order->statusHistories()->create([
                    'status' => 'pending',
                    'note' => 'Order placed',
                ]);

                // Dispatch notification job
                dispatch(new SendOrderNotification($order));

                return $this->successResponse([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'order_details' => [
                        'subtotal' => (float) $order->subtotal,
                        'delivery_fee' => (float) $order->delivery_fee,
                        'discount' => (float) $order->discount,
                        'total' => (float) $order->total,
                    ],
                ], __('messages.order.created_successfully'), 201);
            });

        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.order.creation_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Save payment screenshot to storage
     * 
     * @param string $base64Image
     * @param int $userId
     * @return string
     */
    protected function savePaymentScreenshot(string $base64Image, int $userId): string
    {
        // Remove base64 header if present
        if (strpos($base64Image, 'data:image') === 0) {
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        }

        $imageData = base64_decode($base64Image);
        $filename = 'payment_' . $userId . '_' . time() . '.jpg';
        $path = 'payment_screenshots/' . $filename;

        Storage::disk('r2')->put($path, $imageData);

        return $path;
    }

    /**
     * Cancel order
     * POST /api/v1/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $order = $request->user()->orders()->findOrFail($id);

        if (!$order->canBeCancelled()) {
            return $this->errorResponse(__('messages.order.cannot_cancel'));
        }

        $order->update([
            'cancellation_reason' => $request->reason,
        ]);

        $order->updateStatus('cancelled', 'Cancelled by customer', $request->user());

        // Restore stock
        foreach ($order->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        return $this->successResponse(
            new OrderResource($order->load(['vendorOrders.vendor', 'items'])),
            __('messages.order.cancelled_successfully')
        );
    }

    /**
     * Get order tracking
     * GET /api/v1/orders/{id}/tracking
     */
    public function tracking(Request $request, $id)
    {
        // Can be accessed without auth for public tracking link
        $order = Order::with(['statusHistories' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($id);

        return $this->successResponse([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'created_at' => $order->created_at->toIso8601String(),
            'timeline' => $order->statusHistories->map(function ($history) {
                return [
                    'status' => $history->status,
                    'note' => $history->note,
                    'created_at' => $history->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Update payment method and/or upload payment screenshot
     * POST /api/v1/orders/{id}/payment
     */
    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_screenshot' => 'nullable|string', // base64 image
        ]);

        $order = $request->user()->orders()->findOrFail($id);

        // Only allow payment update for pending orders
        if ($order->status !== 'pending') {
            return $this->errorResponse(__('messages.order.cannot_update_payment'));
        }

        // Validate payment method
        $paymentMethod = PaymentMethod::active()->findOrFail($request->payment_method_id);

        // Check if payment screenshot is required
        if ($paymentMethod->required_transaction_screenshot && !$request->payment_screenshot) {
            return $this->errorResponse(__('messages.order.payment_screenshot_required'));
        }

        $updateData = [
            'payment_method' => $paymentMethod->code,
        ];

        // Store payment screenshot if provided
        if ($request->payment_screenshot) {
            // Delete old screenshot if exists
            if ($order->payment_screenshot) {
                Storage::disk('r2')->delete($order->payment_screenshot);
            }

            $updateData['payment_screenshot'] = $this->savePaymentScreenshot(
                $request->payment_screenshot,
                $request->user()->id
            );
            $updateData['payment_status'] = 'pending_verification';
        }
        
        $updateData['payment_rejection_reason'] = null;
        if($paymentMethod->code === 'cash'){
            $updateData['payment_status'] = 'pending';
        }

        $order->update($updateData);

        // Add status history note
        $order->statusHistories()->create([
            'status' => $order->status,
            'note' => 'Payment method updated to ' . $paymentMethod->code,
            'created_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new OrderResource($order->fresh(['items.product', 'statusHistories'])),
            __('messages.order.payment_updated')
        );
    }
}
