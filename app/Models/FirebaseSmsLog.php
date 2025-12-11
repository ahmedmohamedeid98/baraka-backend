<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirebaseSmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'verification_id',
        'status',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
        ];
    }
}
