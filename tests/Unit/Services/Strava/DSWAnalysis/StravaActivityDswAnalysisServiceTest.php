<?php

namespace Tests\Unit\Services\Strava\DSWAnalysis;

use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaActivityDswAnalysis;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StravaActivityDswAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public static function hasElevationGainDataProvider(): array
    {
        return [
            'has elevation gain' => [true],
            'no elevation gain' => [false],
        ];
    }

    public function test_successful_perform_analysis(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldReceive('determineDswType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($allDswTypesArg) => $allDswTypesArg->contains($dswType) && $allDswTypesArg->count() === 1),
            )
            ->andReturn($dswType);
        $mockedService->shouldReceive('determineIsIntervals')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($dswTypeArg) => $dswTypeArg->is($dswType)),
            )
            ->andReturn($intervals = fake()->boolean());
        $mockedService->shouldReceive('determineIsTreadmill')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
            )
            ->andReturn($treadmill = fake()->boolean());
        $mockedService->shouldReceive('calculateDswScore')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
            )
            ->andReturn($dswScore = fake()->randomNumber());

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        app(StravaActivityDswAnalysisService::class)->performAnalysis($stravaActivity);

        $this->assertDatabaseHas('strava_activity_dsw_analyses', [
            'strava_activity_id' => $stravaActivity->id,
            'dsw_type_id' => $dswType->id,
            'intervals' => $intervals,
            'treadmill' => $treadmill,
            'dsw_score' => $dswScore,
        ]);
    }

    public function test_perform_analysis_no_dsw_type_detected(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldReceive('determineDswType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($allDswTypesArg) => $allDswTypesArg->isEmpty()),
            )
            ->andReturnNull();
        $mockedService->shouldNotReceive('determineIsIntervals');
        $mockedService->shouldNotReceive('determineIsTreadmill');
        $mockedService->shouldNotReceive('calculateDswScore');

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        app(StravaActivityDswAnalysisService::class)->performAnalysis($stravaActivity);

        $this->assertDatabaseMissing('strava_activity_dsw_analyses', [
            'strava_activity_id' => $stravaActivity->id,
        ]);
    }

    public function test_determine_dsw_type_valid_type_in_name(): void
    {
        $dswTypeName = fake()->word();

        $dswType = DswType::factory()->create([
            'name' => $dswTypeName,
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'name' => "Garmin DSW - {$dswTypeName}",
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertTrue($dswType->is($determinedDswType));
    }

    public function test_determine_dsw_type_invalid_type_in_name(): void
    {
        DswType::factory()->create([
            'name' => fake()->unique()->word(),
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'name' => 'Garmin DSW - '.fake()->unique()->word(),
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertNull($determinedDswType);
    }

    public function test_determine_dsw_type_no_type_in_name(): void
    {
        DswType::factory()->create([
            'name' => fake()->unique()->word(),
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'name' => fake()->unique()->word(),
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertNull($determinedDswType);
    }

    public function test_determine_dsw_type_valid_type_in_description(): void
    {
        $dswTypeName = fake()->word();

        $dswType = DswType::factory()->create([
            'name' => $dswTypeName,
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => "Garmin DSW - {$dswTypeName}",
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertTrue($dswType->is($determinedDswType));
    }

    public function test_determine_dsw_type_invalid_type_in_description(): void
    {
        DswType::factory()->create([
            'name' => fake()->unique()->word(),
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => 'Garmin DSW - '.fake()->unique()->word(),
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertNull($determinedDswType);
    }

    public function test_determine_dsw_type_no_type_in_description(): void
    {
        DswType::factory()->create([
            'name' => fake()->unique()->word(),
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => fake()->unique()->word(),
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertNull($determinedDswType);
    }

    public function test_determine_dsw_type_type_in_name_and_description(): void
    {
        $dswTypeName = fake()->unique()->word();

        $dswType = DswType::factory()->create([
            'name' => $dswTypeName,
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);
        $otherDswType = DswType::factory()->create([
            'name' => fake()->unique()->word(),
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'name' => "Garmin DSW - {$dswTypeName}",
            'description' => "Garmin DSW - {$otherDswType->name}",
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $allDswTypes = DswType::with('typeGroup')->get();

        $determinedDswType = app(StravaActivityDswAnalysisService::class)->determineDswType(
            $stravaActivity,
            $allDswTypes
        );

        $this->assertTrue($dswType->is($determinedDswType));
    }

    public function test_determine_is_intervals_description_matches_and_valid_type_group(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create([
                'has_intervals' => true,
            ])->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => '10min recover',
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isIntervals = app(StravaActivityDswAnalysisService::class)->determineIsIntervals(
            $stravaActivity,
            $dswType
        );

        $this->assertTrue($isIntervals);
    }

    public function test_determine_is_intervals_description_matches_and_invalid_type_group(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create([
                'has_intervals' => false,
            ])->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => '10min recover',
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isIntervals = app(StravaActivityDswAnalysisService::class)->determineIsIntervals(
            $stravaActivity,
            $dswType
        );

        $this->assertFalse($isIntervals);
    }

    public function test_determine_is_intervals_description_does_not_match(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create([
                'has_intervals' => true,
            ])->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => '10min easy',
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isIntervals = app(StravaActivityDswAnalysisService::class)->determineIsIntervals(
            $stravaActivity,
            $dswType
        );

        $this->assertFalse($isIntervals);
    }

    public function test_determine_is_intervals_no_description(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create([
                'has_intervals' => true,
            ])->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'description' => null,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isIntervals = app(StravaActivityDswAnalysisService::class)->determineIsIntervals(
            $stravaActivity,
            $dswType
        );

        $this->assertFalse($isIntervals);
    }

    public function test_determine_is_treadmill_description_matches(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'description' => 'Treadmill run',
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isTreadmill = app(StravaActivityDswAnalysisService::class)->determineIsTreadmill(
            $stravaActivity
        );

        $this->assertTrue($isTreadmill);
    }

    public function test_determine_is_treadmill_description_does_not_match(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'description' => 'Road run',
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isTreadmill = app(StravaActivityDswAnalysisService::class)->determineIsTreadmill(
            $stravaActivity
        );

        $this->assertFalse($isTreadmill);
    }

    public function test_determine_is_treadmill_no_description(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'description' => null,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $isTreadmill = app(StravaActivityDswAnalysisService::class)->determineIsTreadmill(
            $stravaActivity
        );

        $this->assertFalse($isTreadmill);
    }

    public function test_calculate_dsw_score_null_average_watts_and_average_heart_rate(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'average_watts' => null,
            'average_heartrate' => null,
            'average_speed_meters_per_second' => fake()->randomFloat(2, 2, 10),
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $dswScore = app(StravaActivityDswAnalysisService::class)->calculateDswScore(
            $stravaActivity
        );

        $this->assertSame(0, $dswScore);
    }

    #[DataProvider('hasElevationGainDataProvider')]
    public function test_calculate_dsw_score_has_average_watts_null_average_heart_rate(bool $withElevationGain): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'average_watts' => fake()->randomFloat(2, 100, 1000),
            'average_heartrate' => null,
            'average_speed_meters_per_second' => fake()->randomFloat(2, 2, 10),
            'elevation_gain_meters' => $withElevationGain ? fake()->randomNumber(2) : 0,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $dswScore = app(StravaActivityDswAnalysisService::class)->calculateDswScore(
            $stravaActivity
        );

        $expectedScore = $stravaActivity->average_speed_meters_per_second * $stravaActivity->average_watts;

        if ($withElevationGain) {
            $expectedScore *= (1 + ($stravaActivity->elevation_gain_meters / 1000));
        }

        $this->assertSame((int) round($expectedScore * 100), $dswScore);
    }

    #[DataProvider('hasElevationGainDataProvider')]
    public function test_calculate_dsw_score_null_average_watts_has_average_heart_rate(bool $withElevationGain): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'average_watts' => null,
            'average_heartrate' => fake()->randomFloat(2, 100, 200),
            'average_speed_meters_per_second' => fake()->randomFloat(2, 2, 10),
            'elevation_gain_meters' => $withElevationGain ? fake()->randomNumber(2) : 0,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $dswScore = app(StravaActivityDswAnalysisService::class)->calculateDswScore(
            $stravaActivity
        );

        $expectedScore = $stravaActivity->average_speed_meters_per_second * (1 - ($stravaActivity->average_heartrate / 200));

        if ($withElevationGain) {
            $expectedScore *= (1 + ($stravaActivity->elevation_gain_meters / 1000));
        }

        $this->assertSame((int) round($expectedScore * 100), $dswScore);
    }

    #[DataProvider('hasElevationGainDataProvider')]
    public function test_calculate_dsw_score_has_average_watts_has_average_heart_rate(bool $withElevationGain): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'average_watts' => fake()->randomFloat(2, 100, 1000),
            'average_heartrate' => fake()->randomFloat(2, 100, 200),
            'average_speed_meters_per_second' => fake()->randomFloat(2, 2, 10),
            'elevation_gain_meters' => $withElevationGain ? fake()->randomNumber(2) : 0,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $dswScore = app(StravaActivityDswAnalysisService::class)->calculateDswScore(
            $stravaActivity
        );

        $expectedScore = $stravaActivity->average_speed_meters_per_second * $stravaActivity->average_watts;
        $expectedScore = $expectedScore * (1 - ($stravaActivity->average_heartrate / 200));

        if ($withElevationGain) {
            $expectedScore *= (1 + ($stravaActivity->elevation_gain_meters / 1000));
        }

        $this->assertSame((int) round($expectedScore * 100), $dswScore);
    }

    public function test_is_re_analysable_is_summary_no_analysis(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'is_summary' => true,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldNotReceive('determineDswType');

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        $isReAnalysable = app(StravaActivityDswAnalysisService::class)->isReAnalysable(
            $stravaActivity,
            DswType::all(),
        );

        $this->assertTrue($isReAnalysable);
    }

    public function test_is_re_analysable_valid_dsw_type_no_analysis(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'is_summary' => false,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldReceive('determineDswType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($allDswTypesArg) => $allDswTypesArg->contains($dswType) && $allDswTypesArg->count() === 1),
            )
            ->andReturn($dswType);

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        $isReAnalysable = app(StravaActivityDswAnalysisService::class)->isReAnalysable(
            $stravaActivity,
            DswType::all(),
        );

        $this->assertTrue($isReAnalysable);
    }

    public function test_is_re_analysable_not_summary_invalid_dsw_type_no_analysis(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'is_summary' => false,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldReceive('determineDswType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($allDswTypesArg) => $allDswTypesArg->contains($dswType) && $allDswTypesArg->count() === 1),
            )
            ->andReturnNull();

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        $isReAnalysable = app(StravaActivityDswAnalysisService::class)->isReAnalysable(
            $stravaActivity,
            DswType::all(),
        );

        $this->assertFalse($isReAnalysable);
    }

    public function test_is_re_analysable_is_summary_has_analysis(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'is_summary' => true,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        StravaActivityDswAnalysis::factory()->create([
            'dsw_type_id' => $dswType->id,
            'strava_activity_id' => $stravaActivity->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldNotReceive('determineDswType');

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        $isReAnalysable = app(StravaActivityDswAnalysisService::class)->isReAnalysable(
            $stravaActivity,
            DswType::all(),
        );

        $this->assertFalse($isReAnalysable);
    }

    public function test_is_re_analysable_valid_dsw_type_has_analysis(): void
    {
        $dswType = DswType::factory()->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $stravaActivity = StravaActivity::factory()->create([
            'is_summary' => false,
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => User::factory()->create()->id,
                ])->id,
            ])->id,
        ]);

        StravaActivityDswAnalysis::factory()->create([
            'dsw_type_id' => $dswType->id,
            'strava_activity_id' => $stravaActivity->id,
        ]);

        $mockedService = Mockery::mock(StravaActivityDswAnalysisService::class)->makePartial();

        $mockedService->shouldReceive('determineDswType')
            ->once()
            ->with(
                Mockery::on(fn ($stravaActivityArg) => $stravaActivityArg->is($stravaActivity)),
                Mockery::on(fn ($allDswTypesArg) => $allDswTypesArg->contains($dswType) && $allDswTypesArg->count() === 1),
            )
            ->andReturn($dswType);

        app()->instance(StravaActivityDswAnalysisService::class, $mockedService);

        $isReAnalysable = app(StravaActivityDswAnalysisService::class)->isReAnalysable(
            $stravaActivity,
            DswType::all(),
        );

        $this->assertFalse($isReAnalysable);
    }
}
