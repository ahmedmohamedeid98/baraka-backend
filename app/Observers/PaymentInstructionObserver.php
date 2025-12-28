<?php

namespace App\Observers;

use App\Models\PaymentInstruction;
use Illuminate\Support\Facades\Cache;

class PaymentInstructionObserver
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
     * Handle the PaymentInstruction "created" event.
     */
    public function created(PaymentInstruction $paymentInstruction): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentInstruction "updated" event.
     */
    public function updated(PaymentInstruction $paymentInstruction): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentInstruction "deleted" event.
     */
    public function deleted(PaymentInstruction $paymentInstruction): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentInstruction "restored" event.
     */
    public function restored(PaymentInstruction $paymentInstruction): void
    {
        $this->clearCache();
    }

    /**
     * Handle the PaymentInstruction "force deleted" event.
     */
    public function forceDeleted(PaymentInstruction $paymentInstruction): void
    {
        $this->clearCache();
    }
}
