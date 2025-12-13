<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LightProductResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends ApiController
{
    /**
     * Get all products with filters
     * GET /api/v1/products
     */
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 20), config('api.pagination.max_per_page'));

        $query = Product::with(['vendor', 'category'])
            ->active()
            ->inStock();

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('featured') && $request->boolean('featured')) {
            $query->featured();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('description_ar', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['price', 'created_at', 'views_count', 'orders_count'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($perPage);

        return $this->paginatedResponse($products, LightProductResource::class);
    }

    /**
     * Get single product
     * GET /api/v1/products/{id}
     */
    public function show($id)
    {
        $product = Product::with(['vendor', 'category', 'variations'])
            ->active()
            ->findOrFail($id);

        // Increment views
        $product->incrementViews();

        return $this->successResponse(new ProductResource($product));
    }
}
