<?php

namespace App\Console\Commands;

use App\Models\VendorSubscription;
use Illuminate\Console\Command;

class RenewExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew expired subscriptions that have auto-renew enabled';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription renewal process...');

        $subscriptions = VendorSubscription::needsRenewal()->with(['vendor.wallet', 'package'])->get();

        $renewed = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            $this->info("Processing subscription #{$subscription->id} for vendor: {$subscription->vendor->name_ar}");

            $newSubscription = $subscription->renew();

            if ($newSubscription) {
                $renewed++;
                $this->info("  ✓ Successfully renewed until {$newSubscription->ends_at->format('Y-m-d')}");
            } else {
                $failed++;
                $this->warn("  ✗ Failed to renew - insufficient balance");
                
                // Mark as expired
                $subscription->update(['status' => VendorSubscription::STATUS_EXPIRED]);
            }
        }

        $this->newLine();
        $this->info("Renewal process completed:");
        $this->info("  - Renewed: {$renewed}");
        $this->info("  - Failed: {$failed}");

        return Command::SUCCESS;
    }
}
