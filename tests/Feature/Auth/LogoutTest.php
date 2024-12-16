<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_logged_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $oldSessionId = session()->getId();
        $oldCsrfToken = csrf_token();

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotEquals($oldSessionId, session()->getId());
        $this->assertNotEquals($oldCsrfToken, csrf_token());
    }
}
