<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name_en',
        'name_ar',
        'code',
        'description_en',
        'description_ar',
        'icon',
        'is_active',
        'sort_order',
        'discount_type',
        'discount_amount',
        'required_transaction_screenshot',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'discount_amount' => 'decimal:2',
            'required_transaction_screenshot' => 'boolean',
        ];
    }

    /**
     * Get the instructions for the payment method
     */
    public function instructions(): HasMany
    {
        return $this->hasMany(PaymentInstruction::class)->ordered();
    }

    /**
     * Scope to get only active payment methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get localized name
     */
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    /**
     * Get localized description
     */
    public function getDescriptionAttribute()
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_en;
    }
}
