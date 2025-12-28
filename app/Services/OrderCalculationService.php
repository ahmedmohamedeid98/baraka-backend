<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Coupon;
use App\Models\Area;
use Illuminate\Support\Collection;

class OrderCalculationService
{
    /**
     * Calculate order totals from cart items
     * 
     * @param array $items Array of items with product_id, variant_id, quantity
     * @param string|null $couponCode
     * @param int|null $areaId
     * @return array
     */
    public function calculateOrder(array $items, ?string $couponCode = null, ?int $areaId = null): array
    {
        $validatedItems = [];
        $subtotal = 0;

        // Validate and calculate each item
        foreach ($items as $item) {
            $validated = $this->validateAndCalculateItem($item);
            
            if (!$validated) {
                continue; // Skip invalid items
            }

            $validatedItems[] = $validated;
            $subtotal += $validated['total'];
        }

        // Calculate delivery fee
        $deliveryFee = 0;
        if ($areaId) {
            $area = Area::find($areaId);
            $deliveryFee = $area ? (float) $area->delivery_fee : 0;
        }

        // Apply coupon
        $discount = 0;
        $couponId = null;
        if ($couponCode) {
            $couponResult = $this->applyCoupon($couponCode, $subtotal, null);
            $discount = $couponResult['discount'];
            $couponId = $couponResult['coupon_id'];
        }

        // Calculate total
        $total = $subtotal + $deliveryFee - $discount;
        $total = max(0, $total); // Ensure total is not negative

        return [
            'items' => $validatedItems,
            'order_details' => [
                'subtotal' => round($subtotal, 2),
                'delivery_fee' => round($deliveryFee, 2),
                'discount' => round($discount, 2),
                'total' => round($total, 2),
            ],
            'coupon_id' => $couponId,
        ];
    }

    /**
     * Validate and calculate single item
     * 
     * @param array $item
     * @return array|null
     */
    protected function validateAndCalculateItem(array $item): ?array
    {
        $productId = $item['product_id'] ?? null;
        $variantId = $item['variant_id'] ?? null;
        $quantity = $item['quantity'] ?? 1;

        // Validate product exists and is active
        $product = Product::active()->with('vendor')->find($productId);
        
        if (!$product) {
            return null;
        }

        $price = $product->price;
        $availableStock = $product->stock;
        $variantName = null;

        // Handle variant if provided
        if ($variantId) {
            $variant = ProductVariation::where('product_id', $productId)
                ->where('id', $variantId)
                ->first();
            
            if (!$variant) {
                return null; // Invalid variant
            }

            $price = $variant->price;
            $availableStock = $variant->stock;
            $variantName = $variant->name;
        }

        // Validate stock
        if ($availableStock < $quantity) {
            $quantity = $availableStock; // Adjust to available stock
        }

        if ($quantity <= 0) {
            return null; // Out of stock
        }

        $total = $price * $quantity;

        return [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => round((float) $price, 2),
            'total' => round($total, 2),
            'product_name' => $product->name,
            'variant_name' => $variantName,
            'vendor_id' => $product->vendor_id,
        ];
    }

    /**
     * Apply coupon and calculate discount
     * 
     * @param string $code
     * @param float $subtotal
     * @param int|null $vendorId
     * @return array
     */
    protected function applyCoupon(string $code, float $subtotal, ?int $vendorId = null): array
    {
        $coupon = Coupon::where('code', $code)
            ->active()
            ->valid()
            ->first();

        if (!$coupon) {
            return ['discount' => 0, 'coupon_id' => null];
        }

        // Check if coupon is for specific vendor
        if ($coupon->vendor_id && $coupon->vendor_id != $vendorId) {
            return ['discount' => 0, 'coupon_id' => null];
        }

        // Check minimum order amount
        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return ['discount' => 0, 'coupon_id' => null];
        }

        // Calculate discount
        $discount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discount = ($subtotal * $coupon->discount_value) / 100;
            
            // Apply max discount cap if set
            if ($coupon->max_discount && $discount > $coupon->max_discount) {
                $discount = $coupon->max_discount;
            }
        } else {
            // Fixed discount
            $discount = $coupon->discount_value;
        }

        // Ensure discount doesn't exceed subtotal
        $discount = min($discount, $subtotal);

        return [
            'discount' => round($discount, 2),
            'coupon_id' => $coupon->id,
        ];
    }
}
