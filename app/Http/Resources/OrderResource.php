<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'payment_rejection_reason' => $this->payment_rejection_reason,
            // 'payment_screenshot' => $this->payment_screenshot ? asset('storage/' . $this->payment_screenshot) : null,
            
            // Delivery Information
            'delivery_address' => $this->delivery_address,
            'delivery_latitude' => $this->delivery_latitude,
            'delivery_longitude' => $this->delivery_longitude,
            
            // Financial Information
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'coupon_code' => $this->coupon_code,
            
            // Additional Info
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            
            // Relationships
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            // 'vendor_orders' => VendorOrderResource::collection($this->whenLoaded('vendorOrders')),
            'status_histories' => $this->when(
                $this->relationLoaded('statusHistories'),
                fn() => $this->statusHistories->map(fn($history) => [
                    'status' => $history->status,
                    'note' => $history->note,
                    'created_at' => $history->created_at->toIso8601String(),
                ])
            ),
            
            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }
}
