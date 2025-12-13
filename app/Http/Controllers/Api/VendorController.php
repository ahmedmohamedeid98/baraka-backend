<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LightVendorResource;
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
        $perPage = min($request->get('per_page', 20), config('api.pagination.max_per_page'));

        $vendors = Vendor::withCount(['products' => fn($q) => $q->active()])
            ->active()
            ->approved()
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->paginate($perPage);

        return $this->paginatedResponse($vendors, LightVendorResource::class);
    }

    public function show($id)
    {
        $vendor = Vendor::withCount(['products' => fn($q) => $q->active()])
            ->with(['categories' => fn($q) => $q->orderBy('sort_order')])
            ->active()
            ->approved()
            ->findOrFail($id);
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
