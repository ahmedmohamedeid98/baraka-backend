<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $vendorRole = Role::create(['name' => 'vendor']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'phone' => '01000000000',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Create sample vendor user
        $vendorUser = User::create([
            'name' => 'Vendor User',
            'phone' => '01111111111',
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);
        $vendorUser->assignRole('vendor');

        echo "✅ Roles and permissions created successfully\n";
        echo "Admin user: 01000000000\n";
        echo "Vendor user: 01111111111\n";
    }
}
