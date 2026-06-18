<?php

use App\Console\Commands\AutoApprovePresence;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('presence:auto-approve', function () {
    (new AutoApprovePresence())();
})->purpose('Auto-approve pending presences (cron mode)');

Schedule::command('presence:auto-approve')->everyMinute();
Schedule::command('telescope:prune')->daily();
