<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'description_ar',
        'pricing_type',
        'price',
        'percentage_tiers',
        'duration_days',
        'features',
        'max_products',
        'max_orders_per_month',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'percentage_tiers' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(VendorSubscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFixed($query)
    {
        return $query->where('pricing_type', 'fixed');
    }

    public function scopePercentage($query)
    {
        return $query->where('pricing_type', 'percentage');
    }

    // Accessors
    public function getNameAttribute()
    {
        return $this->name_ar;
    }

    public function getDescriptionAttribute()
    {
        return $this->description_ar;
    }

    public function getDurationTextAttribute()
    {
        return match($this->duration_days) {
            30 => 'شهري',
            90 => 'ربع سنوي',
            180 => 'نصف سنوي',
            365 => 'سنوي',
            default => $this->duration_days . ' يوم',
        };
    }

    /**
     * Calculate commission for an order based on percentage tiers
     * 
     * @param float $orderAmount The order total amount
     * @return array ['percentage' => float, 'commission' => float]
     */
    public function calculateCommission(float $orderAmount): array
    {
        if ($this->pricing_type !== 'percentage') {
            return ['percentage' => 0, 'commission' => 0];
        }

        // If no tiers defined, use the default price as percentage
        if (empty($this->percentage_tiers)) {
            $percentage = (float) $this->price;
            return [
                'percentage' => $percentage,
                'commission' => round($orderAmount * ($percentage / 100), 2),
            ];
        }

        // Find the matching tier based on order amount
        $percentage = (float) $this->price; // Default fallback
        
        foreach ($this->percentage_tiers as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? null;
            
            if ($orderAmount >= $min && ($max === null || $orderAmount < $max)) {
                $percentage = (float) $tier['percentage'];
                break;
            }
        }

        return [
            'percentage' => $percentage,
            'commission' => round($orderAmount * ($percentage / 100), 2),
        ];
    }

    /**
     * Get formatted tiers text for display
     */
    public function getTiersTextAttribute(): string
    {
        if ($this->pricing_type !== 'percentage' || empty($this->percentage_tiers)) {
            return $this->pricing_type === 'percentage' ? $this->price . '%' : '';
        }

        $texts = [];
        foreach ($this->percentage_tiers as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? null;
            $percentage = $tier['percentage'] ?? 0;

            if ($max === null) {
                $texts[] = ">{$min}: {$percentage}%";
            } else {
                $texts[] = "{$min}-{$max}: {$percentage}%";
            }
        }

        return implode(' | ', $texts);
    }
}
