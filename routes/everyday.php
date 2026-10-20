<?php

use App\Http\Controllers\Activities\ActivityCancellationController;
use App\Http\Controllers\Activities\ActivityController;
use App\Http\Controllers\Activities\CalendarController;
use App\Http\Controllers\Activities\ChildActivityController;
use App\Http\Controllers\ChildAvatarController;
use App\Http\Controllers\ChildRecipeController;
use App\Http\Controllers\ChildShoppingListController;
use App\Http\Controllers\Food\CookController;
use App\Http\Controllers\Food\IngredientController;
use App\Http\Controllers\Food\InventoryController;
use App\Http\Controllers\Food\MealPlanController;
use App\Http\Controllers\Food\PantryController;
use App\Http\Controllers\Food\RecipeController;
use App\Http\Controllers\Food\RecipeImportController;
use App\Http\Controllers\Food\SuggestionController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\Lists\CompletedListItemsController;
use App\Http\Controllers\Lists\IcaConnectionController;
use App\Http\Controllers\Lists\ListController;
use App\Http\Controllers\Lists\ListItemCompletionController;
use App\Http\Controllers\Lists\ListItemController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\Rewards\ChildRewardController;
use App\Http\Controllers\Rewards\PointController;
use App\Http\Controllers\Rewards\RedemptionController;
use App\Http\Controllers\Rewards\RewardController;
use App\Http\Controllers\Routines\ChildRoutineController;
use App\Http\Controllers\Routines\RoutineController;
use App\Http\Controllers\Routines\RoutineTemplateController;
use App\Http\Controllers\Tasks\ChildTaskController;
use App\Http\Controllers\Tasks\RecurringTaskController;
use App\Http\Controllers\Tasks\TaskCompletionController;
use App\Http\Controllers\Tasks\TaskController;
use Illuminate\Support\Facades\Route;

// Lists and tasks. Policies decide what each role may do.

Route::middleware(['auth', 'verified', 'family', 'role:adult'])->group(function () {
    // Handla → ICA (a trial per family, FamilyFeatures::ICA).
    Route::get('listor/ica', [IcaConnectionController::class, 'show'])->name('ica.show');
    Route::post('listor/ica', [IcaConnectionController::class, 'store'])->middleware('throttle:5,10')->name('ica.store');
    Route::post('listor/ica/listor', [IcaConnectionController::class, 'lists'])->middleware('throttle:10,1')->name('ica.lists');
    Route::put('listor/ica', [IcaConnectionController::class, 'update'])->name('ica.update');
    Route::delete('listor/ica', [IcaConnectionController::class, 'destroy'])->name('ica.destroy');
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
    Route::get('mat/recept/importera', [RecipeImportController::class, 'create'])->name('recipes.import');
    Route::post('mat/recept/importera', [RecipeImportController::class, 'store'])->middleware('throttle:10,1')->name('recipes.import.store');
    Route::post('mat/recept', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('mat/recept/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
    Route::get('mat/recept/{recipe}/andra', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('mat/recept/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('mat/recept/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');

    Route::get('mat/skafferi', [PantryController::class, 'index'])->name('pantry.index');
    Route::post('mat/skafferi', [PantryController::class, 'store'])->name('pantry.store');
    Route::put('mat/skafferi/{pantryItem}', [PantryController::class, 'update'])->name('pantry.update');
    Route::delete('mat/skafferi/{pantryItem}', [PantryController::class, 'destroy'])->name('pantry.destroy');
    Route::get('mat/skafferi/inventera/{location}', [InventoryController::class, 'show'])->whereIn('location', ['kyl', 'frys', 'skafferi', 'annat'])->name('inventory.show');
    Route::post('mat/skafferi/inventera/{location}/klar', [InventoryController::class, 'done'])->whereIn('location', ['kyl', 'frys', 'skafferi', 'annat'])->name('inventory.done');
    Route::patch('mat/skafferi/{pantryItem}/mangd', [InventoryController::class, 'update'])->name('inventory.update');

    Route::get('mat/forslag', SuggestionController::class)->name('meal-suggestions');

    Route::get('mat/vecka', [MealPlanController::class, 'index'])->name('meal-plan.index');
    Route::post('mat/vecka', [MealPlanController::class, 'store'])->name('meal-plan.store');
    Route::put('mat/vecka/{entry}', [MealPlanController::class, 'update'])->whereNumber('entry')->name('meal-plan.update');
    Route::delete('mat/vecka/{entry}', [MealPlanController::class, 'destroy'])->whereNumber('entry')->name('meal-plan.destroy');
    Route::post('mat/vecka/{entry}/lagad', [MealPlanController::class, 'cook'])->whereNumber('entry')->name('meal-plan.cook');
    Route::delete('mat/vecka/{entry}/lagad', [MealPlanController::class, 'uncook'])->whereNumber('entry')->name('meal-plan.uncook');
    Route::post('mat/vecka/handla', [MealPlanController::class, 'shoppingList'])->name('meal-plan.shopping-list');
    Route::put('mat/vecka/byte', [MealPlanController::class, 'dinnerSwitch'])->name('meal-plan.dinner-switch');
    Route::get('mat/ingredienser', [IngredientController::class, 'index'])->name('ingredients.index');

    // Imorgon is a day ahead in Idag now. Old links and bookmarks still work.
    Route::get('imorgon', fn () => to_route('dashboard', ['dag' => 1]));
    Route::get('veckan', [CalendarController::class, 'week'])->name('week');

    Route::get('aktiviteter', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('aktiviteter/ny', [ActivityController::class, 'create'])->name('activities.create');
    Route::post('aktiviteter/{activity}/{date}/installd', [ActivityCancellationController::class, 'store'])
        ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('activities.cancellations.store');
    Route::delete('aktiviteter/{activity}/{date}/installd', [ActivityCancellationController::class, 'destroy'])
        ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('activities.cancellations.destroy');
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

// Dagboken: parents only. Other adults and children never reach it.
Route::middleware(['auth', 'verified', 'family', 'role:parent'])->group(function () {
    Route::get('dagbok', [JournalController::class, 'index'])->name('journal.index');
    Route::post('dagbok', [JournalController::class, 'store'])->name('journal.store');
    Route::put('dagbok/{entry}', [JournalController::class, 'update'])->whereNumber('entry')->name('journal.update');
    Route::delete('dagbok/{entry}', [JournalController::class, 'destroy'])->whereNumber('entry')->name('journal.destroy');

    // Belöningar: only parents give points and answer wishes.
    Route::get('beloningar', [RewardController::class, 'index'])->name('rewards.index');
    Route::post('beloningar', [RewardController::class, 'store'])->name('rewards.store');
    Route::put('beloningar/{reward}', [RewardController::class, 'update'])->whereNumber('reward')->name('rewards.update');
    Route::delete('beloningar/{reward}', [RewardController::class, 'destroy'])->whereNumber('reward')->name('rewards.destroy');
    Route::post('beloningar/poang', [PointController::class, 'store'])->name('points.store');
    Route::put('beloningar/installningar', [PointController::class, 'settings'])->name('points.settings');
    Route::post('beloningar/poang/{transaction}/godkann', [PointController::class, 'approve'])->whereNumber('transaction')->name('points.approve');
    Route::post('beloningar/poang/{transaction}/inte-nu', [PointController::class, 'decline'])->whereNumber('transaction')->name('points.decline');
    Route::post('beloningar/onskningar/{redemption}/ja', [RedemptionController::class, 'approve'])->name('redemptions.approve');
    Route::post('beloningar/onskningar/{redemption}/nej', [RedemptionController::class, 'decline'])->name('redemptions.decline');
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

    Route::get('min-dag/recept', [ChildRecipeController::class, 'index'])->name('child.recipes');
    Route::get('min-dag/uppgift', [ChildTaskController::class, 'create'])->name('child.tasks.create');
    Route::post('min-dag/uppgift', [ChildTaskController::class, 'store'])->name('child.tasks.store');
    Route::delete('min-dag/uppgift/{task}', [ChildTaskController::class, 'destroy'])->name('child.tasks.destroy');

    Route::get('min-dag/veckan', [CalendarController::class, 'childWeek'])->name('child.week');
    Route::get('min-dag/beloningar', [ChildRewardController::class, 'index'])->name('child.rewards.index');
    Route::get('min-dag/bild', [ChildAvatarController::class, 'show'])->name('child.avatar');
    Route::put('min-dag/bild', [ChildAvatarController::class, 'update'])->name('child.avatar.update');
    Route::post('min-dag/beloningar/{reward}', [ChildRewardController::class, 'wish'])->name('child.rewards.wish');
    Route::scopeBindings()->group(function () {
        Route::post('min-dag/aktivitet/{activity}/{date}/sak/{item}', [ChildActivityController::class, 'pack'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.pack');
        Route::delete('min-dag/aktivitet/{activity}/{date}/sak/{item}', [ChildActivityController::class, 'unpack'])
            ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.unpack');
    });
    Route::get('min-dag/aktivitet/{activity}/{date}/packa', [ChildActivityController::class, 'show'])
        ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.show');
    Route::post('min-dag/aktivitet/{activity}/{date}/packat', [ChildActivityController::class, 'confirm'])
        ->where('date', '\\d{4}-\\d{2}-\\d{2}')->name('child.activities.confirm');

    Route::get('min-dag/rutin/{routine}', [ChildRoutineController::class, 'show'])->name('child.routines.show');
    Route::scopeBindings()->group(function () {
        Route::post('min-dag/rutin/{routine}/steg/{step}', [ChildRoutineController::class, 'check'])->name('child.routines.steps.check');
        Route::delete('min-dag/rutin/{routine}/steg/{step}', [ChildRoutineController::class, 'uncheck'])->name('child.routines.steps.uncheck');
        Route::post('min-dag/rutin/{routine}/steg/{step}/hoppa', [ChildRoutineController::class, 'skip'])->name('child.routines.steps.skip');
    });
});

// Laga: a recipe step by step, for adults and children (RecipePolicy::cook).
Route::middleware(['auth', 'family'])->group(function () {
    Route::get('laga/{recipe}', [CookController::class, 'show'])->name('recipes.cook');
});

// Picture files: for members and trusted devices such as the home display.
Route::get('bilder/{picture}', [PictureController::class, 'show'])->whereNumber('picture')->name('pictures.show');
