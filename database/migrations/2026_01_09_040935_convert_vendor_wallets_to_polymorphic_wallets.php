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
        // Check if table was already renamed
        if (!Schema::hasTable('wallets')) {
            Schema::rename('vendor_wallets', 'wallets');
        }
        
        // Add polymorphic columns as nullable first (only if they don't exist)
        if (!Schema::hasColumn('wallets', 'walletable_type')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->string('walletable_type')->nullable()->after('id');
                $table->unsignedBigInteger('walletable_id')->nullable()->after('walletable_type');
            });
            
            // Migrate existing data
            DB::table('wallets')->update([
                'walletable_type' => 'App\\Models\\Vendor',
                'walletable_id' => DB::raw('vendor_id')
            ]);
            
            // Now make them non-nullable
            Schema::table('wallets', function (Blueprint $table) {
                $table->string('walletable_type')->nullable(false)->change();
                $table->unsignedBigInteger('walletable_id')->nullable(false)->change();
                
                $table->index(['walletable_type', 'walletable_id']);
            });
        }
        
        // Drop the old vendor_id column if it still exists
        if (Schema::hasColumn('wallets', 'vendor_id')) {
            // Get constraint name from database
            $constraints = DB::select("
                SELECT conname 
                FROM pg_constraint 
                WHERE conrelid = 'wallets'::regclass 
                AND contype = 'f'
                AND conname LIKE '%vendor_id%'
            ");
            
            // Drop foreign key constraints using raw SQL
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE wallets DROP CONSTRAINT {$constraint->conname}");
            }
            
            // Drop the column
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropColumn('vendor_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add vendor_id back
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreignId('vendor_id')->after('id')->constrained('vendors')->onDelete('cascade');
            $table->index('vendor_id');
        });
        
        // Migrate data back
        DB::table('wallets')
            ->where('walletable_type', 'App\\Models\\Vendor')
            ->update([
                'vendor_id' => DB::raw('walletable_id')
            ]);
        
        // Drop polymorphic columns
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex(['walletable_type', 'walletable_id']);
            $table->dropColumn(['walletable_type', 'walletable_id']);
        });
        
        // Rename back
        Schema::rename('wallets', 'vendor_wallets');
    }
};
