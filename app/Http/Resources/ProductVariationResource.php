<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'attributes' => $this->attributes,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'sku' => $this->sku,
            'is_active' => $this->is_active,
        ];
    }
}
