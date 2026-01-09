<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Vendor extends Authenticatable implements FilamentUser
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, HasRoles;

    protected $guard_name = 'vendor';

    protected $fillable = [
        'email',
        'password',
        'name_ar',
        'description_ar',
        'logo',
        'phone',
        'address',
        'latitude',
        'longitude',
        'is_active',
        'is_featured',
        'approved_at',
        'approved_by',
        'sort_order',
        'email_verified_at',
        'fcm_token',
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
            'is_featured' => 'boolean',
            'approved_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
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

    public function documents()
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'vendor_categories')
            ->withTimestamps();
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    public function subscriptions()
    {
        return $this->hasMany(VendorSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(VendorSubscription::class)->active()->latest();
    }

    /**
     * Get or create wallet for vendor
     */
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet()->firstOrCreate(
            [
                'walletable_type' => self::class,
                'walletable_id' => $this->id,
            ],
            ['balance' => 0]
        );
    }

    /**
     * Check if vendor has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()->active()->where('ends_at', '>', now())->exists();
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
