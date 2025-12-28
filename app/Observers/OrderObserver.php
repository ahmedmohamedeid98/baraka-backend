<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Transaction;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Check if status changed to 'delivered' and commissions haven't been paid yet
        if ($order->wasChanged('status') && $order->status === 'delivered') {
            $this->payVendorCommissions($order);
        }
    }

    /**
     * Pay commissions to vendors when order is delivered
     */
    protected function payVendorCommissions(Order $order): void
    {
        // Get all vendor orders for this order
        $vendorOrders = $order->vendorOrders()->where('status', '!=', 'cancelled')->get();

        foreach ($vendorOrders as $vendorOrder) {
            // Check if commission already paid
            $alreadyPaid = Transaction::where('vendor_order_id', $vendorOrder->id)
                ->where('type', 'commission')
                ->exists();

            if ($alreadyPaid) {
                continue;
            }

            // Get vendor and wallet
            $vendor = $vendorOrder->vendor;
            if (!$vendor) {
                continue;
            }

            $wallet = $vendor->wallet ?? $vendor->getOrCreateWallet();

            // Create commission transaction using vendor order subtotal
            $wallet->credit(
                $vendorOrder->subtotal,
                Transaction::TYPE_COMMISSION,
                __('Payment for order #:order_number', ['order_number' => $order->order_number]),
                null, // admin_id
                $order->id, // order_id
                null // subscription_id
            );

            // Update the transaction to include vendor_order_id
            $transaction = $wallet->transactions()->latest()->first();
            if ($transaction) {
                $transaction->update([
                    'vendor_order_id' => $vendorOrder->id,
                ]);
            }
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
