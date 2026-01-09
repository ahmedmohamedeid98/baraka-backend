<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WalletChargeRequestResource;
use App\Models\WalletChargeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WalletChargeRequestController extends ApiController
{
    /**
     * Get charge requests history
     * GET /api/v1/{guard}/wallet/charge-requests
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $perPage = min($request->get('per_page', 20), 50);
        
        $query = WalletChargeRequest::where('wallet_id', $wallet->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->with(['reviewedBy', 'transaction'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($requests, WalletChargeRequestResource::class);
    }

    /**
     * Get single charge request
     * GET /api/v1/{guard}/wallet/charge-requests/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $chargeRequest = WalletChargeRequest::where('wallet_id', $wallet->id)
            ->with(['reviewedBy', 'transaction'])
            ->findOrFail($id);

        return $this->successResponse([
            'request' => new WalletChargeRequestResource($chargeRequest),
        ]);
    }

    /**
     * Submit new charge request
     * POST /api/v1/{guard}/wallet/charge-requests
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:50000',
            'payment_method' => 'required|in:vodafone_cash,instapay,bank_transfer,other',
            'payment_screenshot' => 'required|image|mimes:jpeg,jpg,png,gif|max:5120', // 5MB max
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        // Upload screenshot
        $screenshotPath = $request->file('payment_screenshot')->store('wallet-charge-requests', 'public');

        // Create charge request
        $chargeRequest = WalletChargeRequest::create([
            'wallet_id' => $wallet->id,
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_screenshot' => $screenshotPath,
            'payment_reference' => $request->payment_reference,
            'notes' => $request->notes,
            'status' => WalletChargeRequest::STATUS_PENDING,
        ]);

        return $this->successResponse([
            'request' => new WalletChargeRequestResource($chargeRequest),
            'message' => 'تم إرسال الطلب بنجاح. سيتم مراجعته من قبل الإدارة',
        ], 'تم إرسال الطلب بنجاح', 201);
    }

    /**
     * Update rejected charge request (resubmit)
     * PUT /api/v1/{guard}/wallet/charge-requests/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $chargeRequest = WalletChargeRequest::where('wallet_id', $wallet->id)
            ->where('status', WalletChargeRequest::STATUS_REJECTED)
            ->findOrFail($id);

        $request->validate([
            'amount' => 'nullable|numeric|min:10|max:50000',
            'payment_screenshot' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:5120',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Create new request as resubmission
        $data = [
            'wallet_id' => $wallet->id,
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'amount' => $request->amount ?? $chargeRequest->amount,
            'payment_method' => $chargeRequest->payment_method,
            'payment_reference' => $request->payment_reference ?? $chargeRequest->payment_reference,
            'notes' => $request->notes ?? $chargeRequest->notes,
            'status' => WalletChargeRequest::STATUS_PENDING,
            'is_resubmission' => true,
            'original_request_id' => $chargeRequest->id,
        ];

        // Handle new screenshot if provided
        if ($request->hasFile('payment_screenshot')) {
            $data['payment_screenshot'] = $request->file('payment_screenshot')->store('wallet-charge-requests', 'public');
        } else {
            $data['payment_screenshot'] = $chargeRequest->payment_screenshot;
        }

        $newRequest = WalletChargeRequest::create($data);

        return $this->successResponse([
            'request' => new WalletChargeRequestResource($newRequest),
            'message' => 'تم إعادة إرسال الطلب بنجاح',
        ], 'تم إعادة إرسال الطلب بنجاح');
    }

    /**
     * Delete pending charge request
     * DELETE /api/v1/{guard}/wallet/charge-requests/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $chargeRequest = WalletChargeRequest::where('wallet_id', $wallet->id)
            ->where('status', WalletChargeRequest::STATUS_PENDING)
            ->findOrFail($id);

        // Delete screenshot
        if ($chargeRequest->payment_screenshot) {
            Storage::disk('public')->delete($chargeRequest->payment_screenshot);
        }

        $chargeRequest->delete();

        return $this->successResponse(null, 'تم حذف الطلب بنجاح');
    }

    /**
     * Get charge request statistics
     * GET /api/v1/{guard}/wallet/charge-requests/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        $stats = [
            'pending_count' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->pending()
                ->count(),
            'pending_amount' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->pending()
                ->sum('amount'),
            'approved_count' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->approved()
                ->count(),
            'approved_amount' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->approved()
                ->sum('amount'),
            'rejected_count' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->rejected()
                ->count(),
            'rejected_amount' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->rejected()
                ->sum('amount'),
            'total_charged' => WalletChargeRequest::where('wallet_id', $wallet->id)
                ->approved()
                ->sum('amount'),
        ];

        return $this->successResponse($stats);
    }
}
