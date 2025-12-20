<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'formatted_address' => $this->formatted_address,
            'area_id' => $this->area_id,
            'area' => $this->whenLoaded('area', function () {
                return [
                    'id' => $this->area->id,
                    'name' => $this->area->name,
                    'name_ar' => $this->area->name_ar,
                    'name_en' => $this->area->name_en,
                    'delivery_fee' => (float) $this->area->delivery_fee,
                ];
            }),
            'distance_from_store_km' => $this->when(
                $this->type === 'map' && $this->latitude && $this->longitude,
                function () {
                    // You can set your main store location in config or env
                    // For now using example coordinates - update these to your actual store location
                    $mainStoreLat = config('app.main_store_latitude');
                    $mainStoreLon = config('app.main_store_longitude');
                    
                    return round(
                        \App\Models\Area::getDistanceFromMainLocation(
                            $this->latitude,
                            $this->longitude,
                            $mainStoreLat,
                            $mainStoreLon
                        ),
                        2
                    );
                }
            ),
            'street' => $this->street,
            'note' => $this->note,
            'full_address' => $this->full_address,
            'is_default' => $this->is_default,
        ];
    }
}
