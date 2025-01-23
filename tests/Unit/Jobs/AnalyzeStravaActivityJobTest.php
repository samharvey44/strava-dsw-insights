<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AnalyzeStravaActivityJob;
use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalyzeStravaActivityJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_strava_activity_re_analyzable(): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        DswType::factory()->times(5)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);

        $mockedAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedActivitiesService = Mockery::mock(StravaActivitiesService::class);

        $mockedAnalysisService->shouldReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            )
            ->once()
            ->andReturnTrue();
        $mockedActivitiesService->shouldReceive('fetchActivityByStravaId')
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $stravaActivity->rawActivity->strava_activity_id
            )
            ->once();
        $mockedAnalysisService->shouldReceive('performAnalysis')
            ->with(Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)))
            ->once();

        app()->instance(StravaActivityDswAnalysisService::class, $mockedAnalysisService);
        app()->instance(StravaActivitiesService::class, $mockedActivitiesService);

        app(AnalyzeStravaActivityJob::class, [
            'stravaActivity' => $stravaActivity,
        ])->handle();
    }

    public function test_analyze_strava_activity_not_re_analyzable(): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        DswType::factory()->times(5)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);

        $mockedAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedActivitiesService = Mockery::mock(StravaActivitiesService::class);

        $mockedAnalysisService->shouldReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            )
            ->once()
            ->andReturnFalse();
        $mockedActivitiesService->shouldNotReceive('fetchActivityByStravaId');
        $mockedAnalysisService->shouldNotReceive('performAnalysis');

        app()->instance(StravaActivityDswAnalysisService::class, $mockedAnalysisService);
        app()->instance(StravaActivitiesService::class, $mockedActivitiesService);

        app(AnalyzeStravaActivityJob::class, [
            'stravaActivity' => $stravaActivity,
        ])->handle();
    }
}
