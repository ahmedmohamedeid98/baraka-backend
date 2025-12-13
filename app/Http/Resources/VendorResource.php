<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name_ar,
            'description' => $this->description,
            'logo' => $this->logo,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_featured' => $this->is_featured,
            'active_products_count' => $this->when(isset($this->products_count), $this->products_count),
            'categories' => LightCategoryResource::collection($this->categories),
        ];
    }
}
