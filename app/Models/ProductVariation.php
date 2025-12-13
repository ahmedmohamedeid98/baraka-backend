<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name_ar',
        'attributes',
        'price',
        'stock',
        'sku',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
