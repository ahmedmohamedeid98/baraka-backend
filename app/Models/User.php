<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'avatar',
        'fcm_token',
        'is_active',
        'phone_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'owner_user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class)->orderBy('is_default', 'desc')->orderBy('created_at', 'desc');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class)->withPivot('usage_count')->withTimestamps();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    /**
     * Get or create wallet for user
     */
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create(['balance' => 0]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('phone_verified_at');
    }

    /**
     * Determine if the user can access the Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only vendors can access the vendor panel
        if ($panel->getId() === 'vendor') {
            return $this->is_active && 
                   $this->hasRole('vendor') && 
                   $this->vendor !== null;
        }

        return false;
    }

    /**
     * Get the name to display in Filament
     */
    public function getFilamentName(): string
    {
        return $this->name ?? $this->phone;
    }
}
