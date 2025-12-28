<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
            'is_favorite' => $this->checkIsFavorite($request),
            'vendor' => new LightVendorResource($this->whenLoaded('vendor')),
            'category' => new LightCategoryResource($this->whenLoaded('category')),
        ];
    }

    /**
     * Check if the product is in user's favorites with caching
     */
    protected function checkIsFavorite(Request $request): bool
    {
        // Return false if user is not authenticated
        if (!$request->user()) {
            dd('not auth');
            return false;
        }

        $userId = $request->user()->id;
        $productId = $this->id;
        $cacheKey = "user:{$userId}:favorites";

        // Cache the user's favorite product IDs for 60 minutes
        $favoriteIds = Cache::remember($cacheKey, 3600, function () use ($userId) {
            return \App\Models\Favorite::where('user_id', $userId)
                ->pluck('product_id')
                ->toArray();
        });

        return in_array($productId, $favoriteIds);
    }
}
