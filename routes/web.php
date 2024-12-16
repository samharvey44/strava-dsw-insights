<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Strava\Auth\StravaAuthController;
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

    Route::prefix('/strava-auth')->name('strava-auth.')->group(function () {
        Route::get('/redirect', [StravaAuthController::class, 'redirect'])->name('redirect');

        Route::middleware('signed')->group(function () {
            Route::get('/successful', [StravaAuthController::class, 'successful'])->name('successful');
            Route::get('/unsuccessful', [StravaAuthController::class, 'unsuccessful'])->name('unsuccessful');
        });
    });
});
