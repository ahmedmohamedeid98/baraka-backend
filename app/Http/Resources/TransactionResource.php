<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'amount' => (float) $this->amount,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'is_credit' => $this->is_credit,
            'is_debit' => $this->is_debit,
            'order_id' => $this->order_id,
            'subscription_id' => $this->subscription_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
