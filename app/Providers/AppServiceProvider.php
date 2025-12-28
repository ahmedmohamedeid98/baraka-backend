<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\PaymentInstruction;
use App\Observers\ProductObserver;
use App\Observers\PaymentMethodObserver;
use App\Observers\PaymentInstructionObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        PaymentMethod::observe(PaymentMethodObserver::class);
        PaymentInstruction::observe(PaymentInstructionObserver::class);
    }
}
