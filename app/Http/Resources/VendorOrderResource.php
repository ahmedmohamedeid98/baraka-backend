<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->order_number,
            'vendor_id' => $this->vendor_id,
            'status' => $this->status,
            'cancel_reason' => $this->cancel_reason,
            
            // Financial Information
            'subtotal' => (float) $this->subtotal,
            
            // Vendor Information (if loaded)
            'vendor' => $this->when(
                $this->relationLoaded('vendor'),
                fn() => [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name_ar,
                    'phone' => $this->vendor->phone,
                    'logo' => $this->vendor->logo ? asset('storage/' . $this->vendor->logo) : null,
                ]
            ),
            
            // Order Items
            'items' => VendorOrderItemResource::collection($this->whenLoaded('items')),
            
            // Main Order Info (if loaded)
            // 'main_order' => $this->when(
            //     $this->relationLoaded('order'),
            //     fn() => [
            //         'status' => $this->order->status,
            //         'payment_status' => $this->order->payment_status,
            //         'delivery_address' => $this->order->delivery_address,
            //     ]
            // ),
            
            // Payment Status
            // 'payment_status' => $this->when(
            //     $this->relationLoaded('order'),
            //     fn() => $this->order->status === 'delivered' ? 'paid' : 'pending'
            // ),
            
            // Timestamps
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
