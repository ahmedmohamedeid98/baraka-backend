<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends ApiController
{
    /**
     * Get all categories
     * GET /api/v1/categories
     */
    public function index(Request $request)
    {
        $cacheKey = 'categories:all:' . app()->getLocale();

        $categories = Cache::remember($cacheKey, config('api.cache_ttl.categories'), function () use ($request) {
            $query = Category::with('children')
                ->active()
                ->root()
                ->orderBy('sort_order');

            return $query->get();
        });

        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Get single category
     * GET /api/v1/categories/{id}
     */
    public function show($id)
    {
        $category = Category::with('children')
            ->withCount('products')
            ->active()
            ->findOrFail($id);

        return $this->successResponse(new CategoryResource($category));
    }
}
