<?php

namespace Tests\Unit\Services\Strava;

use App\Models\StravaConnection;
use App\Models\User;
use App\Services\Strava\Auth\StravaAuthorisationService;
use App\Services\Strava\StravaAuthorisationFailedException;
use App\Services\Strava\WithStravaAuthorisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WithStravaAuthorisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_expired_connection(): void
    {
        $classWithTrait = new class
        {
            use WithStravaAuthorisation;
        };

        $this->freezeSecond();

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldNotReceive('refreshAccessToken');
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token_expiry' => now()->addSeconds(61)->getTimestamp(),
        ]);

        $classWithTrait->withStravaAuthorisation($stravaConnection, function ($stravaConnection) {
            //
        });
    }

    public function test_expired_connection_successful_refresh(): void
    {
        $classWithTrait = new class
        {
            use WithStravaAuthorisation;
        };

        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token_expiry' => now()->addMinute()->getTimestamp(),
        ]);

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldReceive('refreshAccessToken')
            ->once()
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->andReturnTrue();
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $classWithTrait->withStravaAuthorisation($stravaConnection, function ($stravaConnection) {
            //
        });
    }

    public function test_expired_connection_unsuccessful_refresh(): void
    {
        $classWithTrait = new class
        {
            use WithStravaAuthorisation;
        };

        $this->freezeSecond();

        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'access_token_expiry' => now()->addMinute()->getTimestamp(),
        ]);

        $mockedStravaAuthorisationService = Mockery::mock(StravaAuthorisationService::class);
        $mockedStravaAuthorisationService->shouldReceive('refreshAccessToken')
            ->once()
            ->with(Mockery::on(fn ($stravaConnectionArg) => $stravaConnectionArg->is($stravaConnection)))
            ->andReturnFalse();
        app()->instance(StravaAuthorisationService::class, $mockedStravaAuthorisationService);

        $this->expectException(StravaAuthorisationFailedException::class);
        $this->expectExceptionMessage("Strava authorisation failed for connection ID: {$stravaConnection->id}");

        $classWithTrait->withStravaAuthorisation($stravaConnection, function ($stravaConnection) {
            //
        });
    }
}
