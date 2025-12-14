<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartOrder extends Model
{
    protected $fillable = [
        'user_id',
        'original_text',
        'parsed_items',
        'total_price',
        'total_items',
        'is_favorite',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'parsed_items' => 'array',
            'total_price' => 'decimal:2',
            'is_favorite' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
