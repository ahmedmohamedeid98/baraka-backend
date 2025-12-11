<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VendorController extends ApiController
{
    /**
     * Get all vendors
     * GET /api/v1/vendors
     */
    public function index(Request $request)
    {
        $cacheKey = 'vendors:all';

        $vendors = Cache::remember($cacheKey, config('api.cache_ttl.vendors'), function () {
            return Vendor::active()
                ->approved()
                ->orderBy('sort_order')
                ->get();
        });

        return $this->successResponse(VendorResource::collection($vendors));
    }

    /**
     * Get vendor with products
     * GET /api/v1/vendors/{id}
     */
    public function show($id)
    {
        $vendor = Vendor::with(['products' => function ($query) {
            $query->active()->inStock()->orderBy('sort_order')->take(50);
        }])->active()->approved()->findOrFail($id);

        return $this->successResponse(new VendorResource($vendor));
    }

    /**
     * Get vendor products
     * GET /api/v1/vendors/{id}/products
     */
    public function products($id, Request $request)
    {
        $vendor = Vendor::active()->approved()->findOrFail($id);
        
        $perPage = min($request->get('per_page', 20), config('api.pagination.max_per_page'));

        $products = $vendor->products()
            ->with('category')
            ->active()
            ->inStock()
            ->orderBy('sort_order')
            ->paginate($perPage);

        return $this->paginatedResponse($products);
    }
}
