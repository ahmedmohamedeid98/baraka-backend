<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LightProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'slug' => $this->slug,
            'unit' => $this->unit,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'has_discount' => $this->has_discount,
            'discount_percentage' => $this->discount_percentage,
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'first_image' => $this->first_image,
            'is_featured' => $this->is_featured,
            'vendor' => new LightVendorResource($this->whenLoaded('vendor')),
            'category' => new LightCategoryResource($this->whenLoaded('category')),
        ];
    }
}
