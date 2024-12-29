<?php

namespace Tests\Unit\Models;

use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StravaConnectionTest extends TestCase
{
    use RefreshDatabase;

    public static function isActiveStravaConnectionDataProvider(): array
    {
        return [
            'active' => [true],
            'not active' => [false],
        ];
    }

    #[DataProvider('isActiveStravaConnectionDataProvider')]
    public function test_disable(bool $active): void
    {
        $stravaConnection = StravaConnection::factory()->create([
            'user_id' => User::factory()->create()->id,
            'active' => $active,
        ]);

        $stravaConnection->disable();

        $this->assertDatabaseHas('strava_connections', [
            'id' => $stravaConnection->id,
            'active' => false,
        ]);
    }
}
