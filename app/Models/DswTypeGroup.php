<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DswTypeGroup extends Model
{
    protected $fillable = [
        'name',
        'display_class',
        'has_intervals',
    ];

    public function dswTypes(): HasMany
    {
        return $this->hasMany(DswType::class, 'dsw_type_group_id');
    }

    protected function casts(): array
    {
        return [
            'has_intervals' => 'boolean',
        ];
    }
}
