<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:cleanup-duplicates {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup duplicate wallets by merging balances and keeping the first wallet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No changes will be made.');
        }

        $this->info('Finding duplicate wallets...');

        // Find duplicates grouped by walletable_type and walletable_id
        $duplicates = DB::table('wallets')
            ->select('walletable_type', 'walletable_id', DB::raw('COUNT(*) as wallet_count'))
            ->groupBy('walletable_type', 'walletable_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate wallets found!');
            return 0;
        }

        $this->warn("Found {$duplicates->count()} users/vendors with duplicate wallets.");

        $totalMerged = 0;
        $totalDeleted = 0;

        foreach ($duplicates as $duplicate) {
            $this->newLine();
            $this->line("Processing: {$duplicate->walletable_type} #{$duplicate->walletable_id}");

            // Get all wallets for this user/vendor
            $wallets = Wallet::where('walletable_type', $duplicate->walletable_type)
                ->where('walletable_id', $duplicate->walletable_id)
                ->orderBy('id', 'asc')
                ->get();

            if ($wallets->count() <= 1) {
                continue;
            }

            // Keep the first wallet
            $primaryWallet = $wallets->first();
            $duplicateWallets = $wallets->skip(1);

            $this->line("  Primary Wallet: #{$primaryWallet->id} (Balance: {$primaryWallet->balance})");
            $this->line("  Duplicate Wallets: {$duplicateWallets->count()}");

            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    $totalBalance = $primaryWallet->balance;

                    foreach ($duplicateWallets as $dupWallet) {
                        $this->line("    Merging Wallet #{$dupWallet->id} (Balance: {$dupWallet->balance})");
                        
                        // Add balance to primary wallet
                        $totalBalance += $dupWallet->balance;

                        // Update transactions to point to primary wallet
                        Transaction::where('wallet_id', $dupWallet->id)
                            ->update(['wallet_id' => $primaryWallet->id]);

                        // Update sent transfers
                        DB::table('wallet_transfers')
                            ->where('from_wallet_id', $dupWallet->id)
                            ->update(['from_wallet_id' => $primaryWallet->id]);

                        // Update received transfers
                        DB::table('wallet_transfers')
                            ->where('to_wallet_id', $dupWallet->id)
                            ->update(['to_wallet_id' => $primaryWallet->id]);

                        // Update charge requests
                        DB::table('wallet_charge_requests')
                            ->where('wallet_id', $dupWallet->id)
                            ->update(['wallet_id' => $primaryWallet->id]);

                        // Delete duplicate wallet
                        $dupWallet->delete();
                        $totalDeleted++;
                    }

                    // Update primary wallet balance
                    $primaryWallet->update(['balance' => $totalBalance]);
                    $this->info("    ✓ Merged into Wallet #{$primaryWallet->id} with total balance: {$totalBalance}");

                    DB::commit();
                    $totalMerged++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("    ✗ Failed to merge: {$e->getMessage()}");
                }
            } else {
                $totalBalance = $primaryWallet->balance;
                foreach ($duplicateWallets as $dupWallet) {
                    $totalBalance += $dupWallet->balance;
                }
                $this->line("    [DRY-RUN] Would merge into Wallet #{$primaryWallet->id} with total balance: {$totalBalance}");
                $totalMerged++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("[DRY-RUN] Would merge {$totalMerged} users/vendors and delete {$totalDeleted} duplicate wallets.");
            $this->warn('Run without --dry-run to apply changes.');
        } else {
            $this->info("✓ Successfully merged {$totalMerged} users/vendors and deleted {$totalDeleted} duplicate wallets.");
        }

        return 0;
    }
}
