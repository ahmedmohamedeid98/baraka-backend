<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'description' => $this->description_ar,
            'pricing_type' => $this->pricing_type,
            'price' => (float) $this->price,
            'percentage_tiers' => $this->pricing_type === 'percentage' ? $this->percentage_tiers : null,
            'duration_days' => $this->duration_days,
            'duration_text' => $this->duration_text,
            'features' => $this->features,
            'max_products' => $this->max_products,
            'max_orders_per_month' => $this->max_orders_per_month,
            'is_featured' => $this->is_featured,
        ];
    }
}
