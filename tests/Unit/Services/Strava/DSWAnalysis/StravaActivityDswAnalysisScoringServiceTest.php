<?php

namespace Tests\Unit\Services\Strava\DSWAnalysis;

use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaActivityDswAnalysis;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisScoringService;
use App\Services\Strava\StravaActivityDswAnalysisScoreBandEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class StravaActivityDswAnalysisScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_activity_score_band_no_dsw_analysis(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::MISSING_ANALYSIS, $activityScoreBand);
    }

    public function test_get_activity_score_band_missing_average_heartrate(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
            'average_heartrate' => null,
            'average_watts' => fake()->randomFloat(2, 0, 200),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::MISSING_HEARTRATE, $activityScoreBand);
    }

    public function test_get_activity_score_band_missing_average_watts(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
            'average_heartrate' => fake()->randomFloat(2, 0, 200),
            'average_watts' => null,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::MISSING_POWER, $activityScoreBand);
    }

    public function test_get_activity_score_band_no_score_bands_provided(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect());
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );
    }

    public function test_get_activity_score_band_score_bands_provided(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldNotReceive('getScoreBandsByType');
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
            collect(),
        );
    }

    public function test_get_activity_score_band_not_intervals_not_enough_data(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => false,
        ]);

        $otherDswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:1" => [
                    'band_1' => fake()->randomFloat(2, 0, 100),
                    'band_2' => fake()->randomFloat(2, 101, 200),
                ],
                "{$otherDswType->id}:0" => [
                    'band_1' => fake()->randomFloat(2, 0, 100),
                    'band_2' => fake()->randomFloat(2, 101, 200),
                ],
                "{$otherDswType->id}:1" => [
                    'band_1' => fake()->randomFloat(2, 0, 100),
                    'band_2' => fake()->randomFloat(2, 101, 200),
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::NOT_ENOUGH_DATA, $activityScoreBand);
    }

    public function test_get_activity_score_band_intervals_not_enough_data(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => true,
        ]);

        $otherDswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:0" => [
                    'band_1' => fake()->randomFloat(2, 0, 100),
                    'band_2' => fake()->randomFloat(2, 101, 200),
                ],
                "{$otherDswType->id}:0" => [
                    'band_1' => fake()->randomFloat(2, 0, 100),
                    'band_2' => fake()->randomFloat(2, 101, 200),
                ],
                "{$otherDswType->id}:1" => [
                    'band_1' => fake()->randomFloat(2, 0, 100),
                    'band_2' => fake()->randomFloat(2, 101, 200),
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::NOT_ENOUGH_DATA, $activityScoreBand);
    }

    public function test_get_activity_score_band_less_than_band_1(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => $intervals = rand(0, 1),
            'dsw_score' => 99,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:{$intervals}" => [
                    'band_1' => 100,
                    'band_2' => 101,
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::BAND_1, $activityScoreBand);
    }

    public function test_get_activity_score_band_equal_to_band_1(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => $intervals = rand(0, 1),
            'dsw_score' => 100,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:{$intervals}" => [
                    'band_1' => 100,
                    'band_2' => 101,
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::BAND_1, $activityScoreBand);
    }

    public function test_get_activity_score_band_less_than_band_2(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => $intervals = rand(0, 1),
            'dsw_score' => 101,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:{$intervals}" => [
                    'band_1' => 100,
                    'band_2' => 102,
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::BAND_2, $activityScoreBand);
    }

    public function test_get_activity_score_band_equal_to_band_2(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => $intervals = rand(0, 1),
            'dsw_score' => 102,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:{$intervals}" => [
                    'band_1' => 100,
                    'band_2' => 102,
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::BAND_2, $activityScoreBand);
    }

    public function test_get_activity_score_band_band_3(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => ($stravaConnection =
                    StravaConnection::factory()->create([
                        'user_id' => User::factory()->create()->id,
                    ])
                )->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => ($dswType =
                DswType::factory()->create([
                    'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
                ])
            )->id,
            'intervals' => $intervals = rand(0, 1),
            'dsw_score' => 103,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect([
                "{$dswType->id}:{$intervals}" => [
                    'band_1' => 100,
                    'band_2' => 102,
                ],
            ]));
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        $activityScoreBand = app(StravaActivityDswAnalysisScoringService::class)->getActivityScoreBand(
            $stravaActivity,
        );

        $this->assertEquals(StravaActivityDswAnalysisScoreBandEnum::BAND_3, $activityScoreBand);
    }

    public function test_get_score_bands_by_type_analysis_present(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $oldestUpdatedAnalysisActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $oldestUpdatedAnalysisActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'updated_at' => now()->subSeconds(3),
        ]);

        $newestUpdatedAnalysisActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
        ]);
        $newestUpdatedAnalysis = StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $newestUpdatedAnalysisActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'updated_at' => now()->subSeconds(2),
        ]);

        $activityForDifferentUser = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $activityForDifferentUser->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'updated_at' => now()->subSeconds(1),
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByTypeUncached')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect());
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        app(StravaActivityDswAnalysisScoringService::class)->getScoreBandsByType($stravaConnection);

        $this->assertEquals(
            collect(),
            Cache::get("strava-activity-dsw-analysis-scoring-service:{$stravaConnection->id}:{$newestUpdatedAnalysis->updated_at->timestamp}"),
        );
    }

    public function test_get_score_bands_by_type_analysis_not_present(): void
    {
        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $activityForDifferentUser = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $activityForDifferentUser->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $mockedStravaActivityDswAnalysisScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class)
            ->makePartial();
        $mockedStravaActivityDswAnalysisScoringService->shouldReceive('getScoreBandsByTypeUncached')
            ->once()
            ->with(
                Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)),
            )
            ->andReturn(collect());
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedStravaActivityDswAnalysisScoringService);

        app(StravaActivityDswAnalysisScoringService::class)->getScoreBandsByType($stravaConnection);

        $this->assertEquals(
            collect(),
            Cache::get("strava-activity-dsw-analysis-scoring-service:{$stravaConnection->id}:NULL"),
        );
    }

    public function test_get_score_bands_by_type_uncached(): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        foreach (range(0, 1) as $intervals) {
            $firstStravaActivity = StravaActivity::factory()->create([
                'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                    'strava_connection_id' => $stravaConnection->id,
                ])->id,
                'average_heartrate' => fake()->randomFloat(2, 0, 200),
                'average_watts' => fake()->randomFloat(2, 0, 200),
            ]);

            StravaActivityDswAnalysis::factory()->create([
                'strava_activity_id' => $firstStravaActivity->id,
                'dsw_type_id' => $dswType->id,
                'intervals' => $intervals,
                'dsw_score' => 100 * $intervals,
            ]);

            $secondStravaActivity = StravaActivity::factory()->create([
                'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                    'strava_connection_id' => $stravaConnection->id,
                ])->id,
                'average_heartrate' => fake()->randomFloat(2, 0, 200),
                'average_watts' => fake()->randomFloat(2, 0, 200),
            ]);

            StravaActivityDswAnalysis::factory()->create([
                'strava_activity_id' => $secondStravaActivity->id,
                'dsw_type_id' => $dswType->id,
                'intervals' => $intervals,
                'dsw_score' => 50 + (100 * $intervals),
            ]);

            $thirdStravaActivity = StravaActivity::factory()->create([
                'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                    'strava_connection_id' => $stravaConnection->id,
                ])->id,
                'average_heartrate' => fake()->randomFloat(2, 0, 200),
                'average_watts' => fake()->randomFloat(2, 0, 200),
            ]);

            StravaActivityDswAnalysis::factory()->create([
                'strava_activity_id' => $thirdStravaActivity->id,
                'dsw_type_id' => $dswType->id,
                'intervals' => $intervals,
                'dsw_score' => 100 + (100 * $intervals),
            ]);

            $activityWithNoHeartRate = StravaActivity::factory()->create([
                'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                    'strava_connection_id' => $stravaConnection->id,
                ])->id,
                'average_heartrate' => null,
                'average_watts' => fake()->randomFloat(2, 0, 200),
            ]);

            StravaActivityDswAnalysis::factory()->create([
                'strava_activity_id' => $activityWithNoHeartRate->id,
                'dsw_type_id' => $dswType->id,
                'intervals' => $intervals,
                'dsw_score' => 150,
            ]);

            $activityWithNoPower = StravaActivity::factory()->create([
                'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                    'strava_connection_id' => $stravaConnection->id,
                ])->id,
                'average_heartrate' => fake()->randomFloat(2, 0, 200),
                'average_watts' => null,
            ]);

            StravaActivityDswAnalysis::factory()->create([
                'strava_activity_id' => $activityWithNoPower->id,
                'dsw_type_id' => $dswType->id,
                'intervals' => $intervals,
                'dsw_score' => 200,
            ]);
        }

        $differentDswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $firstStravaActivityDifferentDswType = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
            'average_heartrate' => fake()->randomFloat(2, 0, 200),
            'average_watts' => fake()->randomFloat(2, 0, 200),
        ]);

        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $firstStravaActivityDifferentDswType->id,
            'dsw_type_id' => $differentDswType->id,
            'intervals' => $intervals,
            'dsw_score' => 0,
        ]);

        $secondStravaActivityDifferentDswType = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $stravaConnection->id,
            ])->id,
            'average_heartrate' => fake()->randomFloat(2, 0, 200),
            'average_watts' => fake()->randomFloat(2, 0, 200),
        ]);

        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $secondStravaActivityDifferentDswType->id,
            'dsw_type_id' => $differentDswType->id,
            'intervals' => $intervals,
            'dsw_score' => 50,
        ]);

        $activityForDifferentUser = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $activityForDifferentUser->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $scoreBandsUncached = app(StravaActivityDswAnalysisScoringService::class)->getScoreBandsByTypeUncached($stravaConnection);

        $this->assertEquals(
            collect([
                "{$dswType->id}:0" => [
                    'band_1' => 33,
                    'band_2' => 67,
                ],
                "{$dswType->id}:1" => [
                    'band_1' => 133,
                    'band_2' => 167,
                ],
            ]),
            $scoreBandsUncached,
        );
    }
}
