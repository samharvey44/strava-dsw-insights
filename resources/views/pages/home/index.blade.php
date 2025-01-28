@use(App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisScoringService)
@use(App\Services\Strava\StravaActivityDswAnalysisScoreBandEnum)

<x-app page-title="Home">
    <div class="pt-3">
        @if(!auth()->user()->hasActiveStravaConnection())
            <div class="alert alert-warning text-center" role="alert">
                You have not connected your Strava account.<br/>
                Please connect your Strava account to view your insights.

                <div class="mt-3">
                    <a href="{{ route('strava.auth.initiate') }}" class="btn btn-warning">
                        <i class="bi bi-link"></i>
                        Connect Strava
                    </a>
                </div>
            </div>
        @else
            @if($activities->isNotEmpty())
                <div class="d-flex align-items-center justify-content-center">
                    <div style="max-width: 800px" class="d-flex flex-column flex-grow-1">
            @endif

            @forelse($activities as $activity)
                <div class="card mb-3">
                    <p class="card-header">
                        <span class="fs-5 fw-bolder">
                            {{ $activity->name }}
                        </span>
                        @if($activity->dswAnalysis)
                            <br class="d-md-none" />

                            @if($activity->dswAnalysis->treadmill)
                                <span class="badge bg-dark float-end d-md-block d-none ms-1">
                                    Treadmill
                                </span>
                            @endif
                            @if($activity->dswAnalysis->intervals)
                                <span class="badge bg-danger float-end d-md-block d-none ms-1">
                                    Intervals
                                </span>
                            @endif

                            <span class="badge bg-{{ $activity->dswAnalysis->dswType->typeGroup->display_class }} float-md-end">
                                {{ $activity->dswAnalysis->dswType->name }}
                            </span>

                            @if($activity->dswAnalysis->treadmill)
                                <span class="badge bg-dark d-md-none">
                                    Treadmill
                                </span>
                            @endif
                            @if($activity->dswAnalysis->intervals)
                                <span class="badge bg-danger d-md-none">
                                    Intervals
                                </span>
                            @endif
                        @endif
                        <br/>
                        {{ $activity->started_at->setTimezone($activity->timezone)->format('d/m/Y \a\t H:i') }}
                    </p>

                    <div class="card-body">
                        @if($activity->description)
                            {!! nl2br(htmlspecialchars($activity->description)) !!}

                            <hr />
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <p class="card-text">
                                    <strong>Distance:</strong> {{ number_format($activity->distance_meters / 1000, 2) }} km<br />
                                    <br/>
                                    <strong>Moving Time:</strong> {{ gmdate('H:i:s', $activity->moving_time_seconds) }}<br />
                                    <strong>Elapsed Time:</strong> {{ gmdate('H:i:s', $activity->elapsed_time_seconds) }}<br />
                                    <strong>Elevation Gain:</strong> {{ number_format($activity->elevation_gain_meters ?? 0, 2) }} m<br />
                                    <br/>
                                    <strong>Avg. Pace:</strong> {{ gmdate('i:s', 1000 / $activity->average_speed_meters_per_second) }}/km<br />
                                    @if(!is_null($activity->average_heartrate))
                                        <strong>Avg. HR:</strong> {{ number_format($activity->average_heartrate) }}bpm<br />
                                    @endif
                                    @if(!is_null($activity->average_watts))
                                        <strong>Avg. Power:</strong> {{ number_format($activity->average_watts) }}W<br />
                                    @endif
                                    @if($activity->dswAnalysis)
                                        <br/>
                                        <strong>DSW Score:</strong>
                                        @switch(app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand($activity, $scoreBands))
                                            @case(StravaActivityDswAnalysisScoreBandEnum::BAND_1)
                                                <span class="badge bg-danger" data-bs-title="Low score" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                            @case(StravaActivityDswAnalysisScoreBandEnum::BAND_2)
                                                <span class="badge bg-warning" data-bs-title="Average score" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                            @case(StravaActivityDswAnalysisScoreBandEnum::BAND_3)
                                                <span class="badge bg-success" data-bs-title="Good score" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                            @case(StravaActivityDswAnalysisScoreBandEnum::MISSING_HEARTRATE)
                                                <span class="badge bg-dark-subtle" data-bs-title="Missing heart rate data" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                            @case(StravaActivityDswAnalysisScoreBandEnum::MISSING_POWER)
                                                <span class="badge bg-dark-subtle" data-bs-title="Missing power data" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                            @case(StravaActivityDswAnalysisScoreBandEnum::MISSING_ANALYSIS)
                                                <span class="badge bg-dark-subtle" data-bs-title="Activity not analysed" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                            @case(StravaActivityDswAnalysisScoreBandEnum::NOT_ENOUGH_DATA)
                                                <span class="badge bg-dark-subtle" data-bs-title="Not enough data for DSW Type" data-bs-toggle="tooltip">
                                                    {{ number_format($activity->dswAnalysis->dsw_score) }}
                                                </span>
                                                @break
                                        @endswitch
                                        <br />
                                    @endif
                                </p>
                            </div>

                            @if($activity->summary_polyline)
                                <div class="col-md-6 mt-md-0 mt-3">
                                    <div
                                        style="height: 300px;"
                                        class="w-100 border border-1 rounded"
                                        id="activity-map-container-{{ $activity->id }}"
                                        data-map-polyline="{{ base64_encode($activity->summary_polyline) }}"
                                    >
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info text-center" role="alert">
                    <span class="fs-6 fw-bolder">No activities found</span><br />
                    <small>
                        If you've only just connected your account, we might still be getting your data from Strava.<br />
                        Check back in a few minutes.
                    </small>
                </div>
            @endforelse

            @if($activities->hasPages())
                <div>
                    {{ $activities->links() }}
                </div>
            @endif

            @if($activities->isNotEmpty())
                    </div>
                </div>
            @endif
        @endif
    </div>

    @push('body_scripts')
        @vite('resources/js/home.js')
    @endpush
</x-app>
