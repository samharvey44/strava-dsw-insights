<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Gear\GearController;
use App\Http\Controllers\Gear\Reminders\GearReminderController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Strava\Activities\StravaActivityController;
use App\Http\Controllers\Strava\Auth\StravaAuthController;
use App\Http\Controllers\Strava\Webhooks\StravaWebhooksController;
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

    Route::prefix('/gear')->group(function () {
        Route::get('/', [GearController::class, 'index'])->name('gear');

        Route::prefix('/create')->name('gear.')->group(function () {
            Route::get('/', [GearController::class, 'create'])->name('create');
            Route::post('/', [GearController::class, 'store'])->name('store');
        });

        Route::prefix('/{gear}')->name('gear.')->group(function () {
            Route::get('/', [GearController::class, 'edit'])->name('edit');
            Route::patch('/', [GearController::class, 'update'])->name('update');
            Route::delete('/', [GearController::class, 'destroy'])->name('destroy');

            Route::prefix('/reminders')->name('reminders.')->group(function () {
                Route::get('/modal-contents', [GearReminderController::class, 'modalContents'])
                    ->name('modal-contents');
                Route::post('/', [GearReminderController::class, 'store'])
                    ->name('store');

                Route::prefix('/{gearReminder}')->group(function () {
                    Route::patch('/', [GearReminderController::class, 'update'])
                        ->name('update');
                    Route::delete('/', [GearReminderController::class, 'destroy'])
                        ->name('destroy');
                });
            });
        });
    });

    Route::prefix('/activities')->name('activities.')->group(function () {
        Route::prefix('/{stravaActivity}')->group(function () {
            Route::prefix('/gear')->name('gear.')->group(function () {
                Route::get('/modal-contents', [StravaActivityController::class, 'gearModalContents'])
                    ->name('modal-contents');

                Route::prefix('/{gear}')->group(function () {
                    Route::post('/', [StravaActivityController::class, 'attachGear'])
                        ->name('attach');
                    Route::delete('/', [StravaActivityController::class, 'detachGear'])
                        ->name('detach');
                });
            });
        });
    });
});

Route::prefix('/strava')->name('strava.')->group(function () {
    Route::prefix('/auth')->middleware('auth')->name('auth.')->group(function () {
        Route::get('/initiate', [StravaAuthController::class, 'initiateAuthorisation'])->name('initiate');
        Route::get('/redirect', [StravaAuthController::class, 'redirect'])->name('redirect');

        Route::middleware('signed')->group(function () {
            Route::get('/successful', [StravaAuthController::class, 'successful'])->name('successful');
            Route::get('/unsuccessful', [StravaAuthController::class, 'unsuccessful'])->name('unsuccessful');
        });
    });

    Route::match(
        ['GET', 'POST'],
        '/webhook-updates-'.config('strava.webhook_callback_uri_suffix'),
        StravaWebhooksController::class
    )->name('webhook-updates');
});

Route::redirect('/', '/home');
