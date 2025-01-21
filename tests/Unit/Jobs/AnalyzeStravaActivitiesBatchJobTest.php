<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AnalyzeStravaActivitiesBatchJob;
use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaActivityDswAnalysis;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\Activities\StravaActivitiesService;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalyzeStravaActivitiesBatchJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_strava_activities_batch(): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $dswTypes = DswType::factory()->times(5)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $unanalyzedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);

        $analysedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $analysedActivity->id,
            'dsw_type_id' => $dswTypes->random()->id,
        ]);

        $mockedAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedActivitiesService = Mockery::mock(StravaActivitiesService::class);

        $mockedAnalysisService->shouldReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($unanalyzedActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            )
            ->once()
            ->andReturnTrue();
        $mockedActivitiesService->shouldReceive('fetchActivityByStravaId')
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $unanalyzedActivity->rawActivity->strava_activity_id
            )
            ->once();
        $mockedAnalysisService->shouldReceive('performAnalysis')
            ->with(Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($unanalyzedActivity)))
            ->once();

        $mockedAnalysisService->shouldNotReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($analysedActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            );
        $mockedActivitiesService->shouldNotReceive('fetchActivityByStravaId')
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $analysedActivity->rawActivity->strava_activity_id
            );
        $mockedAnalysisService->shouldNotReceive('performAnalysis')
            ->with(Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($analysedActivity)));

        app()->instance(StravaActivityDswAnalysisService::class, $mockedAnalysisService);
        app()->instance(StravaActivitiesService::class, $mockedActivitiesService);

        app(AnalyzeStravaActivitiesBatchJob::class, [
            'stravaConnection' => $stravaConnection,
            'limit' => 10,
            'offset' => 0,
        ])->handle();
    }

    public function test_analyze_strava_activities_batch_limit_and_offset(): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        DswType::factory()->times(5)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $firstUnanalyzedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        $secondUnanalyzedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        $thirdUnanalyzedActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);

        $mockedAnalysisService = Mockery::mock(StravaActivityDswAnalysisService::class);
        $mockedActivitiesService = Mockery::mock(StravaActivitiesService::class);

        $mockedAnalysisService->shouldNotReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($firstUnanalyzedActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            );
        $mockedActivitiesService->shouldNotReceive('fetchActivityByStravaId')
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $firstUnanalyzedActivity->rawActivity->strava_activity_id
            );
        $mockedAnalysisService->shouldNotReceive('performAnalysis')
            ->with(Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($firstUnanalyzedActivity)));

        $mockedAnalysisService->shouldReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($secondUnanalyzedActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            )
            ->once()
            ->andReturnTrue();
        $mockedActivitiesService->shouldReceive('fetchActivityByStravaId')
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $secondUnanalyzedActivity->rawActivity->strava_activity_id
            )
            ->once();
        $mockedAnalysisService->shouldReceive('performAnalysis')
            ->with(Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($secondUnanalyzedActivity)))
            ->once();

        $mockedAnalysisService->shouldNotReceive('isReAnalysable')
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($thirdUnanalyzedActivity)),
                Mockery::on(fn ($dswTypesArg) => $dswTypesArg->count() === 5),
            );
        $mockedActivitiesService->shouldNotReceive('fetchActivityByStravaId')
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
                $thirdUnanalyzedActivity->rawActivity->strava_activity_id
            );
        $mockedAnalysisService->shouldNotReceive('performAnalysis')
            ->with(Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($thirdUnanalyzedActivity)));

        app()->instance(StravaActivityDswAnalysisService::class, $mockedAnalysisService);
        app()->instance(StravaActivitiesService::class, $mockedActivitiesService);

        app(AnalyzeStravaActivitiesBatchJob::class, [
            'stravaConnection' => $stravaConnection,
            'limit' => 1,
            'offset' => 1,
        ])->handle();
    }
}
