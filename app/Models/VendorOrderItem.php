<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorOrderItem extends Model
{
    protected $fillable = [
        'vendor_order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_name',
        'product_image',
        'quantity',
        'price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Get the vendor order
     */
    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variant_id');
    }
}
