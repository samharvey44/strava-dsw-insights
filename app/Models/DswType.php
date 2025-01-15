<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DswType extends Model
{
    use HasFactory;

    protected $fillable = [
        'dsw_type_group_id',
        'name',
    ];

    public function typeGroup(): BelongsTo
    {
        return $this->belongsTo(DswTypeGroup::class, 'dsw_type_group_id');
    }

    public function dswAnalyses(): HasMany
    {
        return $this->hasMany(StravaActivityDswAnalysis::class, 'dsw_type_id');
    }
}
