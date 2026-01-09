<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletTransfer extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'from_wallet_id',
        'from_user_type',
        'from_user_id',
        'to_wallet_id',
        'to_user_type',
        'to_user_id',
        'amount',
        'fee',
        'total_deducted',
        'amount_received',
        'reference_number',
        'description',
        'status',
        'ip_address',
        'user_agent',
        'device_info',
        'is_flagged',
        'flagged_reason',
        'flagged_at',
        'reviewed_by',
        'reviewed_at',
        'sender_transaction_id',
        'receiver_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'total_deducted' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'device_info' => 'array',
            'is_flagged' => 'boolean',
            'flagged_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // Boot method to generate reference number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transfer) {
            if (empty($transfer->reference_number)) {
                $transfer->reference_number = 'TRF-' . strtoupper(Str::random(12));
            }
        });
    }

    // Relationships
    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function senderTransaction()
    {
        return $this->belongsTo(Transaction::class, 'sender_transaction_id');
    }

    public function receiverTransaction()
    {
        return $this->belongsTo(Transaction::class, 'receiver_transaction_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeForUser($query, string $userType, int $userId)
    {
        return $query->where(function ($q) use ($userType, $userId) {
            $q->where(function ($subQ) use ($userType, $userId) {
                $subQ->where('from_user_type', $userType)
                     ->where('from_user_id', $userId);
            })->orWhere(function ($subQ) use ($userType, $userId) {
                $subQ->where('to_user_type', $userType)
                     ->where('to_user_id', $userId);
            });
        });
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_FAILED => 'فشل',
            self::STATUS_CANCELLED => 'ملغي',
            default => $this->status,
        };
    }

    public function getIsSenderAttribute(): bool
    {
        return true; // Will be determined dynamically in context
    }
}
