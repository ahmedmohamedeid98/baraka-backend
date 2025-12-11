<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'code',
        'method',
        'verified',
        'expires_at',
        'verified_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('verified', false)
            ->where('expires_at', '>', now());
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAsVerified()
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
        ]);
    }
}
