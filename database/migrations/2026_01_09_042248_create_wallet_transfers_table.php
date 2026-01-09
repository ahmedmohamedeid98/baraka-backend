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
        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            
            // Sender information
            $table->foreignId('from_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->string('from_user_type'); // Vendor or User
            $table->unsignedBigInteger('from_user_id');
            
            // Receiver information
            $table->foreignId('to_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->string('to_user_type'); // Vendor or User
            $table->unsignedBigInteger('to_user_id');
            
            // Amount details
            $table->decimal('amount', 12, 2); // Amount sent (before fee)
            $table->decimal('fee', 12, 2)->default(0); // Transfer fee
            $table->decimal('total_deducted', 12, 2); // amount + fee
            $table->decimal('amount_received', 12, 2); // Amount received by recipient
            
            // Transaction details
            $table->string('reference_number')->unique(); // Unique reference for this transfer
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // Security and fraud detection
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('device_info')->nullable(); // Store device fingerprint
            $table->boolean('is_flagged')->default(false); // Flagged for suspicious activity
            $table->text('flagged_reason')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            
            // Related transactions
            $table->foreignId('sender_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->foreignId('receiver_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('from_wallet_id');
            $table->index('to_wallet_id');
            $table->index(['from_user_type', 'from_user_id']);
            $table->index(['to_user_type', 'to_user_id']);
            $table->index('status');
            $table->index('is_flagged');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transfers');
    }
};
