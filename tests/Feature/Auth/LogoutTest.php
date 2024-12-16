<?php

namespace Tests\Feature\Auth;

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

        $oldSessionId = session()->getId();
        $oldCsrfToken = csrf_token();

        $this->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotEquals($oldSessionId, session()->getId());
        $this->assertNotEquals($oldCsrfToken, csrf_token());
    }
}
