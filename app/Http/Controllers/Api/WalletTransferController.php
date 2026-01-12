<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WalletTransferResource;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Services\PhoneRecipientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class WalletTransferController extends ApiController
{
    protected PhoneRecipientService $phoneRecipientService;

    public function __construct(PhoneRecipientService $phoneRecipientService)
    {
        $this->phoneRecipientService = $phoneRecipientService;
    }
    /**
     * Calculate transfer fee
     * POST /api/v1/{guard}/wallet/transfer/calculate-fee
     */
    public function calculateFee(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . config('api.wallet_transfer.min_amount', 10),
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $amount = (float) $request->amount;
        $fee = $wallet->calculateTransferFee($amount);
        $total = $amount + $fee;

        $response = [
            'amount' => number_format($amount, 2),
            'fee' => number_format($fee, 2),
            'fee_percentage' => config('api.wallet_transfer.fee_percentage', 1),
            'fee_fixed' => config('api.wallet_transfer.fee_fixed', 0),
            'total_deducted' => number_format($total, 2),
            'amount_received' => number_format($amount, 2),
            'sufficient_balance' => $wallet->hasSufficientBalance($total),
            'current_balance' => number_format($wallet->balance, 2),
        ];

        return $this->successResponse($response);
    }

    /**
     * Validate transfer before execution
     * POST /api/v1/{guard}/wallet/transfer/validate
     */
    public function validate(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:' . config('api.wallet_transfer.min_amount', 10),
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $errors = [];
        $warnings = [];
        $recipientName = null;

        // Check if transfers are enabled
        if (!config('api.wallet_transfer.enabled', true)) {
            $errors[] = 'خدمة تحويل الرصيد غير متاحة حالياً';
        }

        // Format and find recipient by phone
        $phone = $this->phoneRecipientService->formatEgyptianPhone($request->phone);
        $recipientData = $this->phoneRecipientService->findRecipientByPhone($phone);
        
        if (!$recipientData) {
            $errors[] = 'المستلم غير موجود';
        } else {
            $recipient = $recipientData['model'];
            $recipientName = $this->phoneRecipientService->maskName($recipientData['name']);
            $recipientWallet = $recipient->wallet;
            
            if (!$recipientWallet) {
                $errors[] = 'المستلم ليس لديه محفظة';
            } elseif ($wallet->id === $recipientWallet->id) {
                $errors[] = 'لا يمكن التحويل إلى نفس المحفظة';
            }
        }

        // Validate amount
        $amount = (float) $request->amount;
        $minAmount = config('api.wallet_transfer.min_amount', 10);
        $maxAmount = config('api.wallet_transfer.max_amount', 10000);

        if ($amount < $minAmount) {
            $errors[] = "المبلغ أقل من الحد الأدنى ({$minAmount} جنيه)";
        }

        if ($amount > $maxAmount) {
            $errors[] = "المبلغ أكبر من الحد الأقصى ({$maxAmount} جنيه)";
        }

        // Check balance
        $fee = $wallet->calculateTransferFee($amount);
        $total = $amount + $fee;

        if (!$wallet->hasSufficientBalance($total)) {
            $errors[] = 'الرصيد غير كافي لإتمام التحويل';
        }

        // Check daily limits
        try {
            $today = now()->startOfDay();
            
            $dailyTotal = WalletTransfer::where('from_wallet_id', $wallet->id)
                ->where('status', WalletTransfer::STATUS_COMPLETED)
                ->where('created_at', '>=', $today)
                ->sum('amount');

            $dailyLimit = config('api.wallet_transfer.daily_limit', 50000);
            
            if (($dailyTotal + $amount) > $dailyLimit) {
                $errors[] = "تجاوز الحد اليومي للتحويلات ({$dailyLimit} جنيه)";
            }

            $dailyCount = WalletTransfer::where('from_wallet_id', $wallet->id)
                ->where('status', WalletTransfer::STATUS_COMPLETED)
                ->where('created_at', '>=', $today)
                ->count();

            $countLimit = config('api.wallet_transfer.daily_count_limit', 20);
            
            if ($dailyCount >= $countLimit) {
                $errors[] = "تجاوز عدد التحويلات اليومية المسموحة ({$countLimit} تحويل)";
            }

            // Warning for large amounts
            $suspiciousThreshold = config('api.wallet_transfer.suspicious_amount_threshold', 5000);
            if ($amount >= $suspiciousThreshold) {
                $warnings[] = 'هذا التحويل سيخضع للمراجعة بسبب المبلغ الكبير';
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        return $this->successResponse([
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'recipient_name' => $recipientName,
            'fee_info' => [
                'amount' => $amount,
                'fee' => $fee,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Transfer balance to another wallet
     * POST /api/v1/{guard}/wallet/transfer
     */
    public function transfer(Request $request)
    {
        // Rate limiting
        $key = 'wallet-transfer:' . $request->user()->id;
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return $this->errorResponse("تم تجاوز الحد المسموح. حاول مرة أخرى بعد {$seconds} ثانية", 429);
        }

        RateLimiter::hit($key, 60); // 5 attempts per minute

        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:' . config('api.wallet_transfer.min_amount', 10) . '|max:' . config('api.wallet_transfer.max_amount', 10000),
            'description' => 'nullable|string|max:500',
            'wallet_password' => 'nullable|string|size:4',
        ]);

        $user = $request->user();
        $wallet = $user->getOrCreateWallet();

        try {
            // Format and find recipient by phone
            $phone = $this->phoneRecipientService->formatEgyptianPhone($request->phone);
            $recipientData = $this->phoneRecipientService->findRecipientByPhone($phone);
            
            if (!$recipientData) {
                return $this->errorResponse('المستلم غير موجود', 404);
            }
            
            $recipient = $recipientData['model'];
            $recipientWallet = $recipient->getOrCreateWallet();

            // Collect security data
            $securityData = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_info' => [
                    'platform' => $request->header('X-Platform'),
                    'version' => $request->header('X-App-Version'),
                    'device_id' => $request->header('X-Device-ID'),
                ],
            ];

            // Perform transfer
            $transfer = $wallet->transferTo(
                $recipientWallet,
                (float) $request->amount,
                $request->description,
                $securityData,
                $request->wallet_password
            );

            RateLimiter::clear($key); // Clear rate limit on successful transfer

            return $this->successResponse([
                'transfer' => new WalletTransferResource($transfer),
                'message' => 'تم التحويل بنجاح',
            ], 'تم التحويل بنجاح');

        } catch (ValidationException $ve) {
            throw $ve; // Rethrow validation exceptions
        }
        catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get transfer history
     * GET /api/v1/{guard}/wallet/transfers
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $perPage = min($request->get('per_page', 20), 50);
        
        $query = WalletTransfer::where(function ($q) use ($wallet) {
            $q->where('from_wallet_id', $wallet->id)
              ->orWhere('to_wallet_id', $wallet->id);
        });

        // Filter by type
        if ($request->has('type')) {
            if ($request->type === 'sent') {
                $query->where('from_wallet_id', $wallet->id);
            } elseif ($request->type === 'received') {
                $query->where('to_wallet_id', $wallet->id);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->with(['fromWallet.walletable', 'toWallet.walletable'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($transfers, WalletTransferResource::class);
    }

    /**
     * Get single transfer details
     * GET /api/v1/{guard}/wallet/transfers/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $transfer = WalletTransfer::where(function ($q) use ($wallet) {
            $q->where('from_wallet_id', $wallet->id)
              ->orWhere('to_wallet_id', $wallet->id);
        })->findOrFail($id);

        return $this->successResponse([
            'transfer' => new WalletTransferResource($transfer->load(['fromWallet.walletable', 'toWallet.walletable'])),
        ]);
    }

    /**
     * Get transfer statistics
     * GET /api/v1/{guard}/wallet/transfer/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $wallet = $user->getOrCreateWallet();
        
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        $stats = [
            'today' => [
                'sent_amount' => WalletTransfer::where('from_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $today)
                    ->sum('amount'),
                'sent_count' => WalletTransfer::where('from_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $today)
                    ->count(),
                'received_amount' => WalletTransfer::where('to_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $today)
                    ->sum('amount'),
                'received_count' => WalletTransfer::where('to_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $today)
                    ->count(),
            ],
            'this_month' => [
                'sent_amount' => WalletTransfer::where('from_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $thisMonth)
                    ->sum('amount'),
                'sent_count' => WalletTransfer::where('from_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $thisMonth)
                    ->count(),
                'received_amount' => WalletTransfer::where('to_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $thisMonth)
                    ->sum('amount'),
                'received_count' => WalletTransfer::where('to_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $thisMonth)
                    ->count(),
            ],
            'limits' => [
                'daily_limit' => config('api.wallet_transfer.daily_limit', 50000),
                'daily_used' => WalletTransfer::where('from_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $today)
                    ->sum('amount'),
                'daily_remaining' => max(0, config('api.wallet_transfer.daily_limit', 50000) - 
                    WalletTransfer::where('from_wallet_id', $wallet->id)
                        ->where('status', WalletTransfer::STATUS_COMPLETED)
                        ->where('created_at', '>=', $today)
                        ->sum('amount')),
                'daily_count_limit' => config('api.wallet_transfer.daily_count_limit', 20),
                'daily_count_used' => WalletTransfer::where('from_wallet_id', $wallet->id)
                    ->where('status', WalletTransfer::STATUS_COMPLETED)
                    ->where('created_at', '>=', $today)
                    ->count(),
            ],
        ];

        return $this->successResponse($stats);
    }
}
