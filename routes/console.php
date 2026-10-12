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
