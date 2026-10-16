<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ART-02. On shared hosting the scheduler itself runs from cron every minute (ARCH §9).
Schedule::command('ctp:publish-scheduled')->everyMinute()->withoutOverlapping();
