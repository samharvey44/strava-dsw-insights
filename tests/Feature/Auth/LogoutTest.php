<?php

namespace Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function testUserIsLoggedOut(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post(route('logout'));

        $this->assertGuest();
    }
}
