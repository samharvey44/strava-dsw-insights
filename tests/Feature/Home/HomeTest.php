<?php

namespace Tests\Feature\Home;

use App\Models\DswType;
use App\Models\DswTypeGroup;
use App\Models\StravaActivity;
use App\Models\StravaConnection;
use App\Models\User;
use App\Services\DswTypes\DswTypesService;
use App\Services\Home\HomeFilteringService;
use App\Services\Strava\DSWAnalysis\StravaActivityDswAnalysisScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_home_page_user_with_no_strava_connection(): void
    {
        $user = User::factory()->create();

        $dswTypes = DswType::factory()->count(3)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $activityQuery = StravaActivity::query();

        $mockedHomeFilteringService = Mockery::mock(HomeFilteringService::class);
        $mockedHomeFilteringService->shouldReceive('applyFiltersAndSort')
            ->with(
                Mockery::on(fn (Builder $query) => true), // The type-hint proves that the query is a Builder instance
                [],
                null,
                null
            )
            ->andReturn($activityQuery);
        app()->instance(HomeFilteringService::class, $mockedHomeFilteringService);

        $mockedScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class);
        $mockedScoringService->shouldNotReceive('getScoreBandsByType');
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedScoringService);

        $mockedDswTypesService = Mockery::mock(DswTypesService::class);
        $mockedDswTypesService->shouldReceive('getAllTypes')->andReturn($dswTypes);
        app()->instance(DswTypesService::class, $mockedDswTypesService);

        $this->actingAs($user);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.home.index');
        $response->assertViewHas([
            'activities' => $activityQuery->paginate(20),
            'scoreBands' => collect(),
            'dswTypes' => $dswTypes,
            'filtersApplied' => false,
        ]);
    }

    public function test_view_home_page_user_with_strava_connection(): void
    {
        $user = User::factory()->create();
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $dswTypes = DswType::factory()->count(3)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $activityQuery = StravaActivity::query();

        $mockedHomeFilteringService = Mockery::mock(HomeFilteringService::class);
        $mockedHomeFilteringService->shouldReceive('applyFiltersAndSort')
            ->with(
                Mockery::on(fn (Builder $query) => true), // The type-hint proves that the query is a Builder instance
                [],
                null,
                null
            )
            ->andReturn($activityQuery);
        app()->instance(HomeFilteringService::class, $mockedHomeFilteringService);

        $mockedScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class);
        $mockedScoringService->shouldReceive('getScoreBandsByType')
            ->with(Mockery::on(fn (StravaConnection $connectionArg) => $connectionArg->is($stravaConnection)))
            ->andReturn(collect());
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedScoringService);

        $mockedDswTypesService = Mockery::mock(DswTypesService::class);
        $mockedDswTypesService->shouldReceive('getAllTypes')->andReturn($dswTypes);
        app()->instance(DswTypesService::class, $mockedDswTypesService);

        $this->actingAs($user);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.home.index');
        $response->assertViewHas([
            'activities' => $activityQuery->paginate(20),
            'scoreBands' => collect(),
            'dswTypes' => $dswTypes,
            'filtersApplied' => false,
        ]);
    }

    public function test_view_home_page_user_with_filters(): void
    {
        $user = User::factory()->create();
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $dswTypes = DswType::factory()->count(3)->create([
            'dsw_type_group_id' => DswTypeGroup::factory()->create()->id,
        ]);

        $activityQuery = StravaActivity::query();

        $mockedHomeFilteringService = Mockery::mock(HomeFilteringService::class);
        $mockedHomeFilteringService->shouldReceive('applyFiltersAndSort')
            ->with(
                Mockery::on(fn (Builder $query) => true), // The type-hint proves that the query is a Builder instance
                ['some' => 'value'],
                null,
                null
            )
            ->andReturn($activityQuery);
        app()->instance(HomeFilteringService::class, $mockedHomeFilteringService);

        $mockedScoringService = Mockery::mock(StravaActivityDswAnalysisScoringService::class);
        $mockedScoringService->shouldReceive('getScoreBandsByType')
            ->with(Mockery::on(fn (StravaConnection $connectionArg) => $connectionArg->is($stravaConnection)))
            ->andReturn(collect());
        app()->instance(StravaActivityDswAnalysisScoringService::class, $mockedScoringService);

        $mockedDswTypesService = Mockery::mock(DswTypesService::class);
        $mockedDswTypesService->shouldReceive('getAllTypes')->andReturn($dswTypes);
        app()->instance(DswTypesService::class, $mockedDswTypesService);

        $this->actingAs($user);

        $response = $this->get(route('home', ['filters' => ['some' => 'value']]));

        $response->assertStatus(200);
        $response->assertViewIs('pages.home.index');
        $response->assertViewHas([
            'activities' => $activityQuery->paginate(20),
            'scoreBands' => collect(),
            'dswTypes' => $dswTypes,
            'filtersApplied' => true,
        ]);
    }
}
