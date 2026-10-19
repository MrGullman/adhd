<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nattlig backup av databas och uppladdade filer (docs/ARCHITECTURE.md → Drift).
Schedule::command('backup:clean')->dailyAt('02:00')->timezone('Europe/Stockholm');
Schedule::command('backup:run')->dailyAt('02:30')->timezone('Europe/Stockholm');

// Återkommande uppgifter skapas en vecka framåt, strax efter midnatt svensk tid.
Schedule::command('tasks:generate-recurring')->dailyAt('00:05')->timezone('Europe/Stockholm')->withoutOverlapping();

// Skolmaten för denna och nästa vecka, varje morgon innan barnen vaknar.
Schedule::command('school-meals:fetch')->dailyAt('05:30')->timezone('Europe/Stockholm')->withoutOverlapping();

// Familjernas kalendrar (iCal-länkar) läses var 15:e minut.
Schedule::command('calendars:sync')->everyFifteenMinutes()->withoutOverlapping();

// Påminnelser som push-notiser (uppgifter, rutiner, kalender och packning).
Schedule::command('reminders:send')->everyMinute()->withoutOverlapping();

// What happened in the families' ICA lists comes into Handla (a trial per family).
Schedule::command('ica:pull')->everyFiveMinutes()->withoutOverlapping();

// Familjernas exporter sparas en vecka.
Schedule::command('exports:prune')->dailyAt('03:30')->timezone('Europe/Stockholm');
