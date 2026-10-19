<?php

use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', WelcomeController::class)->name('home');
Route::inertia('bildkallor', 'PictureSources')->name('picture-sources');
Route::get('integritet', fn () => Inertia::render('Privacy', [
    'controller' => config('privacy.controller'),
    'contact' => config('privacy.contact'),
    'backupDays' => config('backup.cleanup.default_strategy.keep_all_backups_for_days'),
]))->name('privacy');
// Om NPF: public, no login, to share with relatives and school.
Route::get('om-npf', [InfoController::class, 'index'])->name('info.index');
Route::get('om-npf/kanner-du-igen-dig', [InfoController::class, 'situations'])->name('info.situations');
Route::get('om-npf/{article:slug}', [InfoController::class, 'show'])->name('info.show');

Route::get('avstangt', fn () => Inertia::render('Suspended', [
    'contact' => config('privacy.contact'),
]))->name('suspended');

Route::middleware(['auth', 'verified', 'family', 'role:adult'])->group(function () {
    Route::get('dashboard', [TodayController::class, 'family'])->name('dashboard');
});

Route::middleware(['auth', 'family', 'role:child'])->group(function () {
    Route::get('min-dag', [TodayController::class, 'child'])->name('child.home');
});

// "Tyck till" from the adults' pages.
Route::post('tyck-till', [FeedbackController::class, 'store'])
    ->middleware(['auth', 'role:adult', 'throttle:10,1'])
    ->name('feedback.store');

// Push reminders on the device the user is on, for adults and children.
Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::post('push/enhet', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push/enhet', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

require __DIR__.'/family.php';
require __DIR__.'/everyday.php';
require __DIR__.'/settings.php';
require __DIR__.'/community.php';
