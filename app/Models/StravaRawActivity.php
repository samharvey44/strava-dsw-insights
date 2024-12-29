<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StravaRawActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'strava_connection_id',
        'strava_activity_id',
        'data',
    ];

    public function stravaConnection(): BelongsTo
    {
        return $this->belongsTo(StravaConnection::class, 'strava_connection_id');
    }

    public function stravaActivity(): HasOne
    {
        return $this->hasOne(StravaActivity::class, 'strava_activity_id');
    }

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
