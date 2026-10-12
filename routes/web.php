<?php

use App\Http\Controllers\ChildHomeController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::inertia('bildkallor', 'PictureSources')->name('picture-sources');

Route::middleware(['auth', 'verified', 'family', 'role:adult'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'family', 'role:child'])->group(function () {
    Route::get('min-dag', ChildHomeController::class)->name('child.home');
});

require __DIR__.'/family.php';
require __DIR__.'/everyday.php';
require __DIR__.'/settings.php';
