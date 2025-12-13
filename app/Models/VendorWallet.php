<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VendorWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
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

            return $this->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $this->balance,
                'description' => $description,
                'order_id' => $orderId,
                'subscription_id' => $subscriptionId,
                'created_by' => $adminId,
            ]);
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

            return $this->transactions()->create([
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $this->balance,
                'description' => $description,
                'order_id' => $orderId,
                'subscription_id' => $subscriptionId,
                'created_by' => $adminId,
            ]);
        });
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
