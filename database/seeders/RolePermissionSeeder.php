<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Delete existing roles and create new ones
        Role::where('name', 'admin')->delete();
        Role::where('name', 'vendor')->delete();
        Role::where('name', 'customer')->delete();

        // Create roles for different guards
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'admin']);
        $vendorRole = Role::create(['name' => 'vendor', 'guard_name' => 'vendor']);
        $customerRole = Role::create(['name' => 'customer', 'guard_name' => 'web']);

        // Create admin user in admins table
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@eshop.com'],
            [
                'name' => 'System Admin',
                'phone' => '01000000000',
                'password' => Hash::make('password'), // Change in production
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        if (!$admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }

        echo "✅ Roles and permissions created successfully\n";
        echo "Admin (admins table): admin@eshop.com / password: password\n";
    }
}
