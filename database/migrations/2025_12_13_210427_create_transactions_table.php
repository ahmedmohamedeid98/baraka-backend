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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_wallet_id')->constrained('vendor_wallets')->onDelete('cascade');
            
            // Transaction type: charge, subscription, gift, commission, refund
            $table->string('type');
            
            // Amount: positive for credit, negative for debit
            $table->decimal('amount', 12, 2);
            
            // Balance after this transaction
            $table->decimal('balance_after', 12, 2);
            
            $table->text('description')->nullable();
            
            // Reference to related entities
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('subscription_id')->nullable();
            
            // Who created this transaction (admin for charge/gift)
            $table->foreignId('created_by')->nullable()->constrained('admins')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index('vendor_wallet_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
