<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\AreaResource;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends ApiController
{
    /**
     * Get all active areas
     * GET /api/v1/areas
     */
    public function index(Request $request)
    {
        $areas = Area::active()
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get();

        return $this->successResponse(AreaResource::collection($areas));
    }
}
