<?php

namespace App\Services\Home;

use Illuminate\Database\Eloquent\Builder;

class HomeFilteringService
{
    public function applyFiltersAndSort(Builder $activitiesQuery, array $filters): Builder
    {
        $activitiesQuery = $this->applyDswTypeFilters($activitiesQuery, $filters);
        $activitiesQuery = $this->applyIntervalsFilters($activitiesQuery, $filters);
        $activitiesQuery = $this->applyTreadmillFilters($activitiesQuery, $filters);
        $activitiesQuery = $this->applySort($activitiesQuery, $filters);

        return $activitiesQuery;
    }

    private function applyDswTypeFilters(Builder $activitiesQuery, array $filters): Builder
    {
        $dswTypeFilters = array_filter(
            $filters,
            fn (mixed $value, string $key) => str_starts_with($key, 'dsw_type_'),
            ARRAY_FILTER_USE_BOTH
        );

        $typesToInclude = [];
        $typesToExclude = [];

        foreach ($dswTypeFilters as $dswTypeFilter => $value) {
            $dswTypeId = str_replace('dsw_type_', '', $dswTypeFilter);

            if (!is_numeric($dswTypeId)) {
                continue;
            }

            if ($value) {
                $typesToInclude[] = $dswTypeId;

                continue;
            }

            $typesToExclude[] = $dswTypeId;
        }

        if ($typesToInclude) {
            $activitiesQuery->whereHas('dswAnalysis', function (Builder $query) use ($typesToInclude) {
                $query->whereIn('dsw_type_id', $typesToInclude);
            });
        }

        if ($typesToExclude) {
            $activitiesQuery->whereDoesntHave('dswAnalysis', function (Builder $query) use ($typesToExclude) {
                $query->whereIn('dsw_type_id', $typesToExclude);
            });
        }

        return $activitiesQuery;
    }

    private function applyIntervalsFilters(Builder $activitiesQuery, array $filters): Builder
    {
        $valuesToInclude = array_map(fn ($value) => (bool) $value, [
            $filters['interval_activities'] ?? false,
            ! ($filters['non_interval_activities'] ?? false),
        ]);

        $activitiesQuery->whereHas('dswAnalysis', function (Builder $query) use ($valuesToInclude) {
            $query->whereIn('intervals', $valuesToInclude);
        });

        return $activitiesQuery;
    }

    private function applyTreadmillFilters(Builder $activitiesQuery, array $filters): Builder
    {
        $valuesToInclude = array_map(fn ($value) => (bool) $value, [
            $filters['treadmill_activities'] ?? false,
            ! ($filters['non_treadmill_activities'] ?? false),
        ]);

        $activitiesQuery->whereHas('dswAnalysis', function (Builder $query) use ($valuesToInclude) {
            $query->whereIn('treadmill', $valuesToInclude);
        });

        return $activitiesQuery;
    }

    private function applySort(Builder $activitiesQuery, array $filters): Builder
    {
        $sort = $filters['sort'] ?? 'started_at';
        $direction = $filters['sort_direction'] ?? 'desc';

        if (! in_array($sort, ['started_at', 'dsw_score'])) {
            return $activitiesQuery->orderBy('started_at', 'desc');
        }

        return $activitiesQuery->orderBy($sort, $direction);
    }
}
