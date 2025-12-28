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
        'payment_screenshot',
        'payment_status',
        'payment_rejection_reason',
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

    public function vendorOrders()
    {
        return $this->hasMany(VendorOrder::class);
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
            // Create vendor orders when order is confirmed
            $this->createVendorOrders();
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

    /**
     * Create vendor orders from order items
     */
    protected function createVendorOrders(): void
    {
        // Check if vendor orders already exist
        if ($this->vendorOrders()->exists()) {
            return;
        }

        // Group items by vendor_id
        $itemsByVendor = $this->items()->with('product')->get()->groupBy(function ($item) {
            return $item->product->vendor_id;
        });

        foreach ($itemsByVendor as $vendorId => $vendorItems) {
            // Calculate vendor subtotal
            $vendorSubtotal = $vendorItems->sum('subtotal');

            // Create vendor order
            $vendorOrder = VendorOrder::create([
                'order_id' => $this->id,
                'order_number' => $this->order_number,
                'vendor_id' => $vendorId,
                'subtotal' => $vendorSubtotal,
                'status' => 'pending',
            ]);

            // Create vendor order items
            foreach ($vendorItems as $item) {
                // Get product image
                $productImage = $item->product->first_image ?? null;

                $vendorOrder->items()->create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'product_image' => $productImage,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]);
            }
        }
    }
}
