<?php

use App\Http\Controllers\ChildShoppingListController;
use App\Http\Controllers\Lists\CompletedListItemsController;
use App\Http\Controllers\Lists\ListController;
use App\Http\Controllers\Lists\ListItemCompletionController;
use App\Http\Controllers\Lists\ListItemController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\Tasks\RecurringTaskController;
use App\Http\Controllers\Tasks\TaskCompletionController;
use App\Http\Controllers\Tasks\TaskController;
use Illuminate\Support\Facades\Route;

// Lists and tasks. Policies decide what each role may do.

Route::middleware(['auth', 'verified', 'family', 'role:adult'])->group(function () {
    Route::get('listor', [ListController::class, 'index'])->name('lists.index');
    Route::post('listor', [ListController::class, 'store'])->name('lists.store');
    Route::get('listor/{list}', [ListController::class, 'show'])->name('lists.show');
    Route::put('listor/{list}', [ListController::class, 'update'])->name('lists.update');
    Route::delete('listor/{list}', [ListController::class, 'destroy'])->name('lists.destroy');
    Route::delete('listor/{list}/klara', [CompletedListItemsController::class, 'destroy'])->name('lists.completed.destroy');

    Route::scopeBindings()->group(function () {
        Route::put('listor/{list}/varor/{item}', [ListItemController::class, 'update'])->name('list-items.update');
        Route::delete('listor/{list}/varor/{item}', [ListItemController::class, 'destroy'])->name('list-items.destroy');
    });

    Route::get('uppgifter/aterkommande', [RecurringTaskController::class, 'index'])->name('recurring-tasks.index');
    Route::get('uppgifter/aterkommande/ny', [RecurringTaskController::class, 'create'])->name('recurring-tasks.create');
    Route::post('uppgifter/aterkommande', [RecurringTaskController::class, 'store'])->name('recurring-tasks.store');
    Route::get('uppgifter/aterkommande/{recurringTask}/andra', [RecurringTaskController::class, 'edit'])->name('recurring-tasks.edit');
    Route::put('uppgifter/aterkommande/{recurringTask}', [RecurringTaskController::class, 'update'])->name('recurring-tasks.update');
    Route::delete('uppgifter/aterkommande/{recurringTask}', [RecurringTaskController::class, 'destroy'])->name('recurring-tasks.destroy');

    Route::get('uppgifter', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('uppgifter/ny', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('uppgifter', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('uppgifter/{task}/andra', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('uppgifter/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('uppgifter/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});

// Adults and children: children add to the shopping list and tick off their own tasks.
Route::middleware(['auth', 'verified', 'family'])->group(function () {
    Route::get('bilder', [PictureController::class, 'index'])->name('pictures.index');
    Route::post('bilder', [PictureController::class, 'store'])->middleware('throttle:30,1')->name('pictures.store');

    Route::scopeBindings()->group(function () {
        Route::post('listor/{list}/varor', [ListItemController::class, 'store'])->name('list-items.store');
        Route::post('listor/{list}/varor/{item}/klar', [ListItemCompletionController::class, 'store'])->name('list-items.completion.store');
        Route::delete('listor/{list}/varor/{item}/klar', [ListItemCompletionController::class, 'destroy'])->name('list-items.completion.destroy');
    });

    Route::post('uppgifter/{task}/klar', [TaskCompletionController::class, 'store'])->name('tasks.completion.store');
    Route::delete('uppgifter/{task}/klar', [TaskCompletionController::class, 'destroy'])->name('tasks.completion.destroy');
});

Route::middleware(['auth', 'family', 'role:child'])->group(function () {
    Route::get('handla', ChildShoppingListController::class)->name('child.shopping');
});

// Picture files: for members and trusted devices such as the home display.
Route::get('bilder/{picture}', [PictureController::class, 'show'])->whereNumber('picture')->name('pictures.show');
