<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_type' => $this->walletable_type,
            'owner_id' => $this->walletable_id,
            'balance' => (float) $this->balance,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
