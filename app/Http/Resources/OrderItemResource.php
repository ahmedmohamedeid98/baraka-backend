<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            
            // Include product details if loaded
            'product' => $this->when(
                $this->relationLoaded('product'),
                fn() => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'image' => $this->product->first_image,
                ]
            ),
            
            // Include variant details if loaded
            'variant' => $this->when(
                $this->relationLoaded('variant') && $this->variant,
                fn() => [
                    'id' => $this->variant->id,
                    'name' => $this->variant->name,
                    'price' => (float) $this->variant->price,
                ]
            ),
            
            // Include vendor details
            'vendor' => $this->when(
                $this->relationLoaded('vendor') && $this->vendor,
                fn() => [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name,
                ]
            ),
        ];
    }
}
