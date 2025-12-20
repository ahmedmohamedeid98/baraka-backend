<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'delivery_fee',
        'center_points',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'center_points' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if coordinates fall within any of this area's circular zones
     */
    public function containsCoordinates(float $latitude, float $longitude): bool
    {
        if (empty($this->center_points)) {
            return false;
        }

        foreach ($this->center_points as $point) {
            if (!isset($point['latitude'], $point['longitude'], $point['radius_km'])) {
                continue;
            }

            $distance = self::calculateDistance(
                $latitude,
                $longitude,
                $point['latitude'],
                $point['longitude']
            );

            if ($distance <= $point['radius_km']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect area from coordinates using circular zones
     */
    public static function detectFromCoordinates(float $latitude, float $longitude): ?self
    {
        return static::active()
            ->whereNotNull('center_points')
            ->get()
            ->first(function ($area) use ($latitude, $longitude) {
                return $area->containsCoordinates($latitude, $longitude);
            });
    }

    /**
     * Calculate distance from coordinates to main location
     * This can be used for dynamic delivery pricing based on distance
     * 
     * @param float $userLat User's latitude
     * @param float $userLon User's longitude
     * @param float $mainLat Main store latitude (from config or settings)
     * @param float $mainLon Main store longitude (from config or settings)
     * @return float Distance in kilometers
     */
    public static function getDistanceFromMainLocation(float $userLat, float $userLon, float $mainLat, float $mainLon): float
    {
        return self::calculateDistance($userLat, $userLon, $mainLat, $mainLon);
    }

    // Relationships
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getNameAttribute()
    {
        return $this->name_ar;
    }
}
