<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'code' => 'required|string|min:4|max:10',
            'method' => 'sometimes|in:whatsapp,sms',
            'fcm_token' => 'sometimes|string|max:500',
        ];
    }
}
