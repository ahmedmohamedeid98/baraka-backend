<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'walletable_type',
        'walletable_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    // Relationships
    
    /**
     * Get the owning walletable model (Vendor or User)
     */
    public function walletable()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Add funds to wallet (charge or gift)
     */
    public function credit(float $amount, string $type, ?string $description = null, ?int $adminId = null, ?int $orderId = null, ?int $subscriptionId = null): Transaction
    {
        return DB::transaction(function () use ($amount, $type, $description, $adminId, $orderId, $subscriptionId) {
            $this->balance += $amount;
            $this->save();

            $transactionData = [
                'wallet_id' => $this->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $this->balance,
                'description' => $description,
                'order_id' => $orderId,
                'subscription_id' => $subscriptionId,
                'created_by' => $adminId,
            ];

            // Add vendor_id if the wallet owner is a vendor
            if ($this->walletable_type === Vendor::class) {
                $transactionData['vendor_id'] = $this->walletable_id;
            }

            return $this->transactions()->create($transactionData);
        });
    }

    /**
     * Deduct funds from wallet (subscription, commission)
     */
    public function debit(float $amount, string $type, ?string $description = null, ?int $adminId = null, ?int $orderId = null, ?int $subscriptionId = null): Transaction
    {
        return DB::transaction(function () use ($amount, $type, $description, $adminId, $orderId, $subscriptionId) {
            $this->balance -= $amount;
            $this->save();

            $transactionData = [
                'wallet_id' => $this->id,
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $this->balance,
                'description' => $description,
                'order_id' => $orderId,
                'subscription_id' => $subscriptionId,
                'created_by' => $adminId,
            ];

            // Add vendor_id if the wallet owner is a vendor
            if ($this->walletable_type === Vendor::class) {
                $transactionData['vendor_id'] = $this->walletable_id;
            }

            return $this->transactions()->create($transactionData);
        });
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get the owner name (Vendor or User name)
     */
    public function getOwnerNameAttribute(): string
    {
        if (!$this->walletable) {
            return 'N/A';
        }

        if ($this->walletable instanceof Vendor) {
            return $this->walletable->name_ar;
        }

        if ($this->walletable instanceof User) {
            return $this->walletable->name;
        }

        return 'Unknown';
    }

    /**
     * Get the owner type label
     */
    public function getOwnerTypeAttribute(): string
    {
        return match($this->walletable_type) {
            Vendor::class => 'Vendor',
            User::class => 'User',
            default => 'Unknown',
        };
    }
}
