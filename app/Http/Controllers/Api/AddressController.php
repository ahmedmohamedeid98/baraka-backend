<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends ApiController
{
    /**
     * Get all user addresses
     * GET /api/v1/addresses
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->with('area')->get();

        return $this->successResponse(AddressResource::collection($addresses));
    }

    /**
     * Create new address
     * POST /api/v1/addresses
     */
    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|in:map,manual',
            'note' => 'nullable|string|max:500',
            'is_default' => 'sometimes|boolean',
        ];

        if ($request->type === 'map') {
            $rules['latitude'] = 'required|numeric';
            $rules['longitude'] = 'required|numeric';
            $rules['formatted_address'] = 'required|string';
        } else {
            $rules['area_id'] = 'required|exists:areas,id';
            $rules['street'] = 'required|string|max:255';
        }

        $request->validate($rules);

        // If set as default, unset other defaults
        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($request->all());

        return $this->successResponse(
            new AddressResource($address),
            'Address created successfully',
            201
        );
    }

    /**
     * Update address
     * PUT /api/v1/addresses/{id}
     */
    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $rules = [
            'type' => 'sometimes|in:map,manual',
            'note' => 'nullable|string|max:500',
            'is_default' => 'sometimes|boolean',
        ];

        if ($request->type === 'map' || $address->type === 'map') {
            $rules['latitude'] = 'sometimes|numeric';
            $rules['longitude'] = 'sometimes|numeric';
            $rules['formatted_address'] = 'sometimes|string';
        } else {
            $rules['area_id'] = 'sometimes|exists:areas,id';
            $rules['street'] = 'sometimes|string|max:255';
        }

        $request->validate($rules);

        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($request->all());

        return $this->successResponse(
            new AddressResource($address),
            'Address updated successfully'
        );
    }

    /**
     * Delete address
     * DELETE /api/v1/addresses/{id}
     */
    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return $this->successResponse(null, 'Address deleted successfully');
    }
}
