<?php

use App\Http\Controllers\Activities\ActivityController;
use App\Http\Controllers\Activities\CalendarController;
use App\Http\Controllers\Activities\ChildActivityController;
use App\Http\Controllers\ChildShoppingListController;
use App\Http\Controllers\Food\IngredientController;
use App\Http\Controllers\Food\PantryController;
use App\Http\Controllers\Food\RecipeController;
use App\Http\Controllers\Food\SuggestionController;
use App\Http\Controllers\Lists\CompletedListItemsController;
use App\Http\Controllers\Lists\ListController;
use App\Http\Controllers\Lists\ListItemCompletionController;
use App\Http\Controllers\Lists\ListItemController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\Routines\ChildRoutineController;
use App\Http\Controllers\Routines\RoutineController;
use App\Http\Controllers\Routines\RoutineTemplateController;
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

    Route::get('mat/recept', [RecipeController::class, 'index'])->name('recipes.index');
    Route::get('mat/recept/nytt', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('mat/recept', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('mat/recept/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
    Route::get('mat/recept/{recipe}/andra', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('mat/recept/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('mat/recept/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');

    Route::get('mat/skafferi', [PantryController::class, 'index'])->name('pantry.index');
    Route::post('mat/skafferi', [PantryController::class, 'store'])->name('pantry.store');
    Route::put('mat/skafferi/{pantryItem}', [PantryController::class, 'update'])->name('pantry.update');
    Route::delete('mat/skafferi/{pantryItem}', [PantryController::class, 'destroy'])->name('pantry.destroy');

    Route::get('mat/forslag', SuggestionController::class)->name('meal-suggestions');
    Route::get('mat/ingredienser', [IngredientController::class, 'index'])->name('ingredients.index');

    Route::get('imorgon', [CalendarController::class, 'tomorrow'])->name('tomorrow');
    Route::get('veckan', [CalendarController::class, 'week'])->name('week');

    Route::get('aktiviteter', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('aktiviteter/ny', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('aktiviteter', [ActivityController::class, 'store'])->name('activities.store');
    Route::get('aktiviteter/{activity}/andra', [ActivityController::class, 'edit'])->name('activities.edit');
    Route::put('aktiviteter/{activity}', [ActivityController::class, 'update'])->name('activities.update');
    Route::delete('aktiviteter/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

    Route::get('rutiner', [RoutineController::class, 'index'])->name('routines.index');
    Route::get('rutiner/ny', [RoutineController::class, 'create'])->name('routines.create');
    Route::post('rutiner', [RoutineController::class, 'store'])->name('routines.store');
    Route::post('rutiner/mallar/{template}', [RoutineTemplateController::class, 'store'])->whereNumber('template')->name('routine-templates.copy');
    Route::get('rutiner/{routine}/andra', [RoutineController::class, 'edit'])->name('routines.edit');
    Route::put('rutiner/{routine}', [RoutineController::class, 'update'])->name('routines.update');
    Route::delete('rutiner/{routine}', [RoutineController::class, 'destroy'])->name('routines.destroy');

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

    Route::get('min-dag/veckan', [CalendarController::class, 'childWeek'])->name('child.week');
    Route::scopeBindings()->group(function () {
        Route::post('min-dag/aktivitet/{activity}/{date}/sak/{item}', [ChildActivityController::class, 'pack'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.pack');
        Route::delete('min-dag/aktivitet/{activity}/{date}/sak/{item}', [ChildActivityController::class, 'unpack'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.unpack');
    });
    Route::post('min-dag/aktivitet/{activity}/{date}/packat', [ChildActivityController::class, 'confirm'])
        ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.confirm');

    Route::get('min-dag/rutin/{routine}', [ChildRoutineController::class, 'show'])->name('child.routines.show');
    Route::scopeBindings()->group(function () {
        Route::post('min-dag/rutin/{routine}/steg/{step}', [ChildRoutineController::class, 'check'])->name('child.routines.steps.check');
        Route::delete('min-dag/rutin/{routine}/steg/{step}', [ChildRoutineController::class, 'uncheck'])->name('child.routines.steps.uncheck');
        Route::post('min-dag/rutin/{routine}/steg/{step}/hoppa', [ChildRoutineController::class, 'skip'])->name('child.routines.steps.skip');
    });
});

// Picture files: for members and trusted devices such as the home display.
Route::get('bilder/{picture}', [PictureController::class, 'show'])->whereNumber('picture')->name('pictures.show');
