<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WalletChargeRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const PAYMENT_VODAFONE_CASH = 'vodafone_cash';
    const PAYMENT_INSTAPAY = 'instapay';
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_OTHER = 'other';

    protected $fillable = [
        'wallet_id',
        'user_type',
        'user_id',
        'amount',
        'payment_method',
        'payment_screenshot',
        'payment_reference',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'is_resubmission',
        'original_request_id',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_resubmission' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function originalRequest()
    {
        return $this->belongsTo(WalletChargeRequest::class, 'original_request_id');
    }

    public function resubmissions()
    {
        return $this->hasMany(WalletChargeRequest::class, 'original_request_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForUser($query, string $userType, int $userId)
    {
        return $query->where('user_type', $userType)
                     ->where('user_id', $userId);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'قيد المراجعة',
            self::STATUS_APPROVED => 'تم القبول',
            self::STATUS_REJECTED => 'مرفوض',
            default => $this->status,
        };
    }

    public function getPaymentMethodTextAttribute(): string
    {
        return match($this->payment_method) {
            self::PAYMENT_VODAFONE_CASH => 'فودافون كاش',
            self::PAYMENT_INSTAPAY => 'انستاباي',
            self::PAYMENT_BANK_TRANSFER => 'تحويل بنكي',
            self::PAYMENT_OTHER => 'أخرى',
            default => $this->payment_method,
        };
    }

    public function getScreenshotUrlAttribute(): ?string
    {
        if (!$this->payment_screenshot) {
            return null;
        }
        return Storage::url($this->payment_screenshot);
    }

    public function getUserNameAttribute(): string
    {
        return $this->wallet?->walletable?->name_ar ?? 
               $this->wallet?->walletable?->name ?? 
               'N/A';
    }

    // Actions
    public function approve(int $adminId): void
    {
        DB::transaction(function () use ($adminId) {
            // Credit the wallet
            $transaction = $this->wallet->credit(
                $this->amount,
                Transaction::TYPE_CHARGE,
                "شحن محفظة - طلب رقم #{$this->id}",
                $adminId
            );

            // Update request status
            $this->update([
                'status' => self::STATUS_APPROVED,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'transaction_id' => $transaction->id,
            ]);
        });
    }

    public function reject(int $adminId, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function canBeResubmitted(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
