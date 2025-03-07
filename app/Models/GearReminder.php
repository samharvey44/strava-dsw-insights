<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GearReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'gear_id',
        'name',
        'trigger_after_number_of_activities',
        'current_number_of_activities',
        'last_triggered',
    ];

    public function gear(): BelongsTo
    {
        return $this->belongsTo(Gear::class, 'gear_id');
    }

    protected function casts(): array
    {
        return [
            'last_triggered' => 'datetime',
        ];
    }
}
