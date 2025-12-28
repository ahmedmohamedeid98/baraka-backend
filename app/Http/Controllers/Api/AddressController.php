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

        $data = $request->all();
        
        // If set as default, unset other defaults
        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        } elseif (!$request->has('is_default') && $request->user()->addresses()->count() === 0) {
            // If this is the first address and is_default not specified, make it default
            $data['is_default'] = true;
        }

        // Auto-detect area from coordinates if type is map
        if ($request->type === 'map' && $request->latitude && $request->longitude) {
            $detectedArea = \App\Models\Area::detectFromCoordinates(
                $request->latitude,
                $request->longitude
            );
            
            if ($detectedArea) {
                $data['area_id'] = $detectedArea->id;
            }
        }

        $address = $request->user()->addresses()->create($data);

        return $this->successResponse(
            new AddressResource($address),
            __('messages.address.created_successfully'),
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

        $data = $request->all();
        
        // If set as default, unset other defaults (excluding current address)
        if ($request->is_default) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        // Auto-detect area from coordinates if type is map and coordinates provided
        if (($request->type === 'map' || $address->type === 'map') && $request->has('latitude') && $request->has('longitude')) {
            $detectedArea = \App\Models\Area::detectFromCoordinates(
                $request->latitude,
                $request->longitude
            );
            
            if ($detectedArea) {
                $data['area_id'] = $detectedArea->id;
            }
        }

        $address->update($data);

        return $this->successResponse(
            new AddressResource($address),
            __('messages.address.updated_successfully')
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

        return $this->successResponse(null, __('messages.address.deleted_successfully'));
    }
}
