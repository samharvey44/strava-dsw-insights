<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::prefix('/login')->name('login.')->group(function () {
        Route::get('/', [LoginController::class, 'index'])->name('index');
        Route::post('/', [LoginController::class, 'login'])->name('login');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::prefix('/home')->name('home.')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('index');
    });
});
