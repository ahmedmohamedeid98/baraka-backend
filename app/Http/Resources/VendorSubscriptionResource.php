<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package' => new PackageResource($this->whenLoaded('package')),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'auto_renew' => $this->auto_renew,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'price_paid' => (float) $this->price_paid,
            'pricing_type' => $this->pricing_type,
            'days_remaining' => $this->days_remaining,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
