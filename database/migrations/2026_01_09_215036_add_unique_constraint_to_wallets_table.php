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
        // First, find and merge any duplicate wallets
        $this->cleanupDuplicates();

        // Then add unique constraint
        Schema::table('wallets', function (Blueprint $table) {
            // Drop the old index if it exists
            $indexName = 'wallets_walletable_type_walletable_id_index';
            if (DB::getDriverName() === 'pgsql') {
                $exists = DB::select(
                    "SELECT 1 FROM pg_indexes WHERE indexname = ?",
                    [$indexName]
                );
                if (!empty($exists)) {
                    DB::statement("DROP INDEX IF EXISTS {$indexName}");
                }
            }

            // Add unique constraint
            $table->unique(['walletable_type', 'walletable_id'], 'wallets_walletable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique('wallets_walletable_unique');
            $table->index(['walletable_type', 'walletable_id']);
        });
    }

    /**
     * Cleanup duplicate wallets before adding constraint
     */
    private function cleanupDuplicates(): void
    {
        // Find duplicates
        $duplicates = DB::table('wallets')
            ->select('walletable_type', 'walletable_id', DB::raw('COUNT(*) as wallet_count'))
            ->groupBy('walletable_type', 'walletable_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        echo "Found {$duplicates->count()} users/vendors with duplicate wallets. Merging...\n";

        foreach ($duplicates as $duplicate) {
            // Get all wallets for this user/vendor
            $wallets = DB::table('wallets')
                ->where('walletable_type', $duplicate->walletable_type)
                ->where('walletable_id', $duplicate->walletable_id)
                ->orderBy('id', 'asc')
                ->get();

            if ($wallets->count() <= 1) {
                continue;
            }

            // Keep the first wallet
            $primaryWallet = $wallets->first();
            $duplicateWallets = $wallets->skip(1);

            DB::beginTransaction();
            try {
                $totalBalance = $primaryWallet->balance;

                foreach ($duplicateWallets as $dupWallet) {
                    // Add balance to total
                    $totalBalance += $dupWallet->balance;

                    // Update all related records to point to primary wallet
                    DB::table('transactions')
                        ->where('wallet_id', $dupWallet->id)
                        ->update(['wallet_id' => $primaryWallet->id]);

                    DB::table('wallet_transfers')
                        ->where('from_wallet_id', $dupWallet->id)
                        ->update(['from_wallet_id' => $primaryWallet->id]);

                    DB::table('wallet_transfers')
                        ->where('to_wallet_id', $dupWallet->id)
                        ->update(['to_wallet_id' => $primaryWallet->id]);

                    DB::table('wallet_charge_requests')
                        ->where('wallet_id', $dupWallet->id)
                        ->update(['wallet_id' => $primaryWallet->id]);

                    // Delete duplicate wallet
                    DB::table('wallets')->where('id', $dupWallet->id)->delete();
                }

                // Update primary wallet balance
                DB::table('wallets')
                    ->where('id', $primaryWallet->id)
                    ->update(['balance' => $totalBalance]);

                DB::commit();
                echo "Merged {$duplicate->walletable_type} #{$duplicate->walletable_id}\n";
            } catch (\Exception $e) {
                DB::rollBack();
                echo "Failed to merge {$duplicate->walletable_type} #{$duplicate->walletable_id}: {$e->getMessage()}\n";
            }
        }
    }
};
