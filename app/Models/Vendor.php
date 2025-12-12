<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Vendor extends Authenticatable implements FilamentUser
{
    use HasFactory, SoftDeletes, Notifiable, HasRoles;

    protected $guard_name = 'vendor';

    protected $fillable = [
        'owner_user_id',
        'email',
        'password',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'logo',
        'phone',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'approved_at',
        'approved_by',
        'sort_order',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    // Accessors & Mutators
    public function getIsApprovedAttribute()
    {
        return !is_null($this->approved_at);
    }

    public function getNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : ($this->name_en ?? $this->name_ar);
    }

    public function getDescriptionAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : ($this->description_en ?? $this->description_ar);
    }

    /**
     * Determine if the vendor can access the Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow access to vendor panel
        if ($panel->getId() === 'vendor') {
            return $this->is_active && $this->is_approved;
        }

        return false;
    }

    /**
     * Get the name to display in Filament
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }
}
