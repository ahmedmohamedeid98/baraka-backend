<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletChargeRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'payment_method_text' => $this->paymentMethodText,
            'payment_reference' => $this->payment_reference,
            'payment_screenshot' => $this->screenshotUrl,
            'notes' => $this->notes,
            'status' => $this->status,
            'status_text' => $this->statusText,
            'rejection_reason' => $this->rejection_reason,
            'is_resubmission' => (bool) $this->is_resubmission,
            'original_request_id' => $this->original_request_id,
            'can_be_resubmitted' => $this->canBeResubmitted(),
            'reviewed_by' => $this->when($this->reviewedBy, function () {
                return [
                    'id' => $this->reviewedBy->id,
                    'name' => $this->reviewedBy->name,
                ];
            }),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'transaction_id' => $this->transaction_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
