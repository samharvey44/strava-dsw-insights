<?php

namespace Tests\Unit\Services\Home;

use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaActivityDswAnalysis;
use App\Models\StravaConnection;
use App\Models\StravaRawActivity;
use App\Models\User;
use App\Services\Home\HomeFilteringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeFilteringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_filters_and_sort_no_filters_or_sort(): void
    {
        $stravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
        ]);

        $filteredResults = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            null,
            null
        )->get();

        $this->assertEquals($stravaActivity->id, $filteredResults->first()->id);
        $this->assertCount(1, $filteredResults->all());
    }

    public function test_apply_filters_and_sort_dsw_types_filtered(): void
    {
        $stravaActivityWithFirstDswType = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivityWithFirstDswType->id,
            'dsw_type_id' => ($firstDswType = DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ]))->id,
        ]);

        $stravaActivityWithSecondDswType = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivityWithSecondDswType->id,
            'dsw_type_id' => ($secondDswType = DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ]))->id,
        ]);

        $stravaActivityWithThirdDswType = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(3),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $stravaActivityWithThirdDswType->id,
            'dsw_type_id' => ($thirdDswType = DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ]))->id,
        ]);

        $filteredResults = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                "dsw_type_{$firstDswType->id}" => true,
                "dsw_type_{$secondDswType->id}" => true,
                "dsw_type_{$thirdDswType->id}" => false,
            ],
            null,
            null
        )->get();

        $this->assertEquals($stravaActivityWithFirstDswType->id, $filteredResults->first()->id);
        $this->assertEquals($stravaActivityWithSecondDswType->id, $filteredResults->skip(1)->first()->id);
        $this->assertCount(2, $filteredResults->all());
    }

    public function test_apply_filters_and_sort_unanalysed_activities_filtered(): void
    {
        $analysedStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $analysedStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $unanalysedStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);

        $filteredResultsWithoutUnanalysed = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                'unanalysed_activities' => false,
            ],
            null,
            null
        )->get();

        $this->assertEquals($analysedStravaActivity->id, $filteredResultsWithoutUnanalysed->first()->id);
        $this->assertCount(1, $filteredResultsWithoutUnanalysed->all());

        $filteredResultsWithUnanalysed = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                'unanalysed_activities' => true,
            ],
            null,
            null
        )->get();

        $this->assertEquals($analysedStravaActivity->id, $filteredResultsWithUnanalysed->first()->id);
        $this->assertEquals($unanalysedStravaActivity->id, $filteredResultsWithUnanalysed->skip(1)->first()->id);
        $this->assertCount(2, $filteredResultsWithUnanalysed->all());
    }

    public function test_apply_filters_and_sort_intervals_filtered(): void
    {
        $intervalsStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $intervalsStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'intervals' => true,
        ]);

        $nonIntervalsStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $nonIntervalsStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'intervals' => false,
        ]);

        $filteredResultsWithoutIntervals = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                'interval_activities' => false,
                'non_interval_activities' => true,
            ],
            null,
            null
        )->get();

        $this->assertEquals($nonIntervalsStravaActivity->id, $filteredResultsWithoutIntervals->first()->id);
        $this->assertCount(1, $filteredResultsWithoutIntervals->all());

        $filteredResultsWithIntervals = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                'interval_activities' => true,
                'non_interval_activities' => false,
            ],
            null,
            null
        )->get();

        $this->assertEquals($intervalsStravaActivity->id, $filteredResultsWithIntervals->first()->id);
        $this->assertCount(1, $filteredResultsWithIntervals->all());
    }

    public function test_apply_filters_and_sort_treadmill_filtered(): void
    {
        $treadmillStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $treadmillStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'treadmill' => true,
        ]);

        $nonTreadmillStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $nonTreadmillStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'treadmill' => false,
        ]);

        $filteredResultsWithoutTreadmill = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                'treadmill_activities' => false,
                'non_treadmill_activities' => true,
            ],
            null,
            null
        )->get();

        $this->assertEquals($nonTreadmillStravaActivity->id, $filteredResultsWithoutTreadmill->first()->id);
        $this->assertCount(1, $filteredResultsWithoutTreadmill->all());

        $filteredResultsWithTreadmill = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [
                'treadmill_activities' => true,
                'non_treadmill_activities' => false,
            ],
            null,
            null
        )->get();

        $this->assertEquals($treadmillStravaActivity->id, $filteredResultsWithTreadmill->first()->id);
        $this->assertCount(1, $filteredResultsWithTreadmill->all());
    }

    public function test_apply_filters_and_sort_sort_by_arbitrary_column(): void
    {
        $firstStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $firstStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $secondStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $secondStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $filteredResultsById = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'id',
            'desc'
        )->get();

        // The ID sort will be ignored in favour of started_at DESC
        $this->assertEquals($firstStravaActivity->id, $filteredResultsById->first()->id);
    }

    public function test_apply_filters_and_sort_sort_by_arbitrary_direction(): void
    {
        $firstStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $firstStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $secondStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $secondStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $filteredResultsById = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'started_at',
            'not_a_valid_sort_direction'
        )->get();

        // The direction sort will be ignored in favour of started_at DESC
        $this->assertEquals($firstStravaActivity->id, $filteredResultsById->first()->id);
    }

    public function test_apply_filters_and_sort_sort_by_started_at(): void
    {
        $firstStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $firstStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $secondStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $secondStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $filteredResultsByStartedAtDesc = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'started_at',
            'desc'
        )->get();

        $this->assertEquals($firstStravaActivity->id, $filteredResultsByStartedAtDesc->first()->id);
        $this->assertEquals($secondStravaActivity->id, $filteredResultsByStartedAtDesc->skip(1)->first()->id);

        $filteredResultsByStartedAtAsc = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'started_at',
            'asc'
        )->get();

        $this->assertEquals($secondStravaActivity->id, $filteredResultsByStartedAtAsc->first()->id);
        $this->assertEquals($firstStravaActivity->id, $filteredResultsByStartedAtAsc->skip(1)->first()->id);
    }

    public function test_apply_filters_and_sort_sort_by_dsw_score(): void
    {
        $firstStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $firstStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'dsw_score' => 11,
        ]);

        $secondStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $secondStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
            'dsw_score' => 10,
        ]);

        $filteredResultsByStartedAtDesc = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'dsw_score',
            'desc'
        )->get();

        $this->assertEquals($firstStravaActivity->id, $filteredResultsByStartedAtDesc->first()->id);
        $this->assertEquals($secondStravaActivity->id, $filteredResultsByStartedAtDesc->skip(1)->first()->id);

        $filteredResultsByStartedAtAsc = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'dsw_score',
            'asc'
        )->get();

        $this->assertEquals($secondStravaActivity->id, $filteredResultsByStartedAtAsc->first()->id);
        $this->assertEquals($firstStravaActivity->id, $filteredResultsByStartedAtAsc->skip(1)->first()->id);
    }

    public function test_apply_filters_and_sort_sort_by_distance(): void
    {
        $firstStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => StravaConnection::factory()->create([
                    'user_id' => ($user = User::factory()->create())->id,
                ]),
            ])->id,
            'started_at' => now()->subDay(),
            'distance_meters' => 1001,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $firstStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $secondStravaActivity = StravaActivity::factory()->create([
            'strava_raw_activity_id' => StravaRawActivity::factory()->create([
                'strava_connection_id' => $user->stravaConnection,
            ])->id,
            'started_at' => now()->subDays(2),
            'distance_meters' => 1000,
        ]);
        StravaActivityDswAnalysis::factory()->create([
            'strava_activity_id' => $secondStravaActivity->id,
            'dsw_type_id' => DswType::factory()->create([
                'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
            ])->id,
        ]);

        $filteredResultsByStartedAtDesc = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'distance_meters',
            'desc'
        )->get();

        $this->assertEquals($firstStravaActivity->id, $filteredResultsByStartedAtDesc->first()->id);
        $this->assertEquals($secondStravaActivity->id, $filteredResultsByStartedAtDesc->skip(1)->first()->id);

        $filteredResultsByStartedAtAsc = app(HomeFilteringService::class)->applyFiltersAndSort(
            StravaActivity::byUser($user),
            [],
            'distance_meters',
            'asc'
        )->get();

        $this->assertEquals($secondStravaActivity->id, $filteredResultsByStartedAtAsc->first()->id);
        $this->assertEquals($firstStravaActivity->id, $filteredResultsByStartedAtAsc->skip(1)->first()->id);
    }
}
