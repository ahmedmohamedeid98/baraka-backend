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
        // Remove commission_percentage from vendors table
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('commission_percentage');
        });

        // Remove commission_amount from vendor_orders table
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->dropColumn('commission_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back commission_percentage to vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(10.00);
        });

        // Add back commission_amount to vendor_orders
        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->decimal('commission_amount', 10, 2)->default(0);
        });
    }
};
