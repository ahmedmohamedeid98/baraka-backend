<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;

class PhoneRecipientService
{
    /**
     * Format Egyptian phone number to standard format (01xxxxxxxxx)
     */
    public function formatEgyptianPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle different formats
        if (strlen($phone) == 11 && substr($phone, 0, 2) == '01') {
            // 01xxxxxxxxx format
            return $phone;
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '1') {
            // 1xxxxxxxxx format - add 0
            return '0' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '201') {
            // 201xxxxxxxxx format - remove 20
            return '0' . substr($phone, 2);
        } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+201') {
            // Should not happen after preg_replace, but just in case
            return '0' . substr($phone, 3);
        }
        
        return $phone;
    }

    /**
     * Find recipient by phone number
     * Searches users first, then vendors
     * 
     * @return array|null ['model' => User|Vendor, 'type' => 'user'|'vendor', 'name' => string]
     */
    public function findRecipientByPhone(string $phone): ?array
    {
        // Search in users first
        $user = User::where('phone', $phone)->first();
        if ($user) {
            return [
                'model' => $user,
                'type' => 'user',
                'name' => $user->name,
            ];
        }

        // Then search in vendors
        $vendor = Vendor::where('phone', $phone)->first();
        if ($vendor) {
            return [
                'model' => $vendor,
                'type' => 'vendor',
                'name' => $vendor->name,
            ];
        }

        return null;
    }

    /**
     * Mask name for privacy (e.g., "Ali K***")
     */
    public function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        
        if (count($parts) == 1) {
            // Single name: show first 3 chars
            return mb_substr($parts[0], 0, 3) . '***';
        }
        
        // Multiple parts: show first name + first letter of last name
        $firstName = $parts[0];
        $lastInitial = mb_substr($parts[count($parts) - 1], 0, 1);
        
        return $firstName . ' ' . $lastInitial . '***';
    }
}
