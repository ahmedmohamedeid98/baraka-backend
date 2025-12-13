<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\VendorWallet;
use Illuminate\Database\Seeder;

class VendorWalletSeeder extends Seeder
{
    public function run(): void
    {
        // Create wallets for all vendors
        $vendors = Vendor::all();

        foreach ($vendors as $vendor) {
            VendorWallet::firstOrCreate(
                ['vendor_id' => $vendor->id],
                ['balance' => 0]
            );
        }

        echo "✅ Vendor wallets created successfully\n";
    }
}
