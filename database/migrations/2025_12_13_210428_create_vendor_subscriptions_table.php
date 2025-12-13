<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages')->onDelete('restrict');
            
            // Subscription period
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            
            // Auto-renew from wallet
            $table->boolean('auto_renew')->default(true);
            
            // Status: active, expired, cancelled
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            
            // Price at time of subscription (in case package price changes)
            $table->decimal('price_paid', 10, 2);
            $table->enum('pricing_type', ['fixed', 'percentage']);
            
            // Renewed from previous subscription
            $table->foreignId('renewed_from')->nullable()->constrained('vendor_subscriptions')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('vendor_id');
            $table->index('package_id');
            $table->index('status');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_subscriptions');
    }
};
