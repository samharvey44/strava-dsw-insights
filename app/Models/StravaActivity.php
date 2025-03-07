<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StravaActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'strava_raw_activity_id',
        'is_summary',
        'name',
        'description',
        'distance_meters',
        'moving_time_seconds',
        'elapsed_time_seconds',
        'elevation_gain_meters',
        'started_at',
        'timezone',
        'summary_polyline',
        'average_speed_meters_per_second',
        'max_speed_meters_per_second',
        'average_heartrate',
        'max_heartrate',
        'average_watts',
        'max_watts',
    ];

    public function rawActivity(): BelongsTo
    {
        return $this->belongsTo(StravaRawActivity::class, 'strava_raw_activity_id');
    }

    public function dswAnalysis(): HasOne
    {
        return $this->hasOne(StravaActivityDswAnalysis::class, 'strava_activity_id');
    }

    public function gears(): BelongsToMany
    {
        return $this->belongsToMany(Gear::class, 'strava_activity_gear', 'strava_activity_id', 'gear_id')
            ->withTimestamps();
    }

    public function scopeByUser(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user) {
            $query->whereHas('rawActivity', function (Builder $query) use ($user) {
                $query->where('strava_connection_id', $user->stravaConnection?->id);
            });
        });
    }

    protected function casts(): array
    {
        return [
            'is_summary' => 'boolean',
            'elevation_gain_meters' => 'decimal:2',
            'started_at' => 'datetime',
            'average_speed_meters_per_second' => 'decimal:2',
            'max_speed_meters_per_second' => 'decimal:2',
            'average_heartrate' => 'decimal:2',
            'max_heartrate' => 'decimal:2',
            'average_watts' => 'decimal:2',
            'max_watts' => 'decimal:2',
        ];
    }
}
