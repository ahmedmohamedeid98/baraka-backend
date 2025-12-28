<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentInstructionResource extends JsonResource
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
            'instruction' => $this->instruction,
            'font_size' => $this->font_size,
            'is_bold' => $this->is_bold,
            'color' => $this->color,
            'is_copyable' => $this->is_copyable,
            'is_link' => $this->is_link,
            'placeholder' => $this->placeholder,
        ];
    }
}
