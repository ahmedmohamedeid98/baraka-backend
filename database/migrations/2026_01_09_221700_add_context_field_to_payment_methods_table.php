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
        Schema::table('payment_methods', function (Blueprint $table) {
            // Add context field to specify where payment method should appear
            // Values: 'order', 'wallet_charge', 'both'
            $table->string('context', 50)->default('order')->after('required_transaction_screenshot');
            $table->index('context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropIndex(['context']);
            $table->dropColumn('context');
        });
    }
};
