<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'slug' => $this->slug,
            'description' => $this->description_ar,
            'unit' => $this->unit,
            'price' => (float) $this->price,
            'compare_price' => $this->compare_price ? (float) $this->compare_price : null,
            'has_discount' => $this->has_discount,
            'discount_percentage' => $this->discount_percentage,
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'images' => $this->images ?? [],
            'first_image' => $this->first_image,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            'orders_count' => $this->orders_count,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variations' => ProductVariationResource::collection($this->whenLoaded('variations')),
        ];
    }
}
