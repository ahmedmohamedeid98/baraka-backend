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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('wallet_amount', 10, 2)->default(0)->after('discount');
            $table->decimal('paid_amount', 10, 2)->default(0)->after('wallet_amount');
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('transactions')->onDelete('set null')->after('payment_screenshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['wallet_transaction_id']);
            $table->dropColumn(['wallet_amount', 'paid_amount', 'wallet_transaction_id']);
        });
    }
};
