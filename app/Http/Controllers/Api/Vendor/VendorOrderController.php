<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VendorOrderController extends Controller
{
    /**
     * Display a listing of vendor orders
     */
    public function index(Request $request)
    {
        $vendor = $request->user();

        $query = VendorOrder::with(['order.user', 'items.product', 'items.variant'])
            ->where('vendor_id', $vendor->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => __('Vendor orders retrieved successfully'),
            'data' => \App\Http\Resources\VendorOrderResource::collection($orders),
        ]);
    }

    /**
     * Display the specified vendor order
     */
    public function show(Request $request, $id)
    {
        $vendor = $request->user();

        $order = VendorOrder::with(['order.address', 'items.product', 'items.variant'])
            ->where('vendor_id', $vendor->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Vendor order details retrieved successfully'),
            'data' => new \App\Http\Resources\VendorOrderResource($order),
        ]);
    }

    /**
     * Update vendor order status
     */
    public function updateStatus(Request $request, $id)
    {
        $vendor = $request->user();

        $order = VendorOrder::where('vendor_id', $vendor->id)->findOrFail($id);

        $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'ready', 'cancelled'])],
            'cancel_reason' => ['nullable', 'required_if:status,cancelled', 'string', 'max:500'],
        ]);

        // Can not cancel if already collected
        if($request->status === 'cancelled' && $order->status === 'collected') {
            return response()->json([
                'success' => false,
                'message' => __('Cannot cancel an order that has already been collected'),
            ], 422);
        }

        // Check if status transition is valid
        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['ready', 'cancelled'],
            'ready' => ['cancelled'],
            'collected' => [], // final state
            'cancelled' => [], // final state
        ];

        $currentStatus = $order->status;
        $newStatus = $request->status;

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid status transition from :from to :to', [
                    'from' => $currentStatus,
                    'to' => $newStatus,
                ]),
            ], 422);
        }

        // Update status
        $order->update(['status' => $newStatus]);
        
        // Save cancel reason if cancelling
        if ($newStatus === 'cancelled' && $request->cancel_reason) {
            $order->update(['cancel_reason' => $request->cancel_reason]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Vendor order status updated successfully'),
            'data' => new \App\Http\Resources\VendorOrderResource($order->fresh()),
        ]);
    }

    /**
     * Get vendor order statistics
     */
    public function statistics(Request $request)
    {
        $vendor = $request->user();

        $stats = [
            'pending' => VendorOrder::where('vendor_id', $vendor->id)->where('status', 'pending')->count(),
            'processing' => VendorOrder::where('vendor_id', $vendor->id)->where('status', 'processing')->count(),
            'ready' => VendorOrder::where('vendor_id', $vendor->id)->where('status', 'ready')->count(),
            'collected' => VendorOrder::where('vendor_id', $vendor->id)->where('status', 'collected')->count(),
            'cancelled' => VendorOrder::where('vendor_id', $vendor->id)->where('status', 'cancelled')->count(),
            'total_earnings' => VendorOrder::where('vendor_id', $vendor->id)
                ->whereHas('order', function($q) {
                    $q->where('status', 'delivered');
                })
                ->sum('subtotal'),
        ];

        return response()->json([
            'success' => true,
            'message' => __('Vendor order statistics retrieved successfully'),
            'data' => $stats,
        ]);
    }
}
