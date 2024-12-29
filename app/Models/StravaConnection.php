<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function rawActivities(): HasMany
    {
        return $this->hasMany(StravaRawActivity::class, 'strava_connection_id');
    }

    public function disable(): void
    {
        $this->update([
            'active' => false,
        ]);
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
