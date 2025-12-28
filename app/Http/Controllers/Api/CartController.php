<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends ApiController
{
    /**
     * Get user cart
     * GET /api/v1/cart
     */
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->load(['items.product.vendor']);

        return $this->successResponse(new CartResource($cart));
    }

    /**
     * Add item to cart
     * POST /api/v1/cart/items
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'sometimes|integer|min:1|max:100',
        ]);

        $product = Product::active()->inStock()->findOrFail($request->product_id);
        $quantity = $request->get('quantity', 1);

        if ($product->stock < $quantity) {
            return $this->errorResponse(__('messages.cart.insufficient_stock'));
        }

        $cart = $this->getOrCreateCart($request);
        $cart->addItem($product, $quantity);

        $cart->load(['items.product.vendor']);

        return $this->successResponse(
            new CartResource($cart),
            __('messages.cart.item_added')
        );
    }

    /**
     * Update cart item quantity
     * PUT /api/v1/cart/items/{id}
     */
    public function update(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);

        if ($item->product->stock < $request->quantity) {
            return $this->errorResponse(__('messages.cart.insufficient_stock'));
        }

        $cart->updateItemQuantity($itemId, $request->quantity);
        $cart->load(['items.product.vendor']);

        return $this->successResponse(
            new CartResource($cart),
            __('messages.cart.item_updated')
        );
    }

    /**
     * Remove item from cart
     * DELETE /api/v1/cart/items/{id}
     */
    public function destroy(Request $request, $itemId)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->removeItem($itemId);
        $cart->load(['items.product.vendor']);

        return $this->successResponse(
            new CartResource($cart),
            __('messages.cart.item_removed')
        );
    }

    /**
     * Clear cart
     * DELETE /api/v1/cart
     */
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        $cart->clear();

        return $this->successResponse(null, __('messages.cart.cleared'));
    }

    /**
     * Get or create cart for user
     */
    protected function getOrCreateCart(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id]);
        }

        // Guest cart (requires session)
        $sessionId = $request->session()->getId();
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }
}
