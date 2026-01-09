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
        Schema::table('transactions', function (Blueprint $table) {
            // Add new wallet_id column
            $table->unsignedBigInteger('wallet_id')->after('id')->nullable();
            
            // Keep vendor_id for backward compatibility and reference
            $table->foreignId('vendor_id')->after('wallet_id')->nullable()->constrained('vendors')->onDelete('set null');
            
            $table->index('wallet_id');
        });
        
        // Migrate existing data: copy vendor_wallet_id to wallet_id
        DB::statement('UPDATE transactions SET wallet_id = vendor_wallet_id');
        
        // Get vendor_id from the wallet relationship and populate it (PostgreSQL syntax)
        DB::statement("
            UPDATE transactions t
            SET vendor_id = w.walletable_id
            FROM wallets w
            WHERE t.wallet_id = w.id
            AND w.walletable_type = 'App\\\\Models\\\\Vendor'
        ");
        
        // Make wallet_id non-nullable after data migration
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('wallet_id')->nullable(false)->change();
        });
        
        // Drop the old vendor_wallet_id column
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['vendor_wallet_id']);
            $table->dropIndex(['vendor_wallet_id']);
            $table->dropColumn('vendor_wallet_id');
        });
        
        // Add foreign key for wallet_id
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add vendor_wallet_id back
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_wallet_id')->after('id');
            $table->index('vendor_wallet_id');
        });
        
        // Migrate data back
        DB::statement('UPDATE transactions SET vendor_wallet_id = wallet_id');
        
        // Drop new columns
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['wallet_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropIndex(['wallet_id']);
            $table->dropColumn(['wallet_id', 'vendor_id']);
        });
        
        // Add foreign key back
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('vendor_wallet_id')->references('id')->on('wallets')->onDelete('cascade');
        });
    }
};
