<?php

namespace App\Filament\Resources\VendorSubscriptionResource\Pages;

use App\Filament\Resources\VendorSubscriptionResource;
use App\Models\Transaction;
use App\Models\VendorSubscription;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorSubscription extends CreateRecord
{
    protected static string $resource = VendorSubscriptionResource::class;

    protected function afterCreate(): void
    {
        $subscription = $this->record;
        
        // Expire any existing active subscriptions for this vendor
        VendorSubscription::where('vendor_id', $subscription->vendor_id)
            ->where('id', '!=', $subscription->id)
            ->where('status', VendorSubscription::STATUS_ACTIVE)
            ->update(['status' => VendorSubscription::STATUS_EXPIRED]);
        
        // Deduct from wallet for fixed pricing
        if ($subscription->pricing_type === 'fixed') {
            $wallet = $subscription->vendor->getOrCreateWallet();
            
            if ($wallet->hasSufficientBalance($subscription->price_paid)) {
                $wallet->debit(
                    $subscription->price_paid,
                    Transaction::TYPE_SUBSCRIPTION,
                    "اشتراك في باقة {$subscription->package->name_ar}",
                    auth()->id(),
                    null,
                    $subscription->id
                );
            }
        }
    }
}
