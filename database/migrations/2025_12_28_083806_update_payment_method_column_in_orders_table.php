<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to drop the check constraints first
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_status_check');
        
        Schema::table('orders', function (Blueprint $table) {
            // Change payment_method from enum to string to support dynamic payment methods
            $table->string('payment_method')->default('cod')->change();
            
            // Change payment_status from enum to string to support new statuses
            $table->string('payment_status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to enum (only if needed)
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_method TYPE varchar(255)");
            DB::statement("ALTER TABLE orders ALTER COLUMN payment_status TYPE varchar(255)");
        });
    }
};
