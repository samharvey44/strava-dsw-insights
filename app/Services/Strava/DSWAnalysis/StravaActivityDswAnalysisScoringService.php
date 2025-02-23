<?php

namespace App\Services\Strava\DSWAnalysis;

use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Services\Strava\StravaActivityDswAnalysisScoreBandEnum;
use Cache;
use DB;
use Illuminate\Support\Collection;

class StravaActivityDswAnalysisScoringService
{
    public function getActivityScoreBand(
        StravaActivity $activity,
        ?Collection $scoreBands = null,
    ): StravaActivityDswAnalysisScoreBandEnum {
        if (is_null($activity->dswAnalysis)) {
            return StravaActivityDswAnalysisScoreBandEnum::MISSING_ANALYSIS;
        }

        if (is_null($activity->average_heartrate)) {
            return StravaActivityDswAnalysisScoreBandEnum::MISSING_HEARTRATE;
        }

        if (is_null($activity->average_watts)) {
            return StravaActivityDswAnalysisScoreBandEnum::MISSING_POWER;
        }

        $scoreBands ??= $this->getScoreBandsByType($activity->rawActivity->stravaConnection);

        $integerIntervals = (int) $activity->dswAnalysis->intervals;
        $scoreKey = "{$activity->dswAnalysis->dsw_type_id}:{$integerIntervals}";

        if (is_null($scoreBand = $scoreBands->get($scoreKey))) {
            return StravaActivityDswAnalysisScoreBandEnum::NOT_ENOUGH_DATA;
        }

        if ($activity->dswAnalysis->dsw_score <= $scoreBand['band_1']) {
            return StravaActivityDswAnalysisScoreBandEnum::BAND_1;
        }

        if ($activity->dswAnalysis->dsw_score <= $scoreBand['band_2']) {
            return StravaActivityDswAnalysisScoreBandEnum::BAND_2;
        }

        return StravaActivityDswAnalysisScoreBandEnum::BAND_3;
    }

    public function getScoreBandsByType(StravaConnection $stravaConnection): Collection
    {
        $lastUpdatedDswAnalysis = StravaActivity::byUser($stravaConnection->user)
            ->select(DB::raw('UNIX_TIMESTAMP(MAX(strava_activity_dsw_analyses.updated_at)) AS max_last_updated'))
            ->join('strava_activity_dsw_analyses', 'strava_activities.id', '=', 'strava_activity_dsw_analyses.strava_activity_id')
            ->first()?->max_last_updated ?? 'NULL';

        return Cache::remember(
            "strava_activity_dsw_analysis_scoring_service:{$stravaConnection->id}:{$lastUpdatedDswAnalysis}",
            now()->addDay(),
            fn () => $this->getScoreBandsByTypeUncached($stravaConnection),
        );
    }

    /**
     * @return Collection Collection of score bands, keyed [DSW Type ID:Intervals 1/0 => [Band => Score]]
     */
    public function getScoreBandsByTypeUncached(StravaConnection $stravaConnection): Collection
    {
        $analysis = StravaActivity::byUser($stravaConnection->user)
            ->select([
                DB::raw('CONCAT(strava_activity_dsw_analyses.dsw_type_id, ":", strava_activity_dsw_analyses.intervals) AS score_key'),
                DB::raw('MAX(strava_activity_dsw_analyses.dsw_score) - MIN(strava_activity_dsw_analyses.dsw_score) AS score_range'),
                DB::raw('MIN(strava_activity_dsw_analyses.dsw_score) AS min_score'),
            ])
            ->whereNotNull('strava_activities.average_heartrate')
            ->whereNotNull('strava_activities.average_watts')
            ->join('strava_activity_dsw_analyses', 'strava_activities.id', '=', 'strava_activity_dsw_analyses.strava_activity_id')
            ->groupBy('strava_activity_dsw_analyses.dsw_type_id', 'strava_activity_dsw_analyses.intervals')
            ->havingRaw('COUNT(strava_activity_dsw_analyses.id) >= 3')
            ->get();

        return $analysis->reduce(function (Collection $carry, object $item) {
            $carry[$item->score_key] = [
                'band_1' => round($item->min_score + ($item->score_range / 3)),
                'band_2' => round($item->min_score + (($item->score_range / 3) * 2)),
            ];

            return $carry;
        }, collect());
    }
}
