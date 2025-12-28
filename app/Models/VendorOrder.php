<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOrder extends Model
{
    protected $fillable = [
        'order_id',
        'order_number',
        'vendor_id',
        'subtotal',
        'status',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Get the main order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the items
     */
    public function items(): HasMany
    {
        return $this->hasMany(VendorOrderItem::class);
    }

    /**
     * Update status
     */
    public function updateStatus(string $status): bool
    {
        $allowedTransitions = [
            'pending' => ['processing'],
            'processing' => ['ready'],
            'ready' => ['collected'],
        ];

        $currentStatus = $this->status;
        
        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($status, $allowedTransitions[$currentStatus])) {
            return false;
        }

        return $this->update(['status' => $status]);
    }

    /**
     * Check if commission has been paid
     */
    public function isCommissionPaid(): bool
    {
        return $this->order->status === 'delivered';
    }

    /**
     * Scope for vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
