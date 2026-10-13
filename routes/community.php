<?php

use App\Http\Controllers\Community\CommunityController;
use App\Http\Controllers\Community\GroupController;
use App\Http\Controllers\Community\PostController;
use App\Http\Controllers\Community\ThreadController;
use Illuminate\Support\Facades\Route;

// Community: parents only. Other adults and children never reach it.
Route::middleware(['auth', 'verified', 'family', 'role:parent'])->prefix('community')->group(function () {
    Route::get('/', [CommunityController::class, 'index'])->name('community.index');
    Route::put('namn', [CommunityController::class, 'name'])->name('community.name');

    Route::get('grupper/{group}', [GroupController::class, 'show'])->whereNumber('group')->name('community.groups.show');
    Route::post('grupper/{group}/medlem', [GroupController::class, 'join'])->whereNumber('group')->name('community.groups.join');
    Route::delete('grupper/{group}/medlem', [GroupController::class, 'leave'])->whereNumber('group')->name('community.groups.leave');
    Route::post('grupper/{group}/tradar', [ThreadController::class, 'store'])->whereNumber('group')->middleware('throttle:10,1')->name('community.threads.store');

    Route::get('tradar/{thread}', [ThreadController::class, 'show'])->whereNumber('thread')->name('community.threads.show');
    Route::post('tradar/{thread}/svar', [PostController::class, 'store'])->whereNumber('thread')->middleware('throttle:20,1')->name('community.posts.store');

    Route::put('inlagg/{post}', [PostController::class, 'update'])->whereNumber('post')->name('community.posts.update');
    Route::delete('inlagg/{post}', [PostController::class, 'destroy'])->whereNumber('post')->name('community.posts.destroy');
    Route::post('inlagg/{post}/anmal', [PostController::class, 'report'])->whereNumber('post')->middleware('throttle:10,1')->name('community.posts.report');
});
