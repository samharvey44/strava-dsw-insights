<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StravaActivityDswAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'strava_activity_id',
        'dsw_type_id',
        'intervals',
        'treadmill',
        'dsw_score',
        'notes',
    ];

    public function stravaActivity(): BelongsTo
    {
        return $this->belongsTo(StravaActivity::class, 'strava_activity_id');
    }

    public function dswType(): BelongsTo
    {
        return $this->belongsTo(DswType::class, 'dsw_type_id');
    }

    protected function casts(): array
    {
        return [
            'intervals' => 'boolean',
            'treadmill' => 'boolean',
        ];
    }
}
