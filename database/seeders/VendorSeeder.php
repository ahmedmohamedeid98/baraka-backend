<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Get the vendor user created in RolePermissionSeeder
        $vendorUser = User::where('phone', '01111111111')->first();

        if (!$vendorUser) {
            echo "⚠️  Vendor user not found. Run RolePermissionSeeder first.\n";
            return;
        }

        $vendor = Vendor::create([
            'owner_user_id' => $vendorUser->id,
            'name_ar' => 'متجر الإلكترونيات',
            'name_en' => 'Electronics Store',
            'description_ar' => 'أفضل متجر للإلكترونيات في شمال سيناء',
            'description_en' => 'Best electronics store in North Sinai',
            'phone' => '01111111111',
            'address' => 'العريش، شمال سيناء',
            'is_active' => true,
            'approved_at' => now(),
            'approved_by' => 1, // Admin user ID
            'sort_order' => 0,
        ]);

        echo "✅ Sample vendor created successfully\n";
        echo "Vendor ID: {$vendor->id}\n";
    }
}
