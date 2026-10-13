<?php

use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::inertia('bildkallor', 'PictureSources')->name('picture-sources');

Route::middleware(['auth', 'verified', 'family', 'role:adult'])->group(function () {
    Route::get('dashboard', [TodayController::class, 'family'])->name('dashboard');
});

Route::middleware(['auth', 'family', 'role:child'])->group(function () {
    Route::get('min-dag', [TodayController::class, 'child'])->name('child.home');
});

// Push reminders on the device the user is on, for adults and children.
Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::post('push/enhet', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push/enhet', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

require __DIR__.'/family.php';
require __DIR__.'/everyday.php';
require __DIR__.'/settings.php';
