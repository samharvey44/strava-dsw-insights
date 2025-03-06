@use(App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisScoringService)
@use(App\Services\Strava\StravaActivityDswAnalysisScoreBandEnum)

<x-app page-title="Home">
    <div class="pt-3">
        <div class="d-flex align-items-center justify-content-center">
            <div style="max-width: 800px" class="d-flex flex-column flex-grow-1">
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
                    <div>
                        <button class="btn btn-primary mb-3"
                                data-bs-toggle="modal"
                                data-bs-target="#activity_filters_modal"
                        >
                            <i class="bi bi-filter"></i>
                            Filter Activities
                        </button>
                    </div>

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
                                @if($filtersApplied)
                                    No activities matched the filters you applied.
                                @else
                                    If you've only just connected your account, we might still be getting your data from Strava.<br />
                                    Check back in a few minutes.
                                @endif
                            </small>
                        </div>
                    @endforelse
                @endif

                @if($activities->hasPages())
                    <div>
                        {{ $activities->withQueryString()->links() }}
                    </div>
                @endif

                <div class="modal modal-lg fade" tabindex="-1" id="activity_filters_modal">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Activities</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <p class="fs-6 fst-italic">DSW Type</p>

                                        @foreach($dswTypes->sortBy('typeGroup.id') as $dswType)
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" id="filter_dsw_type_{{ $dswType->id }}">
                                                <label class="form-check-label" for="filter_dsw_type_{{ $dswType->id }}">
                                                    <div class="d-flex">
                                                        <div style="width: 100px;">
                                                            {{ $dswType->name }}
                                                        </div>
                                                        <div class="bg-{{ $dswType->typeGroup->display_class }} rounded-circle d-inline-block mt-2" style="width: 10px; height: 10px;"></div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>

                                    <hr class="d-lg-none d-block mt-lg-0 mt-3" />

                                    <div class="col-lg-4">
                                        <p class="fs-6 fst-italic">Meta</p>

                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="filter_unanalysed_activities">
                                            <label class="form-check-label" for="filter_unanalysed_activities">Show Unanalysed Activities</label>
                                        </div>

                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="filter_interval_activities">
                                            <label class="form-check-label" for="filter_interval_activities">Show Intervals</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="filter_non_interval_activities">
                                            <label class="form-check-label" for="filter_non_interval_activities">Show Non-Intervals</label>
                                        </div>

                                        <div class="form-check form-switch mt-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="filter_treadmill_activities">
                                            <label class="form-check-label" for="filter_treadmill_activities">Show Treadmill</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="filter_non_treadmill_activities">
                                            <label class="form-check-label" for="filter_non_treadmill_activities">Show Non-Treadmill</label>
                                        </div>
                                    </div>

                                    <hr class="d-lg-none d-block mt-lg-0 mt-3" />

                                    <div class="col-lg-4">
                                        <p class="fs-6 fst-italic">Sorting</p>

                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-sm btn-light" id="sort_dsw_score">
                                                DSW Score
                                                <i class="bi bi-arrow-up sort-asc d-none"></i>
                                                <i class="bi bi-arrow-down sort-desc d-none"></i>
                                            </button>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-sm btn-light mt-2" id="sort_distance_meters">
                                                Distance
                                                <i class="bi bi-arrow-up sort-asc d-none"></i>
                                                <i class="bi bi-arrow-down sort-desc d-none"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="apply_dsw_filters">Apply Filters</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('body_scripts')
        @vite('resources/js/home.js')
    @endpush
</x-app>
