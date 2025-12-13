<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends ApiController
{
    /**
     * Get all active packages
     * GET /api/v1/packages
     */
    public function index(Request $request)
    {
        $packages = Package::active()
            ->orderBy('is_featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return $this->successResponse(PackageResource::collection($packages));
    }

    /**
     * Get package details
     * GET /api/v1/packages/{id}
     */
    public function show($id)
    {
        $package = Package::active()->findOrFail($id);

        return $this->successResponse(new PackageResource($package));
    }
}
