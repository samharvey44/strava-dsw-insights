<?php

namespace App\Services\DswTypes;

use App\Models\DswType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DswTypesService
{
    public function getAllTypes(bool $cached = true): Collection
    {
        $callback = fn () => DswType::with('typeGroup')->get();

        return $cached
            ? Cache::remember('all_dsw_types', now()->endOfDay(), $callback)
            : $callback();
    }
}
