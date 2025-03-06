<?php

namespace App\Services\Home;

use App\Models\StravaActivityDswAnalysis;
use Illuminate\Database\Eloquent\Builder;

class HomeFilteringService
{
    public function applyFiltersAndSort(
        Builder $activitiesQuery,
        array $filters,
        ?string $sort,
        ?string $sortDirection
    ): Builder {
        $activitiesQuery = $this->applyDswTypeFilters($activitiesQuery, $filters);
        $activitiesQuery = $this->applyUnanalysedFilter($activitiesQuery, $filters);
        $activitiesQuery = $this->applyIntervalsFilters($activitiesQuery, $filters);
        $activitiesQuery = $this->applyTreadmillFilters($activitiesQuery, $filters);
        $activitiesQuery = $this->applySort($activitiesQuery, $sort, $sortDirection);

        return $activitiesQuery;
    }

    private function applyDswTypeFilters(Builder $activitiesQuery, array $filters): Builder
    {
        $dswTypeFilters = array_filter(
            $filters,
            fn (string $key) => str_starts_with($key, 'dsw_type_'),
            ARRAY_FILTER_USE_KEY
        );

        $typesToInclude = [];
        $typesToExclude = [];

        foreach ($dswTypeFilters as $dswTypeFilter => $value) {
            $dswTypeId = str_replace('dsw_type_', '', $dswTypeFilter);

            if (! is_numeric($dswTypeId)) {
                continue;
            }

            if ($value) {
                $typesToInclude[] = $dswTypeId;

                continue;
            }

            $typesToExclude[] = $dswTypeId;
        }

        if ($typesToInclude || $typesToExclude) {
            $activitiesQuery->where(function (Builder $query) use ($typesToInclude, $typesToExclude) {
                $query->whereHas('dswAnalysis', function (Builder $query) use ($typesToInclude, $typesToExclude) {
                    $query->whereIn('dsw_type_id', $typesToInclude)
                        ->whereNotIn('dsw_type_id', $typesToExclude);
                })->orWhereDoesntHave('dswAnalysis');
            });
        }

        return $activitiesQuery;
    }

    private function applyUnanalysedFilter(Builder $activitiesQuery, array $filters): Builder
    {
        if (! ($filters['unanalysed_activities'] ?? true)) {
            return $activitiesQuery->whereHas('dswAnalysis');
        }

        return $activitiesQuery;
    }

    private function applyIntervalsFilters(Builder $activitiesQuery, array $filters): Builder
    {
        $valuesToInclude = array_map(fn ($value) => (bool) $value, [
            $filters['interval_activities'] ?? false,
            ! ($filters['non_interval_activities'] ?? false),
        ]);
        sort($valuesToInclude);

        if ($valuesToInclude !== [false, true]) {
            return $activitiesQuery->where(function (Builder $query) use ($valuesToInclude) {
                $query->whereHas('dswAnalysis', function (Builder $query) use ($valuesToInclude) {
                    $query->whereIn('intervals', $valuesToInclude);
                })->orWhereDoesntHave('dswAnalysis');
            });
        }

        return $activitiesQuery;
    }

    private function applyTreadmillFilters(Builder $activitiesQuery, array $filters): Builder
    {
        $valuesToInclude = array_map(fn ($value) => (bool) $value, [
            $filters['treadmill_activities'] ?? false,
            ! ($filters['non_treadmill_activities'] ?? false),
        ]);
        sort($valuesToInclude);

        if ($valuesToInclude !== [false, true]) {
            return $activitiesQuery->where(function (Builder $query) use ($valuesToInclude) {
                $query->whereHas('dswAnalysis', function (Builder $query) use ($valuesToInclude) {
                    $query->whereIn('treadmill', $valuesToInclude);
                })->orWhereDoesntHave('dswAnalysis');
            });
        }

        return $activitiesQuery;
    }

    private function applySort(Builder $activitiesQuery, ?string $sort, ?string $sortDirection): Builder
    {
        $sort ??= 'started_at';
        $sortDirection ??= 'desc';

        if (
            ! in_array($sort, ['started_at', 'dsw_score', 'distance_meters'])
            || ! in_array($sortDirection, ['asc', 'desc'])
        ) {
            return $activitiesQuery->orderBy('started_at', 'desc');
        }

        if ($sort === 'dsw_score') {
            return $activitiesQuery->orderBy(
                StravaActivityDswAnalysis::select('dsw_score')
                    ->whereColumn('strava_activity_id', 'strava_activities.id')
                    ->limit(1),
                $sortDirection
            );
        }

        return $activitiesQuery->orderBy($sort, $sortDirection);
    }
}
