<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            // 'product_image' => $this->product_image ? asset('storage/' . $this->product_image) : null,
            'quantity' => $this->quantity,
            'price' => (float) $this->price,
            'subtotal' => (float) $this->subtotal,
            
            // Include product details if loaded
            'product' => $this->when(
                $this->relationLoaded('product'),
                fn() => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'stock' => $this->product->stock,
                    'image' => $this->product->image,
                ]
            ),
            
            // Include variant details if loaded
            'variant' => $this->when(
                $this->relationLoaded('variant') && $this->variant,
                fn() => [
                    'id' => $this->variant->id,
                    'name' => $this->variant->name,
                    'stock' => $this->variant->stock,
                ]
            ),
        ];
    }
}
