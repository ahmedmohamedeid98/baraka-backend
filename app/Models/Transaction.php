<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    const TYPE_CHARGE = 'charge';
    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_GIFT = 'gift';
    const TYPE_COMMISSION = 'commission';
    const TYPE_REFUND = 'refund';

    protected $fillable = [
        'vendor_wallet_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'order_id',
        'subscription_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(VendorWallet::class, 'vendor_wallet_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function subscription()
    {
        return $this->belongsTo(VendorSubscription::class, 'subscription_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    // Scopes
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getIsDebitAttribute(): bool
    {
        return $this->amount < 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->amount > 0;
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CHARGE => 'شحن رصيد',
            self::TYPE_SUBSCRIPTION => 'اشتراك',
            self::TYPE_GIFT => 'هدية',
            self::TYPE_COMMISSION => 'عمولة',
            self::TYPE_REFUND => 'استرداد',
            default => $this->type,
        };
    }
}
