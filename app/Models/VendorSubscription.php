<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSubscription extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'vendor_id',
        'package_id',
        'starts_at',
        'ends_at',
        'auto_renew',
        'status',
        'price_paid',
        'pricing_type',
        'renewed_from',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'price_paid' => 'decimal:2',
        ];
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function renewedFrom()
    {
        return $this->belongsTo(VendorSubscription::class, 'renewed_from');
    }

    public function renewedTo()
    {
        return $this->hasOne(VendorSubscription::class, 'renewed_from');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'subscription_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeExpiring($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '<=', now()->addDays(3));
    }

    public function scopeNeedsRenewal($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('auto_renew', true)
            ->where('ends_at', '<=', now());
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->ends_at->isFuture();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->ends_at->isPast();
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->ends_at->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->ends_at);
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'نشط',
            self::STATUS_EXPIRED => 'منتهي',
            self::STATUS_CANCELLED => 'ملغي',
            default => $this->status,
        };
    }

    /**
     * Renew subscription
     */
    public function renew(): ?VendorSubscription
    {
        $wallet = $this->vendor->wallet;
        $package = $this->package;

        // Check if wallet has sufficient balance for fixed pricing
        if ($this->pricing_type === 'fixed' && !$wallet->hasSufficientBalance($package->price)) {
            return null;
        }

        // Create new subscription
        $newSubscription = self::create([
            'vendor_id' => $this->vendor_id,
            'package_id' => $this->package_id,
            'starts_at' => $this->ends_at,
            'ends_at' => $this->ends_at->copy()->addDays($package->duration_days),
            'auto_renew' => $this->auto_renew,
            'status' => self::STATUS_ACTIVE,
            'price_paid' => $package->price,
            'pricing_type' => $package->pricing_type,
            'renewed_from' => $this->id,
        ]);

        // Deduct from wallet for fixed pricing
        if ($this->pricing_type === 'fixed') {
            $wallet->debit(
                $package->price,
                Transaction::TYPE_SUBSCRIPTION,
                "تجديد اشتراك باقة {$package->name_ar}",
                null,
                null,
                $newSubscription->id
            );
        }

        // Mark old subscription as expired
        $this->update(['status' => self::STATUS_EXPIRED]);

        return $newSubscription;
    }

    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        $this->update([
            'auto_renew' => false,
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Change to a different package
     * Expires current subscription and creates new one with the new package
     */
    public function changePackage(Package $newPackage, ?int $performedBy = null): ?VendorSubscription
    {
        $wallet = $this->vendor->wallet;

        // Check if wallet has sufficient balance for fixed pricing
        if ($newPackage->pricing_type === 'fixed' && !$wallet->hasSufficientBalance($newPackage->price)) {
            return null;
        }

        // Create new subscription with new package
        $newSubscription = self::create([
            'vendor_id' => $this->vendor_id,
            'package_id' => $newPackage->id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($newPackage->duration_days),
            'auto_renew' => $this->auto_renew,
            'status' => self::STATUS_ACTIVE,
            'price_paid' => $newPackage->price,
            'pricing_type' => $newPackage->pricing_type,
            'renewed_from' => $this->id,
        ]);

        // Deduct from wallet for fixed pricing
        if ($newPackage->pricing_type === 'fixed') {
            $wallet->debit(
                $newPackage->price,
                Transaction::TYPE_SUBSCRIPTION,
                "تغيير الباقة إلى {$newPackage->name_ar}",
                $performedBy,
                null,
                $newSubscription->id
            );
        }

        // Mark old subscription as expired
        $this->update(['status' => self::STATUS_EXPIRED]);

        return $newSubscription;
    }

    /**
     * Expire current subscription (for manual expiry or when creating new subscription)
     */
    public function expire(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }
}
