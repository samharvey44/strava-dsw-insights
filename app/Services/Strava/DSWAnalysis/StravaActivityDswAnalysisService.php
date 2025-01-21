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
        $isTreadmill = $this->determineIsTreadmill($stravaActivity);
        $dswScore = $this->calculateDswScore($stravaActivity);

        StravaActivityDswAnalysis::create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => $dswType->id,
            'intervals' => $isIntervals,
            'treadmill' => $isTreadmill,
            'dsw_score' => $dswScore,
        ]);
    }

    public function determineDswType(StravaActivity $stravaActivity, Collection $allDswTypes): ?DswType
    {
        // See if we can find a DSW type from the activity title.
        $dswTypeInTitle = trim(
            explode(strtolower('Garmin DSW - '), strtolower($stravaActivity->name))[1] ?? ''
        );

        if ($dswTypeInTitle === '') {
            // Attempt to pull the DSW type from the activity description instead.
            return $allDswTypes->first(
                fn (DswType $dswType) => preg_match(
                    "/\bGarmin DSW - {$dswType->name}\b/i",
                    $stravaActivity->description ?? ''
                )
            );
        }

        return $allDswTypes->first(
            fn (DswType $dswType) => strtolower($dswType->name) === strtolower($dswTypeInTitle),
        );
    }

    public function determineIsIntervals(StravaActivity $stravaActivity, DswType $dswType): bool
    {
        return preg_match(
            '/\brecover\b/i',
            $stravaActivity->description ?? ''
        ) && $dswType->typeGroup->has_intervals;
    }

    public function determineIsTreadmill(StravaActivity $stravaActivity): bool
    {
        return preg_match(
            '/\btreadmill\b/i',
            $stravaActivity->description ?? ''
        );
    }

    public function calculateDswScore(StravaActivity $stravaActivity): int
    {
        if (is_null($stravaActivity->average_watts) && is_null($stravaActivity->average_heartrate)) {
            return 0;
        }

        // Apply power multiplier, higher score for higher power.
        $scoreWithPowerMultiplier = $stravaActivity->average_speed_meters_per_second * ($stravaActivity->average_watts ?: 1);

        // Penalise higher heart rates.
        $heartRateScorePenalty = $stravaActivity->average_heartrate
            ? $stravaActivity->average_heartrate / 200 // Assume 200 as max HR for the purposes of score calculation
            : 0;

        // Apply the penalty to the score initially calculated with the power multiplier.
        $scoreWithPowerMultiplierAndHeartRatePenalty = $scoreWithPowerMultiplier * (1 - $heartRateScorePenalty);

        if ($stravaActivity->elevation_gain_meters > 0) {
            // Apply a bonus for elevation gain.
            $scoreWithPowerMultiplierAndHeartRatePenalty *= (1 + ($stravaActivity->elevation_gain_meters / 1000));
        }

        return max(
            // Apply multiplier to final score to increase granularity.
            (int) round($scoreWithPowerMultiplierAndHeartRatePenalty * 100),
            0
        );
    }

    public function isReAnalysable(StravaActivity $stravaActivity, Collection $allDswTypes): bool
    {
        return ($stravaActivity->is_summary || $this->determineDswType($stravaActivity, $allDswTypes))
            && is_null($stravaActivity->dswAnalysis);
    }
}
