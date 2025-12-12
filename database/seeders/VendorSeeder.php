<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Get vendor role
        $vendorRole = Role::where('name', 'vendor')->where('guard_name', 'vendor')->first();
        
        // Get first admin for approval
        $admin = Admin::first();

        $vendor = Vendor::firstOrCreate(
            ['email' => 'vendor@eshop.com'],
            [
                'email' => 'vendor@eshop.com',
                'password' => Hash::make('password'),
                'name_ar' => 'متجر الإلكترونيات',
                'name_en' => 'Electronics Store',
                'description_ar' => 'أفضل متجر للإلكترونيات في شمال سيناء',
                'description_en' => 'Best electronics store in North Sinai',
                'phone' => '01111111111',
                'address' => 'العريش، شمال سيناء',
                'is_active' => true,
                'approved_at' => now(),
                'approved_by' => $admin?->id,
                'sort_order' => 0,
                'email_verified_at' => now(),
            ]
        );

        if ($vendorRole && !$vendor->hasRole($vendorRole)) {
            $vendor->assignRole($vendorRole);
        }

        echo "✅ Sample vendor created successfully\n";
        echo "Vendor: vendor@eshop.com / password: password\n";
        echo "Vendor ID: {$vendor->id}\n";
    }
}
