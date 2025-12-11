<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'latitude',
        'longitude',
        'formatted_address',
        'area_id',
        'street',
        'note',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_default' => 'boolean',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        if ($this->type === 'map') {
            return $this->formatted_address . ($this->note ? ' - ' . $this->note : '');
        }
        
        $parts = array_filter([
            $this->area?->name,
            $this->street,
            $this->note,
        ]);
        
        return implode(', ', $parts);
    }
}
