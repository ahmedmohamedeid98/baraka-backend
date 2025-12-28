<?php

namespace App\Observers;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Cache;

class PaymentMethodObserver
{
    /**
     * Clear payment methods cache
     */
    protected function clearCache(): void
    {
        Cache::forget('payment_methods:active:ar');
        Cache::forget('payment_methods:active:en');
    }

    /**
     * Handle the PaymentMethod "created" event.
     */
    public function created(PaymentMethod $paymentMethod): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentMethod "updated" event.
     */
    public function updated(PaymentMethod $paymentMethod): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentMethod "deleted" event.
     */
    public function deleted(PaymentMethod $paymentMethod): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentMethod "restored" event.
     */
    public function restored(PaymentMethod $paymentMethod): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentMethod "force deleted" event.
     */
    public function forceDeleted(PaymentMethod $paymentMethod): void
    {
        $this->clearCache();
    }
}
