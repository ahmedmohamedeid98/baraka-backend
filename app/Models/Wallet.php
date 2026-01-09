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

    public function sentTransfers()
    {
        return $this->hasMany(WalletTransfer::class, 'from_wallet_id');
    }

    public function receivedTransfers()
    {
        return $this->hasMany(WalletTransfer::class, 'to_wallet_id');
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

    /**
     * Calculate transfer fee
     */
    public function calculateTransferFee(float $amount): float
    {
        $percentageFee = ($amount * config('api.wallet_transfer.fee_percentage', 1)) / 100;
        $fixedFee = config('api.wallet_transfer.fee_fixed', 0);
        
        return round($percentageFee + $fixedFee, 2);
    }

    /**
     * Transfer balance to another wallet
     */
    public function transferTo(Wallet $toWallet, float $amount, ?string $description = null, array $securityData = []): WalletTransfer
    {
        // Validation
        if (!config('api.wallet_transfer.enabled', true)) {
            throw new \Exception('تحويل الرصيد غير متاح حالياً');
        }

        if ($this->id === $toWallet->id) {
            throw new \Exception('لا يمكن التحويل إلى نفس المحفظة');
        }

        if ($amount < config('api.wallet_transfer.min_amount', 10)) {
            throw new \Exception('المبلغ أقل من الحد الأدنى للتحويل');
        }

        if ($amount > config('api.wallet_transfer.max_amount', 10000)) {
            throw new \Exception('المبلغ أكبر من الحد الأقصى للتحويل');
        }

        // Calculate fee
        $fee = $this->calculateTransferFee($amount);
        $totalDeducted = $amount + $fee;

        if (!$this->hasSufficientBalance($totalDeducted)) {
            throw new \Exception('الرصيد غير كافي لإتمام التحويل');
        }

        // Check daily limits
        $this->checkDailyLimits($amount);

        return DB::transaction(function () use ($toWallet, $amount, $fee, $totalDeducted, $description, $securityData) {
            // Deduct from sender
            $this->balance -= $totalDeducted;
            $this->save();

            // Create sender transaction
            $senderTransactionData = [
                'wallet_id' => $this->id,
                'type' => 'refund', // Using refund type for transfers
                'amount' => -$totalDeducted,
                'balance_after' => $this->balance,
                'description' => $description ?? "تحويل رصيد - رسوم: {$fee} جنيه",
            ];

            if ($this->walletable_type === Vendor::class) {
                $senderTransactionData['vendor_id'] = $this->walletable_id;
            }

            $senderTransaction = $this->transactions()->create($senderTransactionData);

            // Add to receiver
            $toWallet->balance += $amount;
            $toWallet->save();

            // Create receiver transaction
            $receiverTransactionData = [
                'wallet_id' => $toWallet->id,
                'type' => 'charge',
                'amount' => $amount,
                'balance_after' => $toWallet->balance,
                'description' => $description ?? 'استلام تحويل رصيد',
            ];

            if ($toWallet->walletable_type === Vendor::class) {
                $receiverTransactionData['vendor_id'] = $toWallet->walletable_id;
            }

            $receiverTransaction = $toWallet->transactions()->create($receiverTransactionData);

            // Determine if transfer should be flagged
            $isFlagged = config('api.wallet_transfer.auto_flag_suspicious', true) && 
                         $amount >= config('api.wallet_transfer.suspicious_amount_threshold', 5000);

            // Create transfer record
            $transfer = WalletTransfer::create([
                'from_wallet_id' => $this->id,
                'from_user_type' => $this->walletable_type,
                'from_user_id' => $this->walletable_id,
                'to_wallet_id' => $toWallet->id,
                'to_user_type' => $toWallet->walletable_type,
                'to_user_id' => $toWallet->walletable_id,
                'amount' => $amount,
                'fee' => $fee,
                'total_deducted' => $totalDeducted,
                'amount_received' => $amount,
                'description' => $description,
                'status' => WalletTransfer::STATUS_COMPLETED,
                'ip_address' => $securityData['ip_address'] ?? null,
                'user_agent' => $securityData['user_agent'] ?? null,
                'device_info' => $securityData['device_info'] ?? null,
                'is_flagged' => $isFlagged,
                'flagged_reason' => $isFlagged ? 'تحويل بمبلغ كبير - يتطلب المراجعة' : null,
                'flagged_at' => $isFlagged ? now() : null,
                'sender_transaction_id' => $senderTransaction->id,
                'receiver_transaction_id' => $receiverTransaction->id,
            ]);

            return $transfer;
        });
    }

    /**
     * Check daily transfer limits
     */
    protected function checkDailyLimits(float $amount): void
    {
        $today = now()->startOfDay();
        
        // Check daily amount limit
        $dailyTotal = WalletTransfer::where('from_wallet_id', $this->id)
            ->where('status', WalletTransfer::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $dailyLimit = config('api.wallet_transfer.daily_limit', 50000);
        
        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new \Exception("تجاوز الحد اليومي للتحويلات ({$dailyLimit} جنيه)");
        }

        // Check daily count limit
        $dailyCount = WalletTransfer::where('from_wallet_id', $this->id)
            ->where('status', WalletTransfer::STATUS_COMPLETED)
            ->where('created_at', '>=', $today)
            ->count();

        $countLimit = config('api.wallet_transfer.daily_count_limit', 20);
        
        if ($dailyCount >= $countLimit) {
            throw new \Exception("تجاوز عدد التحويلات اليومية المسموحة ({$countLimit} تحويل)");
        }
    }
}
