<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('vendor_id')->constrained()->onDelete('restrict');
            $table->foreignId('address_id')->nullable()->constrained()->onDelete('set null');
            
            // Address snapshot
            $table->text('delivery_address');
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            
            // Pricing
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Coupon
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->string('coupon_code')->nullable();
            
            // Payment
            $table->enum('payment_method', ['cod'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            
            // Status
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'ready',
                'on_the_way',
                'delivered',
                'cancelled'
            ])->default('pending');
            
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order_number');
            $table->index('user_id');
            $table->index('vendor_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            
            // Product snapshot
            $table->string('product_name');
            $table->string('product_image')->nullable();
            
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
            
            $table->index('order_id');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
