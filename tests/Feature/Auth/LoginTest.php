<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public static function rememberMeDataProvider(): array
    {
        return [
            'remember me is set to null' => [null],
            'remember me is set to off' => ['off'],
            'remember me is set to on' => ['on'],
        ];
    }

    public function test_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.auth.login');
    }

    #[DataProvider('rememberMeDataProvider')]
    public function test_login_with_valid_credentials(?string $rememberMe): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.action'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => $rememberMe,
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        if ($rememberMe === 'on') {
            $response->assertCookie(auth()->guard()->getRecallerName());
        } else {
            $response->assertCookieMissing(auth()->guard()->getRecallerName());
        }
    }

    #[DataProvider('rememberMeDataProvider')]
    public function test_login_with_invalid_credentials(?string $rememberMe): void
    {
        $response = $this->post(route('login.action'), [
            'email' => 'notexistinguser@test.com',
            'password' => 'password',
            'remember' => $rememberMe,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
