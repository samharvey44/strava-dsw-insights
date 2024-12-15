<?php

namespace Auth;

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

    public function testCanViewLoginPage(): void
    {
        $response = $this->get(route('login.index'));

        $response->assertStatus(200);
    }

    #[DataProvider('rememberMeDataProvider')]
    public function testCanLoginWithValidCredentials(?string $rememberMe): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => $rememberMe,
        ]);

        $response->assertRedirect(route('home.index'));

        $this->assertAuthenticatedAs($user);

        if ($rememberMe === 'on') {
            $response->assertCookie(auth()->guard()->getRecallerName());
        } else {
            $response->assertCookieMissing(auth()->guard()->getRecallerName());
        }
    }

    #[DataProvider('rememberMeDataProvider')]
    public function testErrorIsDisplayedWithInvalidCredentials(?string $rememberMe): void
    {
        $response = $this->post(route('login.login'), [
            'email' => 'notexistinguser@test.com',
            'password' => 'password',
            'remember' => $rememberMe,
        ]);

        $response->assertRedirect(route('login.index'));
        $response->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
