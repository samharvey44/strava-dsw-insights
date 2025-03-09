<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gear extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'image_path',
        'first_used',
        'decommissioned',
        'auto_attach_to_activities',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function stravaActivities(): BelongsToMany
    {
        return $this->belongsToMany(StravaActivity::class, 'strava_activity_gear', 'gear_id', 'strava_activity_id')
            ->withTimestamps();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(GearReminder::class, 'gear_id');
    }

    protected function casts(): array
    {
        return [
            'first_used' => 'date',
            'decommissioned' => 'date',
            'auto_attach_to_activities' => 'boolean',
        ];
    }
}
