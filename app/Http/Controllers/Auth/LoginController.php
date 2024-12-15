<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function index(): View
    {
        return view('pages.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $authAttempt = [
            'email' => $request->input('email') ?? '',
            'password' => $request->input('password') ?? '',
        ];

        if (auth()->attempt($authAttempt, $request->boolean('remember'))) {
            return redirect()->intended(route('home.index'));
        }

        return redirect()->route('login.index')->withInput()->withErrors([
            'login' => 'Invalid email or password provided.',
        ]);
    }
}
