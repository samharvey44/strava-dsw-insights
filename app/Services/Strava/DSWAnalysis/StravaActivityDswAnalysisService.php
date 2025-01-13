<?php

namespace App\Services\Strava\DSWAnalysis;

use App\Models\DswType;
use App\Models\StravaActivity;
use App\Models\StravaActivityDswAnalysis;
use Illuminate\Support\Collection;

class StravaActivityDswAnalysisService
{
    public function performAnalysis(StravaActivity $stravaActivity): void
    {
        $allDswTypes = DswType::with('typeGroup')->get();

        $dswType = $this->determineDswType($stravaActivity, $allDswTypes);

        if (is_null($dswType)) {
            // This (probably) isn't a DSW activity, so we won't analyse it.
            return;
        }

        $isIntervals = $this->determineIsIntervals($stravaActivity, $dswType);
        $dswScore = $this->calculateDswScore($stravaActivity);

        StravaActivityDswAnalysis::create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => $dswType->id,
            'intervals' => $isIntervals,
            'dsw_score' => $dswScore,
        ]);
    }

    public function determineDswType(StravaActivity $stravaActivity, Collection $allDswTypes): ?DswType
    {
        $dswTypeFromActivityTitle = trim(
            explode('Garmin DSW - ', $stravaActivity->name)[1] ?? ''
        );

        if ($dswTypeFromActivityTitle === '') {
            return null;
        }

        return $allDswTypes->first(
            fn (DswType $dswType) => $dswType->name === $dswTypeFromActivityTitle,
        );
    }

    public function determineIsIntervals(StravaActivity $stravaActivity, DswType $dswType): bool
    {
        return str_contains(
            $stravaActivity->description ?? '',
            'recover',
        ) && $dswType->typeGroup->has_intervals;
    }

    public function calculateDswScore(StravaActivity $stravaActivity): float
    {
        $scoreMultiplier = ! empty($stravaActivity->average_watts)
            ? $stravaActivity->average_watts
            : $stravaActivity->average_heartrate;

        if (is_null($scoreMultiplier)) {
            $scoreMultiplier = 0;
        }

        return ($scoreMultiplier / $stravaActivity->average_speed_meters_per_second) * 100;
    }
}
