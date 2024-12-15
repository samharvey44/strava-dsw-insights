<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Strava\StravaAuthRedirectController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::prefix('/login')->group(function () {
        Route::get('/', [LoginController::class, 'index'])->name('login');
        Route::post('/', [LoginController::class, 'login'])->name('login.action');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::prefix('/home')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
    });

    Route::get('/strava-auth-redirect', StravaAuthRedirectController::class)
        ->name('strava.auth-redirect');
});
