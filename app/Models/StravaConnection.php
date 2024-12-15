<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StravaConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'athlete_id',
        'access_token',
        'access_token_expiry',
        'refresh_token',
        'active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'active' => 'boolean',
        ];
    }
}
