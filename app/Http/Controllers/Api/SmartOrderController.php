<?php

namespace App\Http\Controllers\Api;

use App\Models\SmartOrder;
use App\Services\SmartOrderService;
use Illuminate\Http\Request;

class SmartOrderController extends ApiController
{
    protected $smartOrderService;

    public function __construct(SmartOrderService $smartOrderService)
    {
        $this->smartOrderService = $smartOrderService;
    }

    /**
     * Parse text order using AI
     * POST /api/v1/smart-order/parse
     */
    public function parse(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:1|max:5000',
            'name' => 'sometimes|string|max:255',
        ]);

        $result = $this->smartOrderService->parseTextOrder($request->text);

        // Save if requested
        if (true) {
            $smartOrder = SmartOrder::create([
                'user_id' => $request->user()->id,
                'original_text' => $request->text,
                'parsed_items' => $result['items'],
                'total_price' => $result['totalPrice'],
                'total_items' => $result['totalItems'],
                'name' => $request->name,
            ]);

            return $this->successResponse([
                'smart_order_id' => $smartOrder->id,
                ...$result,
            ], 'تم تحليل الطلب وحفظه بنجاح');
        }

        return $this->successResponse($result, 'تم تحليل الطلب بنجاح');
    }

    /**
     * Get user's saved smart orders
     * GET /api/v1/smart-order/history
     */
    public function history(Request $request)
    {
        $perPage = min($request->get('per_page', 20), 50);

        $orders = SmartOrder::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse([
            'data' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Get specific smart order by ID
     * GET /api/v1/smart-order/{id}
     */
    public function show(Request $request, $id)
    {
        $order = SmartOrder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return $this->successResponse([
            'id' => $order->id,
            'name' => $order->name,
            'original_text' => $order->original_text,
            'items' => $order->parsed_items,
            'totalItems' => $order->total_items,
            'totalPrice' => (float) $order->total_price,
            'currency' => 'EGP',
            'is_favorite' => $order->is_favorite,
            'created_at' => $order->created_at,
        ]);
    }

    /**
     * Update smart order (name or favorite)
     * PUT /api/v1/smart-order/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_favorite' => 'sometimes|boolean',
        ]);

        $order = SmartOrder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $order->update($request->only(['name', 'is_favorite']));

        return $this->successResponse($order, 'تم تحديث الطلب بنجاح');
    }

    /**
     * Delete smart order
     * DELETE /api/v1/smart-order/{id}
     */
    public function destroy(Request $request, $id)
    {
        $order = SmartOrder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $order->delete();

        return $this->successResponse(null, 'تم حذف الطلب بنجاح');
    }

    /**
     * Reuse smart order (get the parsed items again)
     * POST /api/v1/smart-order/{id}/reuse
     */
    public function reuse(Request $request, $id)
    {
        $order = SmartOrder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Re-parse to get fresh prices and availability
        $result = $this->smartOrderService->parseTextOrder($order->original_text);

        return $this->successResponse($result, 'تم استرجاع الطلب بنجاح');
    }

    /**
     * Get favorites
     * GET /api/v1/smart-order/favorites
     */
    public function favorites(Request $request)
    {
        $perPage = min($request->get('per_page', 20), 50);

        $orders = SmartOrder::where('user_id', $request->user()->id)
            ->where('is_favorite', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse([
            'data' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }
}