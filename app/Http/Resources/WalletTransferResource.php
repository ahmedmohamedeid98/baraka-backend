<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $userWallet = $user?->wallet;
        $isSender = $userWallet && $this->from_wallet_id === $userWallet->id;
        
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'type' => $isSender ? 'sent' : 'received',
            
            // Sender info
            'sender' => [
                'type' => $this->from_user_type === 'App\\Models\\Vendor' ? 'vendor' : 'user',
                'id' => $this->from_user_id,
                'name' => $this->fromWallet?->walletable?->name_ar ?? $this->fromWallet?->walletable?->name ?? 'N/A',
                'wallet_id' => $this->from_wallet_id,
            ],
            
            // Receiver info
            'receiver' => [
                'type' => $this->to_user_type === 'App\\Models\\Vendor' ? 'vendor' : 'user',
                'id' => $this->to_user_id,
                'name' => $this->toWallet?->walletable?->name_ar ?? $this->toWallet?->walletable?->name ?? 'N/A',
                'wallet_id' => $this->to_wallet_id,
            ],
            
            // Amount details
            'amount' => (float) $this->amount,
            'fee' => (float) $this->fee,
            'total_deducted' => (float) $this->total_deducted,
            'amount_received' => (float) $this->amount_received,
            
            // Status
            'status' => $this->status,
            'status_text' => $this->status_text,
            
            // Additional info
            'description' => $this->description,
            'is_flagged' => $this->is_flagged,
            'flagged_reason' => $this->when($this->is_flagged, $this->flagged_reason),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
