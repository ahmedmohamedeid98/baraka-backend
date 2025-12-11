<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'vendor_id',
        'address_id',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'coupon_id',
        'coupon_code',
        'payment_method',
        'payment_status',
        'status',
        'notes',
        'cancellation_reason',
        'confirmed_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['delivered', 'cancelled']);
    }

    // Methods
    public function updateStatus(string $status, ?string $note = null, ?User $user = null)
    {
        $this->status = $status;

        if ($status === 'confirmed') {
            $this->confirmed_at = now();
        } elseif ($status === 'delivered') {
            $this->delivered_at = now();
            $this->payment_status = 'paid';
        } elseif ($status === 'cancelled') {
            $this->cancelled_at = now();
        }

        $this->save();

        $this->statusHistories()->create([
            'status' => $status,
            'note' => $note,
            'created_by' => $user?->id,
        ]);

        return $this;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'preparing']);
    }
}
