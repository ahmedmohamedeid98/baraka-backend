<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
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
            'name' => $this->name,
            'delivery_fee' => (float) $this->delivery_fee,
            'has_coverage' => !empty($this->center_points),
            'coverage_zones_count' => is_array($this->center_points) ? count($this->center_points) : 0,
        ];
    }
}
