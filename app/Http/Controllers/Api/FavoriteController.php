<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LightProductResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FavoriteController extends ApiController
{
    /**
     * Get all favorite products
     * GET /api/v1/favorites
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 20), config('api.pagination.max_per_page'));

        $favorites = $request->user()
            ->favoriteProducts()
            ->with(['vendor', 'category'])
            ->active()
            ->paginate($perPage);

        return $this->paginatedResponse($favorites, LightProductResource::class);
    }

    /**
     * Add product to favorites
     * POST /api/v1/favorites
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::active()->findOrFail($request->product_id);

        // Check if already favorited
        $exists = $request->user()->favorites()->where('product_id', $product->id)->exists();

        if ($exists) {
            return $this->errorResponse(__('messages.favorite.already_exists'), 400);
        }

        $request->user()->favorites()->create([
            'product_id' => $product->id,
        ]);

        // Clear cache
        $this->clearFavoritesCache($request->user()->id);

        return $this->successResponse(
            // new ProductResource($product->load(['vendor', 'category'])),
            __('messages.favorite.added'),
            201
        );
    }

    /**
     * Remove product from favorites
     * DELETE /api/v1/favorites/{product_id}
     */
    public function destroy(Request $request, $productId)
    {
        $favorite = $request->user()->favorites()->where('product_id', $productId)->first();

        if (!$favorite) {
            return $this->errorResponse(__('messages.favorite.not_found'), 404);
        }

        $favorite->delete();

        // Clear cache
        $this->clearFavoritesCache($request->user()->id);

        return $this->successResponse(null, __('messages.favorite.removed'));
    }

    /**
     * Toggle product favorite status
     * POST /api/v1/favorites/toggle
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::active()->findOrFail($request->product_id);
        $favorite = $request->user()->favorites()->where('product_id', $product->id)->first();

        if ($favorite) {
            // Remove from favorites
            $favorite->delete();
            $this->clearFavoritesCache($request->user()->id);
            
            return $this->successResponse([
                'is_favorite' => false,
            ], __('messages.favorite.removed'));
        } else {
            // Add to favorites
            $request->user()->favorites()->create([
                'product_id' => $product->id,
            ]);
            $this->clearFavoritesCache($request->user()->id);
            
            return $this->successResponse([
                'is_favorite' => true,
            ], __('messages.favorite.added'));
        }
    }

    /**
     * Check if product is favorited
     * GET /api/v1/favorites/check/{product_id}
     */
    public function check(Request $request, $productId)
    {
        $cacheKey = "user:{$request->user()->id}:favorites";
        
        // Use cached favorites list
        $favoriteIds = Cache::remember($cacheKey, 3600, function () use ($request) {
            return $request->user()->favorites()->pluck('product_id')->toArray();
        });

        $isFavorite = in_array($productId, $favoriteIds);

        return $this->successResponse([
            'is_favorite' => $isFavorite,
        ]);
    }

    /**
     * Clear all favorites for the user
     * DELETE /api/v1/favorites
     */
    public function clearAll(Request $request)
    {
        $count = $request->user()->favorites()->count();
        
        if ($count === 0) {
            return $this->errorResponse(__('messages.favorite.no_favorites'), 404);
        }

        $request->user()->favorites()->delete();

        // Clear cache
        $this->clearFavoritesCache($request->user()->id);

        return $this->successResponse(
            ['count' => $count],
            __('messages.favorite.cleared_all')
        );
    }

    /**
     * Clear user's favorites cache
     */
    protected function clearFavoritesCache($userId)
    {
        Cache::forget("user:{$userId}:favorites");
    }
}
