<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TransactionResource;
use App\Http\Resources\VendorSubscriptionResource;
use App\Http\Resources\WalletResource;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;

class VendorSubscriptionController extends ApiController
{
    /**
     * Get vendor wallet
     * GET /api/v1/vendor/wallet
     */
    public function index(Request $request)
    {
        $vendor = $request->user();
        $wallet = $vendor->getOrCreateWallet();

        return $this->successResponse([
            'wallet' => new WalletResource($wallet),
            'active_subscription' => $vendor->activeSubscription 
                ? new VendorSubscriptionResource($vendor->activeSubscription->load('package')) 
                : null,
        ]);
    }

    /**
     * Get wallet transactions
     * GET /api/v1/vendor/wallet/transactions
     */
    public function transactions(Request $request)
    {
        $vendor = $request->user();
        $wallet = $vendor->getOrCreateWallet();

        $perPage = min($request->get('per_page', 20), 50);

        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($transactions, TransactionResource::class);
    }

    /**
     * Subscribe to a package
     * POST /api/v1/vendor/wallet/subscribe
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $vendor = $request->user();
        $wallet = $vendor->getOrCreateWallet();
        $package = Package::active()->findOrFail($request->package_id);

        // Check if vendor already has active subscription - expire it first
        $existingSubscription = $vendor->subscriptions()->active()->latest()->first();
        if ($existingSubscription) {
            $existingSubscription->expire();
        }

        // For fixed pricing, check wallet balance
        if ($package->pricing_type === 'fixed' && !$wallet->hasSufficientBalance($package->price)) {
            return $this->errorResponse('رصيد المحفظة غير كافي', 400);
        }

        // Create subscription
        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'package_id' => $package->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($package->duration_days),
            'auto_renew' => true,
            'status' => VendorSubscription::STATUS_ACTIVE,
            'price_paid' => $package->price,
            'pricing_type' => $package->pricing_type,
            'renewed_from' => $existingSubscription?->id,
        ]);

        // Deduct from wallet for fixed pricing
        if ($package->pricing_type === 'fixed') {
            $wallet->debit(
                $package->price,
                Transaction::TYPE_SUBSCRIPTION,
                "اشتراك في باقة {$package->name_ar}",
                null,
                null,
                $subscription->id
            );
        }

        return $this->successResponse([
            'subscription' => new VendorSubscriptionResource($subscription->load('package')),
            'wallet' => new WalletResource($wallet->fresh()),
        ], 'تم الاشتراك بنجاح');
    }

    /**
     * Change to a different package
     * POST /api/v1/vendor/wallet/change-package
     */
    public function changePackage(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $vendor = $request->user();
        $wallet = $vendor->getOrCreateWallet();
        $newPackage = Package::active()->findOrFail($request->package_id);

        // Get current active subscription
        $currentSubscription = $vendor->subscriptions()->active()->latest()->first();
        
        if (!$currentSubscription) {
            return $this->errorResponse('لا يوجد اشتراك نشط لتغييره', 404);
        }

        // Can't change to same package
        if ($currentSubscription->package_id == $newPackage->id) {
            return $this->errorResponse('أنت مشترك بالفعل في هذه الباقة', 400);
        }

        // For fixed pricing, check wallet balance
        if ($newPackage->pricing_type === 'fixed' && !$wallet->hasSufficientBalance($newPackage->price)) {
            return $this->errorResponse('رصيد المحفظة غير كافي', 400);
        }

        // Change package (expires old, creates new)
        $newSubscription = $currentSubscription->changePackage($newPackage);

        if (!$newSubscription) {
            return $this->errorResponse('فشل تغيير الباقة', 500);
        }

        return $this->successResponse([
            'subscription' => new VendorSubscriptionResource($newSubscription->load('package')),
            'wallet' => new WalletResource($wallet->fresh()),
        ], 'تم تغيير الباقة بنجاح');
    }

    /**
     * Toggle auto-renew for active subscription
     * POST /api/v1/vendor/wallet/subscription/toggle-auto-renew
     */
    public function toggleAutoRenew(Request $request)
    {
        $vendor = $request->user();
        $subscription = $vendor->subscriptions()->active()->latest()->first();

        if (!$subscription) {
            return $this->errorResponse('لا يوجد اشتراك نشط', 404);
        }

        $subscription->update([
            'auto_renew' => !$subscription->auto_renew,
        ]);

        return $this->successResponse([
            'subscription' => new VendorSubscriptionResource($subscription->load('package')),
        ], $subscription->auto_renew ? 'تم تفعيل التجديد التلقائي' : 'تم إلغاء التجديد التلقائي');
    }

    /**
     * Disable auto-renew for active subscription
     * POST /api/v1/vendor/wallet/subscription/disable-auto-renew
     */
    public function disableAutoRenew(Request $request)
    {
        $vendor = $request->user();
        $subscription = $vendor->subscriptions()->active()->latest()->first();

        if (!$subscription) {
            return $this->errorResponse('لا يوجد اشتراك نشط', 404);
        }

        if (!$subscription->auto_renew) {
            return $this->errorResponse('التجديد التلقائي معطل بالفعل', 400);
        }

        $subscription->update(['auto_renew' => false]);

        return $this->successResponse([
            'subscription' => new VendorSubscriptionResource($subscription->load('package')),
        ], 'تم إلغاء التجديد التلقائي');
    }

    /**
     * Enable auto-renew for active subscription
     * POST /api/v1/vendor/wallet/subscription/enable-auto-renew
     */
    public function enableAutoRenew(Request $request)
    {
        $vendor = $request->user();
        $subscription = $vendor->subscriptions()->active()->latest()->first();

        if (!$subscription) {
            return $this->errorResponse('لا يوجد اشتراك نشط', 404);
        }

        if ($subscription->auto_renew) {
            return $this->errorResponse('التجديد التلقائي مفعل بالفعل', 400);
        }

        $subscription->update(['auto_renew' => true]);

        return $this->successResponse([
            'subscription' => new VendorSubscriptionResource($subscription->load('package')),
        ], 'تم تفعيل التجديد التلقائي');
    }

    /**
     * Get subscription history
     * GET /api/v1/vendor/wallet/subscriptions
     */
    public function subscriptions(Request $request)
    {
        $vendor = $request->user();
        $perPage = min($request->get('per_page', 20), 50);

        $subscriptions = $vendor->subscriptions()
            ->with('package')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($subscriptions, VendorSubscriptionResource::class);
    }

    /**
     * Cancel active subscription
     * POST /api/v1/vendor/wallet/subscription/cancel
     */
    public function cancelSubscription(Request $request)
    {
        $vendor = $request->user();
        $subscription = $vendor->subscriptions()->active()->latest()->first();

        if (!$subscription) {
            return $this->errorResponse('لا يوجد اشتراك نشط', 404);
        }

        $subscription->cancel();

        return $this->successResponse([
            'subscription' => new VendorSubscriptionResource($subscription->load('package')),
        ], 'تم إلغاء الاشتراك بنجاح');
    }
}
