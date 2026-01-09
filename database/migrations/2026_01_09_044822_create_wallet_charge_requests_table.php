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
        Schema::create('wallet_charge_requests', function (Blueprint $table) {
            $table->id();
            
            // User/Wallet information
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->string('user_type'); // Vendor or User
            $table->unsignedBigInteger('user_id');
            
            // Request details
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['vodafone_cash', 'instapay', 'bank_transfer', 'other']);
            $table->string('payment_screenshot'); // Path to uploaded image
            $table->string('payment_reference')->nullable(); // Transaction reference if provided
            $table->text('notes')->nullable(); // User notes
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Admin action
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_resubmission')->default(false); // If user resubmitted after rejection
            $table->foreignId('original_request_id')->nullable()->constrained('wallet_charge_requests')->onDelete('set null');
            
            // Transaction reference (created when approved)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index('wallet_id');
            $table->index(['user_type', 'user_id']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_charge_requests');
    }
};
