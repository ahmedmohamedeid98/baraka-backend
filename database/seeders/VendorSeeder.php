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

        $vendors = [
            [
                'email' => 'electronics@eshop.com',
                'name_ar' => 'متجر الإلكترونيات',
                'description_ar' => 'أفضل متجر للإلكترونيات في شمال سيناء',
                'phone' => '01111111111',
                'address' => 'العريش، شمال سيناء',
            ],
            [
                'email' => 'fashion@eshop.com',
                'name_ar' => 'متجر الأزياء العصرية',
                'description_ar' => 'أحدث صيحات الموضة والملابس',
                'phone' => '01222222222',
                'address' => 'العريش، شمال سيناء',
            ],
            [
                'email' => 'foods@eshop.com',
                'name_ar' => 'سوبر ماركت الطازج',
                'description_ar' => 'أجود المنتجات الغذائية الطازجة',
                'phone' => '01333333333',
                'address' => 'العريش، شمال سيناء',
            ],
            [
                'email' => 'homegoods@eshop.com',
                'name_ar' => 'متجر المنزل الذكي',
                'description_ar' => 'كل ما تحتاجه لمنزلك',
                'phone' => '01444444444',
                'address' => 'العريش، شمال سيناء',
            ],
            [
                'email' => 'beauty@eshop.com',
                'name_ar' => 'متجر الجمال والصحة',
                'description_ar' => 'منتجات التجميل والعناية الشخصية',
                'phone' => '01555555555',
                'address' => 'العريش، شمال سيناء',
            ],
        ];

        foreach ($vendors as $index => $vendorData) {
            $vendor = Vendor::firstOrCreate(
                ['email' => $vendorData['email']],
                [
                    'email' => $vendorData['email'],
                    'password' => Hash::make('password'),
                    'name_ar' => $vendorData['name_ar'],
                    'description_ar' => $vendorData['description_ar'],
                    'phone' => $vendorData['phone'],
                    'address' => $vendorData['address'],
                    'is_active' => true,
                    'approved_at' => now(),
                    'approved_by' => $admin?->id,
                    'sort_order' => $index,
                    'email_verified_at' => now(),
                ]
            );

            if ($vendorRole && !$vendor->hasRole($vendorRole)) {
                $vendor->assignRole($vendorRole);
            }
        }

        echo "✅ " . count($vendors) . " vendors created successfully\n";
        echo "Vendor emails: electronics@eshop.com, fashion@eshop.com, foods@eshop.com, homegoods@eshop.com, beauty@eshop.com\n";
        echo "All vendors password: password\n";
    }
}
