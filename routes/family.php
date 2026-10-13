<?php

use App\Http\Controllers\ChildSessionController;
use App\Http\Controllers\DisplayController as HomeDisplayController;
use App\Http\Controllers\Family\ActiveFamilyController;
use App\Http\Controllers\Family\CalendarController;
use App\Http\Controllers\Family\ChildAccessController;
use App\Http\Controllers\Family\ChildController;
use App\Http\Controllers\Family\ChildPairingController;
use App\Http\Controllers\Family\ChildPinController;
use App\Http\Controllers\Family\DayPartController;
use App\Http\Controllers\Family\DeviceController;
use App\Http\Controllers\Family\DisplayController;
use App\Http\Controllers\Family\FamilyController;
use App\Http\Controllers\Family\FamilyInvitationController;
use App\Http\Controllers\Family\InvitationAcceptanceController;
use App\Http\Controllers\Family\SchoolMenuController;
use Illuminate\Support\Facades\Route;

// Adults without a family create one first.
Route::middleware(['auth', 'verified', 'role:adult'])->group(function () {
    Route::get('familj/ny', [FamilyController::class, 'create'])->name('family.create');
    Route::post('familj', [FamilyController::class, 'store'])->name('family.store');
});

Route::middleware(['auth', 'verified', 'family', 'role:adult'])->group(function () {
    Route::get('familj', [FamilyController::class, 'show'])->name('family.show');
    Route::put('familj', [FamilyController::class, 'update'])->name('family.update');
    Route::put('familj/dagsdelar', [DayPartController::class, 'update'])->name('family.day-parts.update');
    Route::post('familjer/{family}/aktiv', [ActiveFamilyController::class, 'update'])->name('active-family.update');

    Route::post('familj/inbjudningar', [FamilyInvitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('family-invitations.store');
    Route::delete('familj/inbjudningar/{invitation}', [FamilyInvitationController::class, 'destroy'])
        ->name('family-invitations.destroy');

    Route::post('familj/barn', [ChildController::class, 'store'])->name('children.store');
    Route::get('familj/barn/{child}', [ChildController::class, 'show'])->whereNumber('child')->name('children.show');
    Route::put('familj/barn/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('children.update');
    Route::put('familj/barn/{child}/kod', [ChildPinController::class, 'update'])->whereNumber('child')->name('children.pin.update');
    Route::post('familj/barn/{child}/kod', [ChildPinController::class, 'store'])->whereNumber('child')->name('children.pin.store');
    Route::post('familj/barn/{child}/inloggning', [ChildAccessController::class, 'store'])->whereNumber('child')->name('children.access.store');
    Route::delete('familj/barn/{child}/inloggning', [ChildAccessController::class, 'destroy'])->whereNumber('child')->name('children.access.destroy');
    Route::post('familj/barn/{child}/koppling', [ChildPairingController::class, 'store'])->whereNumber('child')->name('children.pairing.store');

    Route::post('familj/enheter', [DeviceController::class, 'store'])->name('devices.store');

    Route::post('familj/skolor', [SchoolMenuController::class, 'store'])->name('school-menus.store');
    Route::delete('familj/skolor/{schoolMenu}', [SchoolMenuController::class, 'destroy'])->name('school-menus.destroy');
    Route::put('familj/skolor/byte', [SchoolMenuController::class, 'switchTime'])->name('school-menus.switch');
    Route::put('familj/medlemmar/{member}/skola', [SchoolMenuController::class, 'assign'])->whereNumber('member')->name('school-menus.assign');

    Route::get('familj/kalendrar', [CalendarController::class, 'index'])->name('calendars.index');
    Route::post('familj/kalendrar', [CalendarController::class, 'store'])->middleware('throttle:10,1')->name('calendars.store');
    Route::put('familj/kalendrar/{calendar}', [CalendarController::class, 'update'])->middleware('throttle:10,1')->name('calendars.update');
    Route::delete('familj/kalendrar/{calendar}', [CalendarController::class, 'destroy'])->name('calendars.destroy');
    Route::post('familj/kalendrar/{calendar}/las-in', [CalendarController::class, 'sync'])->middleware('throttle:6,1')->name('calendars.sync');

    Route::post('familj/hemskarmar', [DisplayController::class, 'store'])->name('displays.store');
    Route::get('familj/hemskarmar/{device}', [DisplayController::class, 'edit'])->name('displays.edit');
    Route::put('familj/hemskarmar/{device}', [DisplayController::class, 'update'])->name('displays.update');
    Route::post('familj/hemskarmar/{device}/lank', [DisplayController::class, 'link'])->name('displays.link');
    Route::post('familj/hemskarmar/{device}/widgets', [DisplayController::class, 'addWidget'])->name('displays.widgets.store');
    Route::put('familj/hemskarmar/{device}/widgets', [DisplayController::class, 'layout'])->name('displays.layout');
    Route::delete('familj/hemskarmar/{device}/widgets/{widget}', [DisplayController::class, 'removeWidget'])->name('displays.widgets.destroy');
    Route::delete('familj/enheter/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
});

// The link in an invitation e-mail, for guests and logged-in adults.
Route::get('inbjudan/{token}', [InvitationAcceptanceController::class, 'show'])->name('invitations.show');
Route::post('inbjudan/{token}', [InvitationAcceptanceController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('invitations.accept');

// Children log in with their code on a trusted device.
Route::get('barn', [ChildSessionController::class, 'create'])->name('child-login');
Route::post('barn', [ChildSessionController::class, 'store'])
    ->middleware('throttle:child-login')
    ->name('child-login.store');

// A child's own device is paired by opening the link in the QR code.
Route::get('koppla/{token}', [ChildPairingController::class, 'show'])->name('device-pairing.show');
Route::post('koppla/{token}', [ChildPairingController::class, 'update'])
    ->middleware('throttle:10,1')
    ->name('device-pairing.update');

// The home display: a secret link instead of a login. Read-only.
Route::get('display/{token}', [HomeDisplayController::class, 'show'])
    ->middleware('display')
    ->where('token', '[A-Za-z0-9]{40,128}')
    ->name('display.show');
