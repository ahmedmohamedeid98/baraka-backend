<?php

namespace App\Http\Controllers\Api;

use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;

class VendorCategoryController extends ApiController
{
    /**
     * Get all categories used by a specific vendor
     * 
     * @param int $vendorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        
        $categories = $vendor->categories()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Vendor categories retrieved successfully'
        );
    }
}
