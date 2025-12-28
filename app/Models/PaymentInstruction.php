<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInstruction extends Model
{
    protected $fillable = [
        'payment_method_id',
        'instruction_en',
        'instruction_ar',
        'font_size',
        'is_bold',
        'color',
        'is_copyable',
        'is_link',
        'placeholder',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'font_size' => 'integer',
            'is_bold' => 'boolean',
            'is_copyable' => 'boolean',
            'is_link' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the payment method that owns the instruction
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get localized instruction
     */
    public function getInstructionAttribute()
    {
        $locale = app()->getLocale();
        return $this->{"instruction_{$locale}"} ?? $this->instruction_en;
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
