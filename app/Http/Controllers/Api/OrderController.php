<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\OrderResource;
use App\Jobs\SendOrderNotification;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends ApiController
{
    /**
     * Get user orders
     * GET /api/v1/orders
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 20), config('api.pagination.max_per_page'));

        $orders = $request->user()
            ->orders()
            ->with(['vendor', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($orders);
    }

    /**
     * Get single order
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()
            ->orders()
            ->with(['vendor', 'items.product', 'statusHistories'])
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
            'address_id' => 'required|exists:addresses,id',
            'coupon_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $cart = Cart::where('user_id', $user->id)->with('items.product.vendor')->first();

                if (!$cart || $cart->items->isEmpty()) {
                    return $this->errorResponse('Cart is empty');
                }

                // Group cart items by vendor
                $itemsByVendor = $cart->items->groupBy('product.vendor_id');

                if ($itemsByVendor->count() > 1) {
                    return $this->errorResponse('Please order from one vendor at a time');
                }

                $address = $user->addresses()->findOrFail($request->address_id);
                $vendorId = $itemsByVendor->keys()->first();
                $items = $itemsByVendor->first();

                // Calculate subtotal
                $subtotal = $items->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

                // Delivery fee
                $deliveryFee = $address->type === 'manual' && $address->area
                    ? $address->area->delivery_fee
                    : 0;

                // Apply coupon
                $discount = 0;
                $coupon = null;
                if ($request->coupon_code) {
                    $coupon = Coupon::where('code', $request->coupon_code)->active()->first();
                    
                    if (!$coupon) {
                        return $this->errorResponse('Invalid coupon code');
                    }

                    if (!$coupon->canBeUsedByUser($user)) {
                        return $this->errorResponse('Coupon cannot be used');
                    }

                    $discount = $coupon->calculateDiscount($subtotal);
                }

                $total = $subtotal + $deliveryFee - $discount;

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'vendor_id' => $vendorId,
                    'address_id' => $address->id,
                    'delivery_address' => $address->full_address,
                    'delivery_latitude' => $address->latitude,
                    'delivery_longitude' => $address->longitude,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'discount' => $discount,
                    'total' => $total,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'payment_method' => 'cod',
                    'notes' => $request->notes,
                ]);

                // Create order items
                foreach ($items as $cartItem) {
                    $order->items()->create([
                        'product_id' => $cartItem->product_id,
                        'product_name' => $cartItem->product->name,
                        'product_image' => $cartItem->product->first_image,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'subtotal' => $cartItem->price * $cartItem->quantity,
                    ]);

                    // Decrement stock
                    $cartItem->product->decrementStock($cartItem->quantity);
                }

                // Update coupon usage
                if ($coupon) {
                    $coupon->increment('usage_count');
                    $coupon->users()->syncWithoutDetaching([
                        $user->id => ['usage_count' => DB::raw('usage_count + 1')]
                    ]);
                }

                // Clear cart
                $cart->clear();

                // Create status history
                $order->statusHistories()->create([
                    'status' => 'pending',
                    'note' => 'Order placed',
                ]);

                // Dispatch notification job
                dispatch(new SendOrderNotification($order));

                return $this->successResponse(
                    new OrderResource($order->load(['vendor', 'items'])),
                    'Order placed successfully',
                    201
                );
            });

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
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
            return $this->errorResponse('Order cannot be cancelled at this stage');
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
            new OrderResource($order->load(['vendor', 'items'])),
            'Order cancelled successfully'
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
}
